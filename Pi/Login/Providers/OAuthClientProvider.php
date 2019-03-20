<?php

namespace CodePi\Login\Providers;

//use InvalidArgumentException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use Session,Config;

class OAuthClientProvider extends AbstractProvider {

    use BearerAuthorizationTrait;

    /**
     * @var string
     */
    private $urlAuthorize ;

    /**
     * @var string
     */
    private $urlAccessToken ;

    /**
     * @var string
     */
    private $urlResourceOwnerDetails;

    /**
     * @var string
     */
    private $accessTokenMethod;

    /**
     * @var string
     */
    private $accessTokenResourceOwnerId;

    /**
     * @var array|null
     */
    private $scopes = null;

    /**
     * @var string
     */
    private $scopeSeparator;

    /**
     * @var string
     */
    private $responseError = 'error';

    /**
     * @var string
     */
    private $responseCode;

    /**
     * @var string
     */
    private $responseResourceOwnerId = 'users_id';

    /**
     * @param array $options
     * @param array $collaborators
     */
    public function __construct(array $options = [], array $collaborators = []) {
        $this->urlAuthorize =  Config::get('app.iviessourl').'authorize';
        $this->urlAccessToken = Config::get('app.iviessourl').'token';
        $this->urlResourceOwnerDetails = Config::get('app.iviessourl').'resource';
        $possible = $this->getConfigurableOptions();
        $configured = array_intersect_key($options, array_flip($possible));

        foreach ($configured as $key => $value) {
            $this->$key = $value;
        }

        // Remove all options that are only used locally
        $options = array_diff_key($options, $configured);

        parent::__construct($options, $collaborators);
    }

    /**
     * Returns all options that can be configured.
     *
     * @return array
     */
    protected function getConfigurableOptions() {
        return array_merge($this->getRequiredOptions(), [
            'accessTokenMethod',
            'accessTokenResourceOwnerId',
            'scopeSeparator',
            'responseError',
            'responseCode',
            'responseResourceOwnerId',
            'scopes',
        ]);
    }

    /**
     * Returns all options that are required.
     *
     * @return array
     */
    protected function getRequiredOptions() {
        return [
            'urlAuthorize',
            'urlAccessToken',
            'urlResourceOwnerDetails',
        ];
    }

