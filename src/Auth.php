<?php

namespace Hroc\Saml2;

use \DOMDocument;
use Hroc\Saml2\ExtendedOneLoginAuth as OneLoginAuth;
use Hroc\Saml2\Models\Tenant;
use Hroc\Saml2\Events\SignedOut;
use Hroc\Saml2\Models\Saml2LoginRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use OneLogin\Saml2\Error as OneLoginError;
use OneLogin\Saml2\Utils;

/**
 * Class Auth
 *
 * @package Hroc\Saml2
 */
class Auth
{
    /**
     * The base authentication handler.
     *
     * @var OneLoginAuth
     */
    protected $base;

    /**
     * The resolved tenant.
     *
     * @var Tenant
     */
    protected $tenant;

    /**
     * Auth constructor.
     *
     * @param OneLoginAuth $auth
     * @param Tenant $tenant
     */
    public function __construct(OneLoginAuth $auth, Tenant $tenant)
    {
        $this->base = $auth;
        $this->tenant = $tenant;
    }

    /**
     * Checks whether a user is authenticated.
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->base->isAuthenticated();
    }

    /**
     * Create a SAML2 user.
     *
     * @return Saml2User
     */
    public function getSaml2User()
    {
        return new Saml2User($this->base, $this->tenant);
    }

    /**
     * The ID of the last message processed.
     *
     * @return String
     */
    public function getLastMessageId()
    {
        return $this->base->getLastMessageId();
    }

    /**
     * The ID of the last message processed.
     *
     * @return String
     */
    public function getLastRequestId()
    {
        return $this->base->getLastRequestId();
    }

    /**
     * Initiate a saml2 login flow.
     *
     * It will redirect! Before calling this, check if user is
     * authenticated (here in saml2). That would be true when the assertion was received this request.
     *
     * @param string|null $returnTo The target URL the user should be returned to after login.
     * @param array $parameters Extra parameters to be added to the GET
     * @param bool $forceAuthn When true the AuthNReuqest will set the ForceAuthn='true'
     * @param bool $isPassive When true the AuthNReuqest will set the Ispassive='true'
     * @param bool $stay True if we want to stay (returns the url string) False to redirect
     * @param bool $setNameIdPolicy When true the AuthNReuqest will set a nameIdPolicy element
     *
     * @return string|null If $stay is True, it return a string with the SLO URL + LogoutRequest + parameters
     *
     * @throws OneLoginError
     */
    public function login(
        $returnTo = null,
        $parameters = array(),
        $forceAuthn = false,
        $isPassive = false,
        $stay = false,
        $setNameIdPolicy = true
    )
    {
        $loginRedirect = $this->base->login($returnTo, $parameters, $forceAuthn, $isPassive, $stay, $setNameIdPolicy);

        if(config('saml2.debug')) {
            Log::channel(config('saml2.logChannel'))->info('ACS Login', [
                'lastRequestId' => $this->base->getLastRequestId(),
                'lastMessageId' => $this->base->getLastMessageId(),
                'lastAssertionId' => $this->base->getLastAssertionId(),
                'lastRequestXml' => $this->base->getLastRequestXML(),
                'lastResponseXml' => $this->base->getLastResponseXML(),
            ]);
        }

        return $loginRedirect;
    }

    /**
     * Initiate a saml2 logout flow. It will close session on all other SSO services.
     * You should close local session if applicable.
     *
     * @param string|null $returnTo The target URL the user should be returned to after logout.
     * @param string|null $nameId The NameID that will be set in the LogoutRequest.
     * @param string|null $sessionIndex The SessionIndex (taken from the SAML Response in the SSO process).
     * @param string|null $nameIdFormat The NameID Format will be set in the LogoutRequest.
     * @param bool $stay True if we want to stay (returns the url string) False to redirect
     * @param string|null $nameIdNameQualifier The NameID NameQualifier will be set in the LogoutRequest.
     *
     * @return string|null If $stay is True, it return a string with the SLO URL + LogoutRequest + parameters
     *
     * @throws OneLoginError
     */
    public function logout(
        $returnTo = null,
        $nameId = null,
        $sessionIndex = null,
        $nameIdFormat = null,
        $stay = false,
        $nameIdNameQualifier = null
    )
    {
        $auth = $this->base;

        return $auth->logout($returnTo, [], $nameId, $sessionIndex, $stay, $nameIdFormat, $nameIdNameQualifier);
    }

