<?php

namespace App\Http\Controllers;

use App\Http\Requests\Compliance\CreateComplianceObjectRequest;
use App\Jobs\MoveComplianceTemplateFileToS3;
use App\Models\Compliance;
use App\Models\ComplianceFile;
use App\Models\ComplianceObject;
use App\Models\ComplianceTemplate;
use App\Models\DesignatedBody;
use App\Models\MainMaps;
use App\Models\SharedUserListType;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ComplianceUsersController extends Controller
{
    /**
     * @var bool
     * Check if user is Senior Admin
     */
    protected $isSeniorAdmin = false;

    /**
     * @var bool
     * Check if user is Admin
     */
    protected $isAdmin = false;

    /**
     * @var bool
     * Check if user is Designated Body Admin
     */
    protected $isOrganizationAdmin = false;

    /**
     * @var bool
     */
    protected $ownerMap = false;

    /**
     * @var null
     */
    protected $userId = null;

    /**
     * @var string
     * Files upload error messages
     */
    protected $fileUploadMessage = '';

    /**
     * @var array
     * Name for all uploaded files
     */
    protected $uploadedFiles = [];

    /**
     * @var array
     * Files that should move to S3
     */
    public $fileWhichMoveToS3 = [];


    /**
     * @param $request
     * @return mixed
     * Checks if all input data and upload files is valid 
     */
    protected function checkComplianceInputData($request) {
        $validated = $request->validated();
        if (!$this->isSeniorAdmin && !$this->isOrganizationAdmin && !$this->isAdmin) {
            $objectId = $request->input('object_id');

            if ($objectId) {
                $complianceObjectId = Compliance::where('id', $objectId)->first(['compliance_object_id']);

                if ($complianceObjectId) {
                    $complianceFiles = ComplianceFile::where('object_id', $complianceObjectId->compliance_object_id)->first(['file_names']);
                    $files = json_decode($complianceFiles->file_names);
                    if (!$request->file() && !$files) {
                        $this->fileUploadMessage = 'File is required, please choose file.';
                        return false;
                    } else {
                        if ($request->file() && (count($files) + count($request->file())) > 5) {
                            $this->fileUploadMessage = 'The count of files should not be more than 5.';
                            return false;
                        }
                    }
                }
            }
        }
        $this->uploadFile($request);
        return $validated;
    }

    /**
     * @param $request
     * Checks if there are files, then uploading
     */
    protected function uploadFile($request) {
        if ($request->file()) {
            $validData = $request->validated();
             $fileUploadResult = complianceFileUploadHandler(array_pop($validData), $this->isSeniorAdmin);
             $this->fileWhichMoveToS3 = $fileUploadResult['moved_files'];
             $this->uploadedFiles = $fileUploadResult['uploaded_files'];
             $this->fileUploadMessage = $fileUploadResult['file_upload_message'];

        }
    }

    /**
     * @param CreateComplianceObjectRequest $request
     * @return JsonResponse
     * Filling compliance fields for Compliance object
     */
    public function fillingComplianceFields(CreateComplianceObjectRequest $request) {
        if ($this->ownerMap) {
            $templateId = $request->input('template_id');
            $objectId = $request->input('object_id');
            $dbId = $request->input('designated_body_id');
            $newFiles = null;
            $complianceData = $this->checkComplianceInputData($request);
            if ($this->fileUploadMessage) return response()->json(['status' => false, 'message' => $this->fileUploadMessage]);


            if ($complianceData && $objectId && $templateId) {
                $createCompliance = Compliance::where('is_archived', '0')->where('is_validated', '0')->updateOrCreate(
                    [
                        'user_id' => auth()->user()->id,
                        'compliance_template_id' => $templateId,
                        'compliance_object_id' => $objectId,
                    ],
                    [
                        'description' => $complianceData['object_description'],
                        'url_link' => $complianceData['url_link'],
                        'designated_body_id' => $dbId,
                    ]
                );

                if (count($this->uploadedFiles) && $createCompliance) {
                    // Move files to S3
                    if (count($this->fileWhichMoveToS3)) {
                        $this->dispatch(new MoveComplianceTemplateFileToS3($this->fileWhichMoveToS3));
                    }

                    // Save template files
                    $newFiles = ComplianceFile::create([
                        'type' => '1', // This means that these files are for compliance object
                        'object_id' => $objectId,
                        'file_names' => json_encode($this->uploadedFiles)
                    ]);
                }

                $newCompliance = Compliance::where('id', $createCompliance->id)->first();
                $files = ComplianceFile::where('id', $newFiles->id)->first();

                if ($createCompliance) return response()->json(['status' => true, 'message' => 'Compliance create successfully', 'new_data' => $newCompliance, 'new_files' => $files]);
                return response()->json(['status' => false, 'message' => 'Compliance is not created']);
            }
            return response()->json(['status' => true, 'message' => 'Something is wrong']);
        }
        return response()->json(['status' => false, 'message' => 'You don\'t have the access to this action.']);
    }

}