    /**
     * Verifies that all required options have been passed.
     *
     * @param  array $options
     * @return void
     * @throws InvalidArgumentException
     */
    private function assertRequiredOptions(array $options) {
        $missing = array_diff_key(array_flip($this->getRequiredOptions()), $options);

        if (!empty($missing)) {
            throw new Exception(
            'Required options not defined: ' . implode(', ', array_keys($missing))
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getBaseAuthorizationUrl() {
        return $this->urlAuthorize;
    }

    /**
     * @inheritdoc
     */
    public function getBaseAccessTokenUrl(array $params) {
        return $this->urlAccessToken;
    }

    /**
     * @inheritdoc
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token) {
        return $this->urlResourceOwnerDetails;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultScopes() {
        return $this->scopes;
    }

    /**
     * @inheritdoc
     */
    protected function getAccessTokenMethod() {
        return $this->accessTokenMethod ? : parent::getAccessTokenMethod();
    }

    /**
     * @inheritdoc
     */
    protected function getAccessTokenResourceOwnerId() {
        return $this->accessTokenResourceOwnerId ? : parent::getAccessTokenResourceOwnerId();
    }

    /**
     * @inheritdoc
     */
    protected function getScopeSeparator() {
        return $this->scopeSeparator ? : parent::getScopeSeparator();
    }

    /**
     * @inheritdoc
     */
    protected function checkResponse(ResponseInterface $response, $data) {
        if (!empty($data[$this->responseError])) {
            $error = $data[$this->responseError];
            $code = $this->responseCode ? $data[$this->responseCode] : 0;
            throw new IdentityProviderException($error, $code, $data);
        }
    }

    /**
     * @inheritdoc
     */
    protected function createResourceOwner(array $response, AccessToken $token) {
        return new GenericResourceOwner($response, $this->responseResourceOwnerId);
    }

    /**
     * Returns the default headers used by this provider.
     *
     * Typically this is used to set 'Accept' or 'Content-Type' headers.
     *
     * @return array
     */
    protected function getDefaultHeaders() {

        return ['user-agent' => filter_input(INPUT_SERVER, 'HTTP_USER_AGENT')]; //$_SERVER['HTTP_USER_AGENT']];
    }

    /**
     * Builds the login URL.
     * @return string Logout URL
     */
    public function getLoginUrl() {
        $loginUrl = $this->getAuthorizationUrl();
        Session::put('oauth2state', $this->getState()); // store this for security reason. To avoid CSRF 
        return $loginUrl.'&scope=openid&nonce='.time();
    }

    /**
     * Builds the logout URL.
     * @return string Logout URL
     */
    public function getLogoutUrl($redirect_url = '') {
        //if($this->isTokenExpired()){
        $objAccessToken = $this->getLocalAccessTokenSession();

        $params = [
            'redirect_uri' => urlencode('http://' . filter_input(INPUT_SERVER, 'HTTP_HOST') . filter_input(INPUT_SERVER, 'PHP_SELF')),
            'access_token' => $objAccessToken->getToken(),
        ];

        if ($redirect_url != '') {
            $params['redirect_uri'] = urlencode($redirect_url);
        }
        return $url = Config::get('app.iviessourl').'oauthlogout?' . http_build_query($params, null, '&');
        // }
    }

    public function getMyAccountUrl() {
        $objAccessToken = $this->getLocalAccessTokenSession();

        $params = [
            'access_token' => $objAccessToken->getToken(),
        ];

        return $url = Config::get('app.iviessourl').'profile?' . http_build_query($params, null, '&');
    }

    public function setAccessTokenByCode($code) {
        $accessToken = $this->getAccessToken('authorization_code', [
            'code' => $code
        ]);
    }

    /**
     * set the Local access token into session for future purpose
     */
    public function setLocalAccessTokenSession($accessToken) {

        if (!isset($accessToken)) {

            return false;
        }

        try {

            $arrAccessToken = [ 'access_token' => $accessToken->getToken(), 'expires' => $accessToken->getExpires(),
                'refresh_token' => $accessToken->getRefreshToken()
            ];
            Session::put('oauth_token_' . $this->clientId, $arrAccessToken);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * get the stored access token
     * @return object Token
     */
    public function getLocalAccessTokenSession() {

        if (!Session::has('oauth_token_' . $this->clientId)) {
            //throw new \Exception("Session expired login again", 1);
            redirect()->to('/login');
        }
        $arrAccessTokenInfo = Session::get('oauth_token_' . $this->clientId);
        if (!isset($arrAccessTokenInfo))
            return false;

        try {

            $prepared = $this->prepareAccessTokenResponse($arrAccessTokenInfo);
            $token = $this->createAccessToken($prepared, $this->verifyGrant('authorization_code'));

            return $token;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * unset the stored access token
     */
    public function unSetLocalAccessTokenSession() {
        if (Session::has('oauth_token_' . $this->clientId)) {
            Session::forget('oauth_token_' . $this->clientId);
        }
    }

    /**
     * Requests an access token using a specified grant and option set. Overrided this method
     *
     * @param  mixed $grant
     * @param  array $options
     * @return AccessToken
     */
    public function getAccessToken($grant, array $options = []) {
        $grant = $this->verifyGrant($grant);

        $params = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
        ];

        $params = $grant->prepareRequestParameters($params, $options);
       
        $request = $this->getAccessTokenRequest($params);
      
        $response = $this->getParsedResponse($request);
//         echo "<pre>";
// print_r($response);exit;
         $prepared = $this->prepareAccessTokenResponse($response);
         $prepared['access_token'] = $prepared['id_token'];
         
        $token = $prepared;//$this->createAccessToken($prepared, $grant);
        //print_r($token);exit;
        // set Local Session for Access Token to access the resource
        //$this->setLocalAccessTokenSession($token);

        return $token;
    }

    /**
     * Requests to get the userInfo
     *
     * @return UserInfo
     */
    public function getUserInfo() {
  
        $token = $this->getLocalAccessTokenSession();

        $params = ['request_type' => 'user_info'];
        $request = $this->getAuthenticatedRequest('GET', $this->getResourceOwnerDetailsUrl($token) . '?' . http_build_query($params, null, '&'), $token);
        $response = $this->getParsedResponse($request);
        return json_encode($response);
    }
    
    /**
     * Get User permission
     * @params string $profile_id
     * @return json
     */
    public function getUserPermission($profile_id=''){
        $token = $this->getLocalAccessTokenSession();

        $params = ['request_type' => 'permissions','profile_id'=>$profile_id];
        $request = $this->getAuthenticatedRequest('GET', $this->getResourceOwnerDetailsUrl($token) . '?' . http_build_query($params, null, '&'), $token);
        $response = $this->getParsedResponse($request);
        return json_encode($response);
    }

    /**
     * Check token has expired or not
     */
    public function hasTokenExpired() {
        try {
            $token = $this->getLocalAccessTokenSession();
            
            $params = ['request_type' => 'token_check'];
            $request = $this->getAuthenticatedRequest('GET', $this->getResourceOwnerDetailsUrl($token) . '?' . http_build_query($params, null, '&'), $token);
            $response = $this->getParsedResponse($request);
            
            if (isset($response['result']) && $response['result']['success']) {
                
                return false;
            } else {
                return true;
            }
        } catch (GuzzleHttp\Exception\ConnectException $ex) {
            return false;
        }
    }

}
