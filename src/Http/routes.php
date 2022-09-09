<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('saml2.routesPrefix'),
    'middleware' => array_merge(['saml2.resolveTenant'], config('saml2.routesMiddleware')),
], function () {
    Route::get('/{key}/logout', array(
        'as' => 'saml.logout',
        'uses' => 'HROC\Saml2\Http\Controllers\Saml2Controller@logout',
    ));

    Route::get('/{key}/login', array(
        'as' => 'saml.login',
        'uses' => 'HROC\Saml2\Http\Controllers\Saml2Controller@login',
    ));

    Route::get('/{key}/metadata', array(
        'as' => 'saml.metadata',
        'uses' => 'HROC\Saml2\Http\Controllers\Saml2Controller@metadata',
    ));

    Route::post('/{key}/acs', array(
        'as' => 'saml.acs',
        'uses' => 'HROC\Saml2\Http\Controllers\Saml2Controller@acs',
    ));

    Route::get('/{key}/sls', array(
        'as' => 'saml.sls',
        'uses' => 'HROC\Saml2\Http\Controllers\Saml2Controller@sls',
    ));
});
