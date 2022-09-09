<?php

namespace HROC\Saml2\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Saml2Auth
 *
 * @method static \HROC\Saml2\Models\Tenant|null getTenant()
 *
 * @package HROC\Saml2\Facades
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
        return 'HROC\Saml2\Auth';
    }
}