<?php

namespace Hroc\Saml2\Events;

use Hroc\Saml2\Saml2User;
use Hroc\Saml2\Auth;
use Illuminate\Http\Response;

/**
 * Class LoggedIn
 *
 * @package Hroc\Saml2\Events
 */
class SignedIn
{
    /**
     * The signed-up user.
     *
     * @var Saml2User
     */
    public $user;

    /**
     * The authentication handler.
     *
     * @var Auth
     */
    public $auth;

    /**
     * The ID of the SAML2 login request.
     *
     * @var int
     */
    public int $dbSaml2LoginRequestId;

    /**
     * If a custom response is needed then populate this in a listener
     *
     * @var Response
     */
    public ?Response $response;

    /**
     * LoggedIn constructor.
     *
     * @param Saml2User $user
     * @param Auth $auth
     */
    public function __construct(Saml2User $user, Auth $auth, int $dbSaml2LoginRequestId)
    {
        $this->user = $user;
        $this->auth = $auth;
        $this->dbSaml2LoginRequestId = $dbSaml2LoginRequestId;
        $this->response = null;     // this maybe set by an event listener to override the default response
    }

    /**
     * Get the authentication handler for a SAML sign in attempt
     *
     * @return Auth The authentication handler for the SignedIn event
     */
    public function getAuth(): Auth
    {
        return $this->auth;
    }

    /**
     * Get the user represented in the SAML sign in attempt
     *
     * @return Saml2User The user for the SignedIn event
     */
    public function getSaml2User(): Saml2User
    {
        return $this->user;
    }
}
