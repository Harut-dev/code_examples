<?php

namespace App\LinkedIn;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Session;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\Psr7\build_query;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class LinkedInClass
{
    const OAUTH2_GRANT_TYPE = 'authorization_code';
    const OAUTH2_RESPONSE_TYPE = 'code';

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $clientSecret;

    /**
     * @var string
     */
    protected $scopes;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var string
     */
    protected $redirectUrl;

    /**
     * @var string
     */
    protected $apiRoot;

    /**
     * @var string
     */
    protected $oAuthApiRoot;

    /**
     * @var bool
     */
    protected $useTokenParam = false;

    /**
     * @var string
     */
    protected $sharedUrl = '';

    /**
     * @var array
     */
    protected $apiHeaders = [
        'Content-Type' => 'application/json',
        'x-li-format' => 'json',
    ];

    protected $allowedMethods = ['GET', 'POST', 'PUT'];


    public function __construct()
    {
        $this->setClientId(config('services.linkedin_api.linkedin_app'));
        $this->setClientSecret(config('services.linkedin_api.linkedin_secret'));
        $this->setRedirectUrl(config('services.linkedin_api.linkedin_callback'));
        $this->setApiRoot(config('services.linkedin_api.linkedin_api_root'));
        $this->setOAuthApiRoot(config('services.linkedin_api.linkedin_oauth2_api_root'));
    }

    /**
     * @return bool
     */
    private function isUsingTokenParam(): bool
    {
        return $this->useTokenParam;
    }

    /**
     * @param bool $useTokenParam
     * @return $this
     */
    private function setUseTokenParam(bool $useTokenParam): LinkedInClass
    {
        $this->useTokenParam = $useTokenParam;
        return $this;
    }

    /**
     * @return array
     */
    private function getApiHeaders(): array
    {
        return $this->apiHeaders;
    }

    /**
     * @return string
     */
    private function getApiRoot(): string
    {
        return $this->apiRoot;
    }

    /**
     * @return string
     */
    private function getOAuthApiRoot(): string
    {
        return $this->oAuthApiRoot;
    }

    /**
     * @param string $oAuthApiRoot
     * @return $this
     */
    private function setOAuthApiRoot(string $oAuthApiRoot): LinkedInClass
    {
        $this->oAuthApiRoot = $oAuthApiRoot;
        return $this;
    }

    /**
     * @param string $apiRoot
     * @return $this
     */
    private function setApiRoot(string $apiRoot): LinkedInClass
    {
        $this->apiRoot = $apiRoot;
        return $this;
    }

    /**
     * @return string
     */
    private function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     * @return $this
     */
    private function setClientId(string $clientId): LinkedInClass
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @return string
     */
    private function getScopes(): string
    {
        return $this->scopes;
    }

    /**
     * @param $scopes
     * @return $this
     *
     * Permissions that application requires
     */
    public function setScopes($scopes): LinkedInClass
    {
        $this->scopes = $scopes;
        return $this;
    }

    /**
     * @return string
     */
    private function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * @param string $clientSecret
     * @return $this
     */
    private function setClientSecret(string $clientSecret): LinkedInClass
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    /**
     * @return string
     */
    private function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    /**
     * @param $callback
     * @return $this
     */
    private function setRedirectUrl($callback): LinkedInClass
    {
        $this->redirectUrl = url($callback);
        return $this;
    }

    /**
     * @return string
     *
     * Set unique state to prevent CSRF attacks.
     */
    private function getState(): string
    {
        if (empty($this->state)) {
            $this->setState(
                rtrim(
                    base64_encode(uniqid('', true)),
                    '='
                )
            );
        }

        return $this->state;
    }

    /**
     * @param string $state
     * @return $this
     *
     * A value passed to prevent CSRF attacks.
     */
    private function setState(string $state): LinkedInClass
    {
        Session::put('linkedin_state', $state);
        $this->state = $state;
        return $this;
    }

    /**
     * @param $shareIn
     * @param $urn
     * @param $accessToken
     */
    private function setSharedUrl($shareIn, $urn, $accessToken)
    {
        if ($shareIn === 'profile') {
            $this->sharedUrl = 'https://www.linkedin.com/in/' . $this->getPerson($accessToken)->vanityName . '/detail/recent-activity/';
        } else {
            $parseUrn = explode(':', $urn);
            $this->sharedUrl = 'https://www.linkedin.com/company/' .  array_pop($parseUrn) . '/admin/';
        }
    }

    /**
     * @return string
     */
    private function getSharedUrl() : string
    {
        return $this->sharedUrl;
    }

