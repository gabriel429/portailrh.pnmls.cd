<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait OptimizedQueries
{
    /**
     * Scope for optimized agent queries with commonly needed relationships
     */
    public function scopeWithCommonRelations(Builder $query): Builder
    {
        return $query->with([
            'user:id,name,email',
            'department:id,nom,code',
            'grade:id,nom,niveau',
            'fonction:id,nom',
            'province:id,nom',
        ]);
    }

    /**
     * Scope for searching agents
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('nom', 'LIKE', "%{$search}%")
              ->orWhere('prenom', 'LIKE', "%{$search}%")
              ->orWhere('postnom', 'LIKE', "%{$search}%")
              ->orWhere('matricule', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('telephone', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        if (!$status) {
            return $query;
        }

        return $query->where('statut', $status);
    }

    /**
     * Scope for filtering by department
     */
    public function scopeByDepartment(Builder $query, ?int $departmentId): Builder
    {
        if (!$departmentId) {
            return $query;
        }

        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope for filtering by province
     */
    public function scopeByProvince(Builder $query, ?int $provinceId): Builder
    {
        if (!$provinceId) {
            return $query;
        }

        return $query->where('province_id', $provinceId);
    }

    /**
     * Scope for active agents only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('statut', 'Actif');
    }

    /**
     * Scope for paginated results with search and filters
     */
    public function scopePaginateWithFilters(Builder $query, array $filters = [], int $perPage = 15)
    {
        return $query
            ->search($filters['search'] ?? null)
            ->byStatus($filters['status'] ?? null)
            ->byDepartment($filters['department_id'] ?? null)
            ->byProvince($filters['province_id'] ?? null)
            ->withCommonRelations()
            ->latest()
            ->paginate($perPage);
    }
}