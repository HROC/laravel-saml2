<?php

namespace Hroc\Saml2\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Hroc\Saml2\Models\Tenant;

/**
 * Class TenantRepository
 *
 * @package Mkhyman\Saml2\Repositories
 */
class TenantRepository
{
    /**
     * Create a new query.
     *
     * @param bool $withTrashed Whether need to include safely deleted records.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(bool $withTrashed = false)
    {
        $query = Tenant::query();

        if($withTrashed) {
            $query->withTrashed();
        }

        return $query;
    }

    /**
     * Find all tenants.
     *
     * @param bool $withTrashed Whether need to include safely deleted records.
     *
     * @return Tenant[]|\Illuminate\Database\Eloquent\Collection
     */
    public function all(bool $withTrashed = true) : Collection {
        return $this->query($withTrashed)->get();
    }

    public function findBy(string $value, string $check='uuid', bool $withTrashed = true) : ?Tenant {
        $allowedChecks = ['id', 'uuid', 'key'];
        if (!in_array($check, $allowedChecks)) {
            throw new \Exception("Attempted to find Saml tenant using invalid field. ($check) is not allowed.");
        }
        
        return $this->query($withTrashed)
            ->where($check, $value)
            ->first();
    }

    /**
     * Find a tenant by any identifier.
     *
     * @param int|string $key ID, key or UUID
     * @param bool $withTrashed Whether need to include safely deleted records.
     *
     * @return Tenant[]|\Illuminate\Database\Eloquent\Collection
     */
    public function matchAnyIdentifier($key, bool $withTrashed = true) : Collection {
        return $this->query($withTrashed)
            ->where('id', $key)
            ->orWhere('key', $key)
            ->orWhere('uuid', $key)
            ->get();
    }

    /**
     * Find a tenant by the key.
     *
     * @param string $key
     * @param bool $withTrashed
     *
     * @return Tenant|\Illuminate\Database\Eloquent\Model|null
     */
    public function findByKey(string $key, bool $withTrashed = true) : ?Tenant {
        return $this->query($withTrashed)
            ->where('key', $key)
            ->first();
    }

    /**
     * Find a tenant by ID.
     *
     * @param int $id
     * @param bool $withTrashed
     *
     * @return Tenant|\Illuminate\Database\Eloquent\Model|null
     */
    public function findById(int $id, bool $withTrashed = true) : ?Tenant {
        return $this->query($withTrashed)
            ->where('id', $id)
            ->first();
    }

    /**
     * Find a tenant by UUID.
     *
     * @param int $uuid
     * @param bool $withTrashed
     *
     * @return Tenant|\Illuminate\Database\Eloquent\Model|null
     */
    public function findByUUID(string $uuid, bool $withTrashed = true) : ?Tenant {
        return $this->query($withTrashed)
            ->where('uuid', $uuid)
            ->first();
    }
}