    /**
     * Retrieve URL which will be used to send User to LinkedIn
     * for authentication
     *
     * @return string
     */
    public function getAuthUrl(): string
    {
        $params = [
            'response_type' => self::OAUTH2_RESPONSE_TYPE,
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUrl(),
            'state' => $this->getState(),
            'scope' => $this->getScopes(),
        ];

        return $this->buildUrl('authorization', $params);
    }

    /**
     * @param string $code
     * @return mixed
     */
    public function getAccessToken($code = '')
    {
        if (!empty($code)) {
            $uri = $this->buildUrl('accessToken', []);
            $guzzle = new GuzzleClient([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-li-format' => 'json',
                    'Connection' => 'Keep-Alive'
                ]
            ]);
            try {
                $response = $guzzle->post($uri, ['form_params' => [
                    'grant_type' => self::OAUTH2_GRANT_TYPE,
                    self::OAUTH2_RESPONSE_TYPE => $code,
                    'redirect_uri' => $this->getRedirectUrl(),
                    'client_id' => $this->getClientId(),
                    'client_secret' => $this->getClientSecret(),
                ]]);

                return $this->parseResponse($response);
            } catch (RequestException $e) {
                return $e->getMessage();
            }
        }
    }

    /**
     * @param $refreshToken
     * @return mixed|string
     */
    public function getAccessTokenFromRefreshToken($refreshToken)
    {
        $uri = $this->buildUrl('accessToken', []);
        $guzzle = new GuzzleClient([
            'headers' => [
                'Content-Type' => 'application/json',
                'x-li-format' => 'json',
                'Connection' => 'Keep-Alive'
            ]
        ]);
        try {
            $response = $guzzle->post($uri, ['form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
            ]]);

            return $this->parseResponse($response);
        } catch (RequestException $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param $accessToken
     * @return array|mixed|string
     */
    public function getPerson($accessToken)
    {
        $this->setUseTokenParam(false);
        return $this->api('me', $accessToken, [], 'GET', true);
    }

    /**
     * @param $accessToken
     * @return mixed
     */
    public function getUserEmail($accessToken)
    {
        try {
            $uri = 'clientAwareMemberHandles?q=members&projection=(elements*(primary,type,handle~))';
            return $this->getProfileAndPageData($uri, $accessToken);
        } catch (\Exception $e) {
            return 'Something went wrong. Please try again.';
        }

    }

    /**
     * @param $accessToken
     * @return array|mixed|string
     */
    public function getCompanyPages($accessToken)
    {
        try {
            $uri = 'organizationAcls?q=roleAssignee&role=ADMINISTRATOR&projection=(elements*(*,roleAssignee~(localizedFirstName, localizedLastName), organization~(localizedName)))';
            return $this->getProfileAndPageData($uri, $accessToken);
        } catch (\Exception $e) {
            return 'Something went wrong. Please try again.';
        }

    }

    private function getProfileAndPageData($uri, $accessToken)
    {
        $headers = $this->getApiHeaders();
        $headers['Authorization'] = 'Bearer ' . $accessToken;
        $guzzle = new GuzzleClient([
            'base_uri' => $this->getApiRoot(),
            'headers' => $headers,
        ]);
        $response = $guzzle->get($uri);
        return $this->parseResponse($response);
    }

    /**
     * @param $accessToken
     * @param $message
     * @param $urn
     * @param $shareIn
     * @param string $visibility
     * @return ResponseInterface|string
     *
     * Update user status on LinkedIn page
     */
    public function linkedInTextPost($accessToken , $message, $urn, $shareIn, $visibility = 'PUBLIC')
    {
        $params = [
            "author" => $urn,
            "lifecycleState" => "PUBLISHED",
            "specificContent" => [
                "com.linkedin.ugc.ShareContent" => [
                    "shareCommentary" => [
                        "text" => urldecode($message)
                    ],
                    "shareMediaCategory" => "NONE",
                ],
            ],
            "visibility" => [
                "com.linkedin.ugc.MemberNetworkVisibility" => $visibility,
            ]
        ];

        $this->setSharedUrl($shareIn, $urn, $accessToken);
        $this->setUseTokenParam(false);
        return $this->api('ugcPosts', $accessToken, $params, 'POST');
    }

    /**
     * @param $accessToken
     * @param $message
     * @param $filePath
     * @param $title
     * @param $description
     * @param $urn
     * @param $shareIn
     * @param $mediaType
     * @param string $visibility
     * @return array|mixed|string
     *
     * Share image or video on User LinkedIn page
     */
    public function linkedInMediaPost($accessToken, $message, $filePath,  $title, $description, $urn, $shareIn, $mediaType, $visibility = 'PUBLIC')
    {
        $prepareUrl = 'assets?action=registerUpload&oauth2_access_token=' . $accessToken;
        $prepareRequest =  [
            "registerUploadRequest" => [
                "recipes" => [
                    "urn:li:digitalmediaRecipe:feedshare-image"
                ],
                "owner" => $urn,
                "serviceRelationships" => [
                    [
                        "relationshipType" => "OWNER",
                        "identifier" => "urn:li:userGeneratedContent"
                    ],
                ],
                "supportedUploadMechanism" => [
                    "SYNCHRONOUS_UPLOAD"
                ]
            ],
        ];

        try {
            $this->setSharedUrl($shareIn, $urn, $accessToken);
            $this->setUseTokenParam(true);
            $prepareResponse = $this->api($prepareUrl, $accessToken, $prepareRequest, 'POST', true);
            $uploadURL = $prepareResponse->value->uploadMechanism->{"com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest"}->uploadUrl;
            $assetId = $prepareResponse->value->asset;
            $client = new GuzzleClient();
            $client->post($uploadURL, [
                'headers' => [ 'Authorization' => 'Bearer ' . $accessToken ],
                'body' => fopen($filePath, 'r'),
            ]);
        } catch (\Exception $e) {
            Log::error('LinkedIn upload file Error: ' . $e->getMessage());
            return 'File not uploaded';
        }

        $params = [
            "author" => $urn,
            "lifecycleState" => "PUBLISHED",
            "specificContent" => [
                "com.linkedin.ugc.ShareContent" => [
                    "shareCommentary" => [
                        "text" => urldecode($message)
                    ],
                    "shareMediaCategory" => $mediaType,
                    "media"=> [[
                        "status" => "READY",
                        "description"=> [
                            "text" => substr($description, 0, 200),
                        ],
                        "media" =>  $assetId,
                        "title" => [
                            "text" => $title,
                        ],
                    ]],
                ],

            ],
            "visibility" => [
                "com.linkedin.ugc.MemberNetworkVisibility" => $visibility ,
            ]
        ];

        $this->setUseTokenParam(false);
        return $this->api('ugcPosts', $accessToken, $params, 'POST');
    }

    /**
     * @param string $endpoint
     * @param array $params
     * @return string
     */
    private function buildUrl(string $endpoint, array $params): string
    {
        $url = $this->getOAuthApiRoot();
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $authority = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $path .= trim($endpoint, '/');
        $fragment = '';

        return Uri::composeComponents(
            $scheme,
            $authority,
            $path,
            build_query($params),
            $fragment
        );
    }

    /**
     * @param string $endpoint
     * @param $accessToken
     * @param array $params
     * @param string $method
     * @param bool $parseResponse
     * @return array|mixed|string
     */
    private function api(string $endpoint, $accessToken, array $params = [], $method = 'GET', $parseResponse = false)
    {
        $headers = $this->getApiHeaders();
        $options = $this->prepareOptions($params, $method);
        $this->isMethodSupported($method);

        if ($this->isUsingTokenParam()) {
            $params['oauth2_access_token'] = $accessToken;
        } else {
            $headers['Authorization'] = 'Bearer ' . $accessToken;
        }

        $guzzle = new GuzzleClient([
            'base_uri' => $this->getApiRoot(),
            'headers' => $headers,
        ]);

        if (!empty($params) && 'GET' === $method) {
            $endpoint .= '?' . build_query($params);
        }

        try {
            $response = $guzzle->request($method, $endpoint, $options);

            if ($parseResponse) return $this->parseResponse($response);
            return [
                'status' => true,
                'link' => $this->getSharedUrl(),
                'message' => 'Your content successfully published on LinkedIn. You can visit to LinkedIn and check it.',
                'publishing_status' => 'done'
            ];
        } catch (\Exception | GuzzleException $e) {
            return [
                'status' => false,
                'link' => null,
                'message' => 'Your content can\'t successfully published on LinkedIn. ' . $e->getMessage(),
                'publishing_status' => 'error'
            ];
        }
    }

    /**
     * @param array $params
     * @param string $method
     * @return mixed
     */
    private function prepareOptions(array $params, string $method): array
    {
        $options = [];
        if ($method === 'POST') {
            $options['body'] = \GuzzleHttp\json_encode($params);
        }
        return $options;
    }

    /**
     * @param $method
     */
    private function isMethodSupported($method)
    {
        if (!in_array($method, $this->allowedMethods)) {
            throw new \InvalidArgumentException('The method is not correct');
        }
    }

    /**
     * @param $response
     * @return mixed
     */
    private function parseResponse($response)
    {
        return json_decode($response->getBody()->getContents());
    }
}
