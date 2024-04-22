<?php
declare(strict_types=1);
namespace Hroc\Saml2\Events;

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
	 * The generated saml request id.
	 *
	 * @var string
	 */
	public $authNRequestId;

	/**
	 * The redirect url.
	 *
	 * @var string
	 */
	public $redirectUrl;

	/**
	 * LoggedIn constructor.
	 *
	 * @param Saml2User $user
	 * @param Auth $auth
	 */
	public function __construct(string $authNRequestId, string $redirectUrl)
	{
		$this->authNRequestId = $authNRequestId;
		$this->redirectUrl = $redirectUrl;
	}
}
