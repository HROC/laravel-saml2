<?php
declare(strict_types=1);
namespace Hroc\Saml2\Events;

use Hroc\Saml2\Models\Saml2LoginRequest;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class LoggedIn
 *
 * @package Hroc\Saml2\Events
 */
class IdpLogin
{
	use Dispatchable;

	/**
	 * The login request model
	 *
	 * @var Saml2LoginRequest
	 */
	public Saml2LoginRequest $saml2LoginRequest;

	/**
	 * LoggedIn constructor.
	 *
	 * @param Saml2User $user
	 * @param Auth $auth
	 */
	public function __construct(Saml2LoginRequest $saml2LoginRequest)
	{
		$this->saml2LoginRequest = $saml2LoginRequest;
	}
}
