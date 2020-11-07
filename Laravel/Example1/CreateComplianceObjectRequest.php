<?php

namespace App\Http\Requests\Compliance;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateComplianceObjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'object_description' => 'required',
            'url_link' => [
                'required',
                'regex:/((?:https?:\/\/)?[^.\/]+(?:\.[^.\/]+)+(?:\/.*)?)$/'
            ],
        ];

        $templateFiles = $this->file('file');

        if ($templateFiles && count($templateFiles)) {
            $templateFilesCount = count($templateFiles) - 1;

            foreach (range(0, $templateFilesCount) as $index) {
                $rules['file.' . $index] = 'mimes:pdf|max:102400';
            }
        }

        return $rules;
    }

    /**
     * @return Validator
     * Modify input keys
     */
    protected function getValidatorInstance()
    {
        $data = $this->all('compliance_data');
        $data['object_description'] = json_decode($this->request->get('compliance_data'))->object_description;
        $data['url_link'] = json_decode($this->request->get('compliance_data'))->url_link;
        $data['object_id'] = json_decode($this->request->get('object_id')) ?? json_decode($this->request->get('compliance_data'))->object_id;
        $data['template_id'] = json_decode($this->request->get('template_id'));
        $data['designated_body_id'] = json_decode($this->request->get('designated_body_id'));
        unset($data['object_data']);
        $this->getInputSource()->replace($data);
        return parent::getValidatorInstance();
    }

    /**
     * @return array|string[]
     * Generate errors message
     */
    public function messages()
    {
        $message = [
            'object_description.required' => 'Object description is required',
            'url_link.required' => 'URL is required',
            'url_link.regex' => 'Please write correct URL',
        ];

        $templateFiles = $this->file('file');

        if ($templateFiles && count($templateFiles)) {
            $templateFilesCount = count($templateFiles) - 1;

            foreach (range(0, $templateFilesCount) as $index) {
                $message['file.' . $index  . '.mimes' ]= 'The file must be a PDF';
                $message['file.' . $index  . '.max' ]= 'File size should not be more than 100M';
            }
        }

        return $message;
    }


    /**
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors' => $validator->errors(),
            'status' => false,
        ], 200));
    }
}