    /**
     * Process the SAML Response sent by the IdP.
     *
     * @return array|null
     *
     * @throws OneLoginError
     * @throws \OneLogin\Saml2\ValidationError
     */
    public function acs()
    {
        // we do not have the same session because the response is posted back and we have cookie setting same_site=lax therefore we can not get the AuthNRequestID from the session
        // this means we can not pass the AuthNRequestID into processResponse so we need to find another way to check it
        $this->base->processResponse();

        if(config('saml2.debug')) {
            Log::channel(config('saml2.logChannel'))->info('ACS Processing', [
                'lastRequestId' => $this->base->getLastRequestId(),
                'lastMessageId' => $this->base->getLastMessageId(),
                'lastAssertionId' => $this->base->getLastAssertionId(),
                'lastRequestXML' => $this->base->getLastRequestXML(),
                'lastResponseXML' => $this->base->getLastResponseXML(),
            ]);
        }

        // check the AuthNRequestID from the response, we can not confirm it was this user but we have stored it in the db
        $inResponse = $this->getInResponseTo();
        if(!$inResponse) {
            return ['error' => 'Could not authenticate due to missing AuthNRequestID'];
        }

        $dbLoginRequest = Saml2LoginRequest::where('request_id', $inResponse)
            ->where('created_at', '>', Carbon::now()
            ->subMinutes(config('sso.authNRequest_expiry_mins', 60)))       // in case of the VERY unlikely event of a request id collision
            ->first();

        if (!$dbLoginRequest) {
            return ['error' => 'Could not match InResponseTo db entries'];
        }

        if ($dbLoginRequest->is_processed) {
            return ['error' => 'Could not authenticate due to already processed AuthNRequestID'];
        }

        $errors = $this->base->getErrors();

        if (!empty($errors)) {
            if(config('saml2.debug')) {
                Log::channel(config('saml2.logChannel'))->error('ACS Processing Error', ["OneLogin Errors" => $errors]);
            }
            return $errors;
        }

        if (!$this->base->isAuthenticated()) {
            return ['error' => 'Could not authenticate'];
        }

        // register that this response was processed so it can not be processed again to reduce replay attacks
        $dbLoginRequestId = $dbLoginRequest->id;
        $dbLoginRequest->response_processed = 1;
        $dbLoginRequest->save();
        $dbLoginRequest->delete();

        // store the login request id in the session so we can use it SignedIn event
        Session::put('sso.third_party.saml2_login_request_id', $dbLoginRequestId);

        return null;
    }

    /**
     * Get the InResponseTo info from a SAML Response from the Idp. 
     * 
     * @return string|null
     */
    private function getInResponseTo() {
        // since we can not get the previously generated DOMDocument from the oneLogin library (we can only get the xml string) we need to parse the response xml AGAIN (such a waste of processing)
        $document = new DOMDocument();
        $document = Utils::loadXML($document, $this->base->getLastResponseXML());

        $inResponseTo = null;
        if ($document->documentElement->hasAttribute('InResponseTo')) {
            $inResponseTo = $document->documentElement->getAttribute('InResponseTo');
        }

        return $inResponseTo;
    }

    /**
     * Process the SAML Logout Response / Logout Request sent by the IdP.
     *
     * Returns an array with errors if it can not logout.
     *
     * @param bool $retrieveParametersFromServer
     *
     * @return array
     *
     * @throws \OneLogin\Saml2\Error
     */
    public function sls($retrieveParametersFromServer = false)
    {
        $this->base->processSLO(true, null, $retrieveParametersFromServer, function () {
            event(new SignedOut());
        });

        $errors = $this->base->getErrors();

        return $errors;
    }

    /**
     * Get metadata about the local SP. Use this to configure your Saml2 IdP.
     *
     * @return string
     *
     * @throws \OneLogin\Saml2\Error
     * @throws \Exception
     * @throws \InvalidArgumentException If metadata is not correctly set
     */
    public function getMetadata()
    {
        $settings = $this->base->getSettings();
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);

        if (!count($errors)) {
            return $metadata;
        }

        throw new \InvalidArgumentException(
            'Invalid SP metadata: ' . implode(', ', $errors),
            OneLoginError::METADATA_SP_INVALID
        );
    }

    /**
     * Get the last error reason from \OneLogin_Saml2_Auth, useful for error debugging.
     *
     * @see \OneLogin_Saml2_Auth::getLastErrorReason()
     *
     * @return string
     */
    public function getLastErrorReason()
    {
        return $this->base->getLastErrorReason();
    }

    /**
     * Get the base authentication handler.
     *
     * @return OneLoginAuth
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * Set a tenant
     *
     * @param Tenant $tenant
     *
     * @return void
     */
    public function setTenant(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * Get a resolved tenant.
     *
     * @return Tenant|null
     */
    public function getTenant()
    {
        return $this->tenant;
    }

    /**
     * Redirects the user to the url past by parameter
     * or to the url that we defined in our SSO Request.
     *
     * @param string $url        The target URL to redirect the user.
     * @param array  $parameters Extra parameters to be passed as part of the url
     * @param bool   $stay       True if we want to stay (returns the url string) False to redirect
     *
     * @return string|null
     */
    public function redirectTo($url = '', array $parameters = array(), $stay = false)
    {
        $this->base->redirectTo($url, $parameters, $stay);
    }
}
