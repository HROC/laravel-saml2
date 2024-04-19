<?php
declare(strict_types=1);
namespace Hroc\Saml2\Http\Controllers;

use Illuminate\Support\Facades\Auth as LaravelAuth;
use Hroc\Saml2\Events\SignedIn;
use Hroc\Saml2\Auth;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use OneLogin\Saml2\Error as OneLoginError;

/**
 * Class Saml2Controller
 *
 * @package Hroc\Saml2\Http\Controllers
 */
class Saml2Controller extends Controller
{
    /**
     * Render the metadata.
     *
     * @param Auth $auth
     *
     * @return \Illuminate\Support\Facades\Response
     *
     * @throws OneLoginError
     */
    public function metadata(Auth $auth)
    {
        $metadata = $auth->getMetadata();

        return response($metadata, 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Process the SAML Response sent by the IdP.
     *
     * Fires "SignedIn" event if a valid user is found.
     *
     * @param Auth $auth
     *
     * @return \Illuminate\Support\Facades\Redirect
     *
     * @throws OneLoginError
     * @throws \OneLogin\Saml2\ValidationError
     */
    public function acs(Auth $auth)
    {
        $errors = $auth->acs();

        if (!empty($errors)) {
            logger()->error('saml2.error_detail', ['error' => $auth->getLastErrorReason()]);
            session()->flash('saml2.error_detail', [$auth->getLastErrorReason()]);

            logger()->error('saml2.error', $errors);
            session()->flash('saml2.error', $errors);

            return redirect(config('saml2.errorRoute'));
        }

        $user = $auth->getSaml2User();

        event(new SignedIn($user, $auth));

        $redirectUrl = $user->getIntendedUrl();

        if ($redirectUrl) {
            return redirect($redirectUrl);
        }

        return redirect($auth->getTenant()->relay_state_url ?: config('saml2.loginRoute'));
    }

    /**
     * Process the SAML Logout Response / Logout Request sent by the IdP.
     *
     * Fires 'saml2.logoutRequestReceived' event if its valid.
     *
     * This means the user logged out of the SSO infrastructure, you 'should' log him out locally too.
     *
     * @param Auth $auth
     *
     * @return \Illuminate\Support\Facades\Redirect
     *
     * @throws OneLoginError
     * @throws \Exception
     */
    public function sls(Auth $auth)
    {
        $error = $auth->sls(config('saml2.retrieveParametersFromServer'));

        if (!empty($error)) {
            throw new \Exception("Could not log out");
        }

        return redirect(config('saml2.logoutRoute')); //may be set a configurable default
    }

    /**
     * Initiate a login request.
     *
     * @param \Illuminate\Http\Request $request
     * @param Auth $auth
     *
     * @return void
     *
     * @throws OneLoginError
     */
    public function login(Request $request, Auth $auth)
    {
        $redirectUrl = $auth->getTenant()->relay_state_url ?: config('saml2.loginRoute');
        $stay = true;            // we need to disable the auto redirect so we can record the request id

        // setup login request and store slo url
        $sloUrl = $auth->login($request->query('returnTo', $redirectUrl), [], false, false, $stay);

        // record the request id in the session so we can use it later when we receive the response
        Session::put('sso.AuthNRequestID', $auth->getLastRequestID());

        // now we manually redirect since we stopped auto redirection earlier
        $auth->redirectTo($sloUrl);
    }

    /**
     * Initiate a logout request.
     *
     * @param \Illuminate\Http\Request $request
     * @param Auth $auth
     *
     * @return void
     *
     * @throws OneLoginError
     */
    public function logout(Request $request, Auth $auth)
    {
        $laravelAuthUser = LaravelAuth::user();

        // work out nameId
        $nameId = null;
        if ($laravelAuthUser) {
            // use property set in config as nameId
            $nameId = $laravelAuthUser->{config('saml2.sp.singleLogoutService.nameIdUserProperty')};
        }

        $auth->logout(
            $request->query('returnTo'),
            $nameId, //$request->query('nameId'),
            $request->query('sessionIndex')
        );
    }
}
