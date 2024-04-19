<?php

namespace Hroc\Saml2\Commands;

use Illuminate\Support\Str;

/**
 * Class CreateTenant
 *
 * @package Hroc\Saml2\Commands
 */
trait RendersTenants
{
    /**
     * Render tenants in a table.
     *
     * @param \Hroc\Saml2\Models\Tenant|\Illuminate\Support\Collection $tenants
     * @param string|null $title
     *
     * @return void
     */
    protected function renderTenants($tenants, string $title = null)
    {
        /** @var \Hroc\Saml2\Models\Tenant[]|\Illuminate\Database\Eloquent\Collection $tenants */
        $tenants = $tenants instanceof \Hroc\Saml2\Models\Tenant
            ? collect([$tenants])
            : $tenants;

        $headers = ['Column', 'Value'];
        $columns = [];

        foreach ($tenants as $tenant) {
            foreach ($this->getTenantColumns($tenant) as $column => $value) {
                $columns[] = [$column, $value ?: '(empty)'];
            }

            if($tenants->last()->id !== $tenant->id) {
                $columns[] = new \Symfony\Component\Console\Helper\TableSeparator();
            }
        }

        if($title) {
            $this->getOutput()->title($title);
        }

        $this->table($headers, $columns);
    }

    /**
     * Get a columns of the Tenant.
     *
     * @param \Hroc\Saml2\Models\Tenant $tenant
     *
     * @return array
     */
    protected function getTenantColumns(\Hroc\Saml2\Models\Tenant $tenant)
    {
        return [
            'ID' => $tenant->id,
            'UUID' => $tenant->uuid,
            'Key' => $tenant->key,
            'Entity ID' => $tenant->idp_entity_id,
            'Login URL' => $tenant->idp_login_url,
            'Logout URL' => $tenant->idp_logout_url,
            'Relay State URL' => $tenant->relay_state_url,
            'Name ID format' => $tenant->name_id_format,
            'x509 cert' => Str::limit($tenant->idp_x509_cert, 50),
            'Metadata' => $this->renderArray($tenant->metadata ?: []),
            'Created' => $tenant->created_at->toDateTimeString(),
            'Updated' => $tenant->updated_at->toDateTimeString(),
            'Deleted' => $tenant->deleted_at ? $tenant->deleted_at->toDateTimeString() : null
        ];
    }

    /**
     * Render a tenant credentials.
     *
     * @param \Hroc\Saml2\Models\Tenant $tenant
     *
     * @return void
     */
    protected function renderTenantCredentials(\Hroc\Saml2\Models\Tenant $tenant)
    {
        $this->output->section('Credentials for the tenant');
        $match = config('saml2.routeIdpIdentifier');

        $this->getOutput()->text([
            'Identifier (Entity ID): <comment>' . route('saml.metadata', ['idp' => $tenant->{$match}]) . '</comment>',
            'Reply URL (Assertion Consumer Service URL): <comment>' . route('saml.acs', ['idp' => $tenant->{$match}]) . '</comment>',
            'Sign on URL: <comment>' . route('saml.login', ['idp' => $tenant->{$match}]) . '</comment>',
            'Logout URL: <comment>' . route('saml.logout', ['idp' => $tenant->{$match}]) . '</comment>',
            'Relay State: <comment>' . ($tenant->relay_state_url ?: config('saml2.loginRoute')) . ' (optional)</comment>'
        ]);
    }

    /**
     * Print an array to a string.
     *
     * @param array $array
     *
     * @return string
     */
    protected function renderArray(array $array)
    {
        $lines = [];

        foreach ($array as $key => $value) {
            $lines[] = "$key: $value";
        }

        return implode(PHP_EOL, $lines);
    }
}