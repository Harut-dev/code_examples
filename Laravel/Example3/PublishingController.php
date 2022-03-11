<?php

namespace App\Http\Controllers;

use App\Models\Craft;
use App\Models\PublishedContentTime;
use App\Models\Refefile;
use Carbon\Carbon;
use App\Models\ScheduleTime;
use App\Models\ManageAccount;
use Facebook\Facebook;
use App\Models\PublishedContent;
use App\Jobs\PublishToYouTube;
use App\Jobs\PublishToTwitter;
use App\Jobs\PublishToFacebook;
use App\Jobs\PublishToLinkedIn;
use App\Jobs\PublishToInstagram;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\PublishingFormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PublishingController extends Controller
{
    /**
     * @var array
     */
    private $result = [
        'data' => [],
        'code' => 400,
        'messages' => 'Your session has expired! Please refresh the page and try again.'
    ];

    /**
     * @param PublishingFormRequest $request
     * @param Facebook $fb
     * @return JsonResponse
     *
     * Check Social Media type and publish
     */
    public function postContentOnSocialMedia(PublishingFormRequest $request, Facebook $fb): JsonResponse
    {
        $scheduleData = [];
        $shareOption = [];
        $shareIn = '';
        $userOrPageAccessToken = null;
        $inputData = $request->validated();
        $accountId = $inputData['account_id'];
        $socialMedia = $inputData['social_media'];
        $accountData = ManageAccount::find($accountId);
        $sharedName = config('constant.social_media_shared_name.' . $socialMedia);
        $inputData['post_content'] = str_replace("\r\n", "%0A", $inputData['post_content']);

        if (!$accountData) {
            $this->result['messages'] = 'Your account not found!';
            return $this->JsonResponse($this->result);
        }

        $socialMediaTokenNames = config('constant.social_media_tokens');
        $socialMediaToken = $socialMediaTokenNames[$socialMedia];

        $craft = Craft::where('slug', $inputData['slug'])->first();
        $file = Refefile::where('id', $inputData['file_id'])->first();

        if (!$file) {
            $this->result['messages'] = 'File not found!';
            return $this->JsonResponse($this->result);
        }

        $fileType = $inputData['publishing_file_type'];
        $postExcerpt = clearString($inputData['post_excerpt']);
        $postName = clearString($inputData['post_name']);
        $postContent = $inputData['post_content'];
        $token = ($socialMedia === 'facebook' || $socialMedia === 'instagram') ? $accountData->{$socialMediaToken} : json_decode($accountData->{$socialMediaToken});
        $user = Auth::user();

        if (!$token) {
            return $this->JsonResponse($this->result);
        }

        if ($socialMedia === 'facebook' || $socialMedia === 'instagram') {
            $accountOption = $accountData->{$inputData['share_in']};
            $shareOption = (array)$accountOption;
            $userOrPageAccessToken = $inputData['share_in'] === 'facebook_pages' ? $shareOption['access_token'] : $token;
            $shareIn = $inputData['share_in'];
            $inputData[$shareIn] = $shareOption;
        }

        if (array_key_exists('schedule_post', $inputData) && $inputData['schedule_post']) {
            $scheduleData['user_id'] = $user->id;
            $scheduleData['craft_id'] = $craft->id;
            $scheduleData['file_id'] = $file->id;
            $scheduleData['user_token'] = ($socialMedia === 'facebook' || $socialMedia === 'instagram') ? json_encode(["access_token" => $userOrPageAccessToken, $inputData['share_in'] => $inputData['share_with']]) : json_encode($token);
            $scheduleData['social_media'] = $socialMedia;

            $timezone = Auth::user()->timezone ?? $inputData['timezone'];
            if ($timezone) {

                $scheduleData['user_time_zone'] = $timezone;

                try {
                    $scheduleDate = Carbon::parse(Carbon::createFromFormat('Y-m-d H:i A', $inputData['schedule_date_time'], $timezone))->format('m/d/Y H:i');
                    $given = new \DateTime($inputData['schedule_date_time'], new \DateTimeZone($timezone));
                    $given->setTimezone(new \DateTimeZone('UTC'));
                    $scheduleData['schedule_time'] = strtotime($given->format('Y-m-d H:i:sP'));
                    $scheduleData['schedule_time_by_user'] = $scheduleDate;
                    $scheduleData['json_data'] = json_encode($inputData);
                    $scheduleData['status'] = 'pending';
                    $scheduleData['result_status'] = '';
                    $scheduleData['brand_id'] = $inputData['brand_id'];
                    $this->result['messages'] = 'Post Scheduled!';
                    $this->result['code'] = 200;

                    if ($inputData['placeholder_event']) {
                        $repeatedEvent = ScheduleTime::find($inputData['placeholder_event']);
                        $exceptedDates = $repeatedEvent->except_dates ? json_decode($repeatedEvent->except_dates) : [];
                        $exceptDate = Carbon::parse($scheduleDate, $timezone)->format('Y-m-d\TH:i:s');
                        $exceptedDates[] = $exceptDate;
                        $repeatedEvent->except_dates = json_encode($exceptedDates);
                        $repeatedEvent->save();
                    }

                    ScheduleTime::create($scheduleData);

                } catch (\Exception $e) {
                    $this->result['messages'] = 'Something went wrong! Please try a later.';
                    Log::emergency('Failed to schedule content.');
                    return $this->JsonResponse($this->result);
                }
            } else {
                $this->result['messages'] = 'Sorry! Your browser cannot detect the Time Zone.';
            }
        } else {
            switch ($socialMedia) {
                case 'facebook':
                    PublishToFacebook::dispatch([
                        'file' => $file,
                        'fb' => $fb,
                        'share_option' => $shareOption,
                        'post_name' => $postName,
                        'post_content' => $postContent,
                        'access_token' => $userOrPageAccessToken,
                        'share_in' => $shareIn,
                        'user' => $user,
                        'time_zone' => Auth::user()->timezone ?? $inputData['timezone']
                    ]);
                    break;
                case 'instagram':
                    PublishToInstagram::dispatch([
                        'file' => $file,
                        'fb' => $fb,
                        'share_option' => $shareOption,
                        'post_content' => $postContent,
                        'access_token' => $userOrPageAccessToken,
                        'save_cropped_content' => (int)$inputData['save_cropped_content'],
                        'cropped_content_details' => $inputData['cropped_content_details'],
                        'post_name' => $postName,
                        'user' => $user,
                        'time_zone' => Auth::user()->timezone ?? $inputData['timezone']
                    ]);
                    break;
                case 'twitter':
                    $requestToken = [
                        'token'  => $token->oauth_token,
                        'secret' => $token->oauth_token_secret,
                    ];

                    PublishToTwitter::dispatch([
                        'file' => $file,
                        'post_name' => $postName,
                        'post_content' => $postContent,
                        'request_token' => $requestToken,
                        'user' => $user,
                        'time_zone' => Auth::user()->timezone ?? $inputData['timezone']
                    ]);
                    break;
                case 'youtube':
                    $getTags = '';

                    if(count($craft->getPrimaryTopic)){
                        $getTags = implode(',', $craft->getPrimaryTopic()->pluck('primary_topics')->toArray());
                    }

                    PublishToYouTube::dispatch([
                        'access_token' => $token,
                        'account_id' => $accountId,
                        'share_in' => $inputData['share_in'],
                        'file' => $file,
                        'post_name' => $postName,
                        'post_content' => $postContent,
                        'tag' => $getTags,
                        'user' => $user,
                        'time_zone' => Auth::user()->timezone ?? $inputData['timezone']
                    ]);
                    break;
                case 'linkedin':
                    PublishToLinkedIn::dispatch([
                        'file_type' => $fileType,
                        'access_token' => $token->access_token,
                        'post_content' => $postContent,
                        'file' => $file,
                        'post_name' => $postName,
                        'post_excerpt' => $postExcerpt,
                        'share_with' => $inputData['share_with'],
                        'share_in' => $inputData['share_in'],
                        'account_id' => $accountId,
                        'user' => $user,
                        'time_zone' => Auth::user()->timezone ?? $inputData['timezone']
                    ]);
                    break;
            }

            PublishedContent::add($craft, $file, $sharedName);
            PublishedContentTime::add($file, explode('_', $sharedName)[0], \auth()->user()->timezone);
            $this->result['messages'] = 'Publishing in processing. We will notify you when it will be done!';
            $this->result['code'] = 200;
        }

        return $this->JsonResponse($this->result);
    }

    /**
     * @param PublishingFormRequest $request
     * @return JsonResponse
     */
    public function checkVideoResolution(Request $request): JsonResponse
    {
        $fileId = $request->input('file_id');
        if (!is_numeric($fileId)) return response()->json(['status' => false, 'message' => 'Wrong Data']);
        $file = Refefile::find($fileId);

        $fileHandling = \FFMpeg\FFProbe::create();

        $dimensions = $fileHandling
            ->streams($file->refe_file_path)
            ->videos()
            ->first()
            ->getDimensions();

        $res = array_values((array)$dimensions);
        $aspectRatio = $this->getAspectRatio($res[0], $res[1]);

        if (($aspectRatio[0] / $aspectRatio[1]) < (4 / 7) || ($aspectRatio[0] / $aspectRatio[1]) > (16 / 9)) {
            return response()->json(['status' => true]);
        }

        return response()->json(['status' => false]);
    }

    /**
     * @param int $width
     * @param int $height
     * @return float[]|int[]
     */
    public function getAspectRatio(int $width, int $height): array
    {
        // search for greatest common divisor
        $greatestCommonDivisor = static function($width, $height) use (&$greatestCommonDivisor) {
            return ($width % $height) ? $greatestCommonDivisor($height, $width % $height) : $height;
        };

        $divisor = $greatestCommonDivisor($width, $height);

        return [$width / $divisor, $height / $divisor];
    }
}
