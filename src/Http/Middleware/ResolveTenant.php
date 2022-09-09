<?php

namespace Mkhyman\Saml2\Http\Middleware;

use Mkhyman\Saml2\Models\Tenant;
use Mkhyman\Saml2\Repositories\TenantRepository;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ResolveTenant
 *
 * @package Mkhyman\Saml2\Http\Middleware
 */
class ResolveTenant {
    protected string $resolveBy;

    /**
     * @var TenantRepository
     */
    protected $tenants;

    /**
     * ResolveTenant constructor.
     *
     * @param TenantRepository $tenants
     */
    public function __construct(TenantRepository $tenants) {
        $resolveBy = config('saml2.routeIdpIdentifier');
        if (!in_array($resolveBy, ['id', 'key', 'uuid'])) {
            throw new \Exception('Invalid route idp check.');
        }

        $this->resolveBy = $resolveBy;
        $this->tenants = $tenants;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @throws NotFoundHttpException
     *
     * @return mixed
     */
    public function handle($request, \Closure $next) {
        if(!$tenant = $this->resolveTenant($request)) {
            throw new NotFoundHttpException();
        }

        if (config('saml2.debug')) {
            Log::debug('[Saml2] Tenant resolved', [
                'uuid' => $tenant->uuid,
                'id' => $tenant->id,
                'key' => $tenant->key
            ]);
        }

        session()->flash('saml2.tenant.id', $tenant->id);
        session()->flash('saml2.tenant.uuid', $tenant->uuid);
        session()->flash('saml2.tenant.key', $tenant->key);

        return $next($request);
    }

    /**
     * Resolve a tenant by a request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Mkhyman\Saml2\Models\Tenant|null
     */
    protected function resolveTenant($request) : ?Tenant {
        if(!$idp = $request->route('idp')) {
            if (config('saml2.debug')) {
                Log::debug('[Saml2] Tenant \'idp\' is not present in the URL so cannot be resolved.', [
                    'url' => $request->fullUrl()
                ]);
            }

            return null;
        }

        if(!$tenant = $this->tenants->findBy($idp, $this->resolveBy)) {
            if (config('saml2.debug')) {
                Log::debug('[Saml2] Tenant doesn\'t exist', [
                    $idp => $this->resolveBy
                ]);
            }

            return null;
        }

        if($tenant->trashed()) {
            if (config('saml2.debug')) {
                Log::debug('[Saml2] Tenant #' . $tenant->id. ' resolved but marked as deleted', [
                    'id' => $tenant->id,
                    'key' => $tenant->key,
                    'uuid' => $tenant->uuid,
                    'deleted_at' => $tenant->deleted_at->toDateTimeString()
                ]);
            }

            return null;
        }

        return $tenant;
    }
}