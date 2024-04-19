<?php

namespace Hroc\Saml2\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Saml2Auth
 *
 * @method static \Mkhyman\Saml2\Models\Tenant|null getTenant()
 *
 * @package Mkhyman\Saml2\Facades
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
        return 'Mkhyman\Saml2\Auth';
    }
}