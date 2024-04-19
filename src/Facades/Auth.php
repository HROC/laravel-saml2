<?php

namespace Hroc\Saml2\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Saml2Auth
 *
 * @method static \Hroc\Saml2\Models\Tenant|null getTenant()
 *
 * @package Hroc\Saml2\Facades
 */
class Auth extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Hroc\Saml2\Auth';
    }
}