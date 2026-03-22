<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Syncable Trait
 *
 * Provides sync capabilities to Eloquent models.
 * Safe to use even before the sync migration has been run —
 * all sync features are no-ops when the columns don't exist yet.
 */
trait Syncable
{
    /**
     * Cache of which tables have sync columns.
     */
    protected static array $syncColumnsCache = [];

    /**
     * Check if sync columns exist on this model's table.
     */
    public static function hasSyncColumns(): bool
    {
        $table = (new static)->getTable();

        if (!isset(static::$syncColumnsCache[$table])) {
            try {
                static::$syncColumnsCache[$table] = Schema::hasColumn($table, 'uuid');
            } catch (\Throwable) {
                static::$syncColumnsCache[$table] = false;
            }
        }

        return static::$syncColumnsCache[$table];
    }

    /**
     * Boot the Syncable trait.
     */
    public static function bootSyncable(): void
    {
        // Only register sync hooks and soft-delete scope if columns exist
        if (!static::hasSyncColumns()) {
            return;
        }

        // Register SoftDeletes global scope
        static::addGlobalScope(new SoftDeletingScope);

        // Auto-generate UUID on creating
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        // Mark as dirty on updating (unless we're syncing)
        static::updating(function ($model) {
            if (!$model->isSyncing()) {
                $model->is_dirty = true;
            }
        });
    }

    /**
     * Initialize the trait (runs on every model instantiation).
     */
    public function initializeSyncable(): void
    {
        if (!static::hasSyncColumns()) {
            return;
        }

        $this->mergeFillable(['uuid', 'synced_at', 'is_dirty']);

        $this->mergeCasts([
            'synced_at' => 'datetime',
            'is_dirty' => 'boolean',
            'deleted_at' => 'datetime',
        ]);
    }

    /**
     * Flag to prevent marking as dirty during sync operations.
     */
    protected bool $syncing = false;

    public function setSyncing(bool $syncing = true): static
    {
        $this->syncing = $syncing;
        return $this;
    }

    public function isSyncing(): bool
    {
        return $this->syncing;
    }

    /**
     * Mark the record as synced.
     */
    public function markSynced(): static
    {
        if (!static::hasSyncColumns()) {
            return $this;
        }

        $this->syncing = true;
        $this->update([
            'is_dirty' => false,
            'synced_at' => now(),
        ]);
        $this->syncing = false;
        return $this;
    }

    /**
     * Mark the record as dirty (needs sync).
     */
    public function markDirty(): static
    {
        if (!static::hasSyncColumns()) {
            return $this;
        }

        $this->syncing = true;
        $this->update(['is_dirty' => true]);
        $this->syncing = false;
        return $this;
    }

    /**
     * Scope: records that need to be pushed to server.
     */
    public function scopeDirty($query)
    {
        if (!static::hasSyncColumns()) {
            return $query;
        }
        return $query->where('is_dirty', true);
    }

    /**
     * Scope: records not yet synced.
     */
    public function scopeUnsynced($query)
    {
        if (!static::hasSyncColumns()) {
            return $query;
        }
        return $query->whereNull('synced_at');
    }

    /**
     * Scope: records synced before a given date.
     */
    public function scopeSyncedBefore($query, $date)
    {
        if (!static::hasSyncColumns()) {
            return $query;
        }
        return $query->where('synced_at', '<', $date);
    }

    /**
     * Scope: records updated after a given date (for pull).
     */
    public function scopeUpdatedSince($query, $date)
    {
        return $query->where('updated_at', '>', $date);
    }

    /**
     * Find a record by UUID.
     */
    public static function findByUuid(string $uuid)
    {
        if (!static::hasSyncColumns()) {
            return null;
        }
        return static::where('uuid', $uuid)->first();
    }

    /**
     * Find a record by UUID or fail.
     */
    public static function findByUuidOrFail(string $uuid)
    {
        return static::where('uuid', $uuid)->firstOrFail();
    }

    /**
     * Get the sync-safe attributes (exclude internal fields).
     */
    public function toSyncArray(): array
    {
        $data = $this->toArray();
        unset($data['is_dirty'], $data['syncing']);
        return $data;
    }

    /**
     * SoftDeletes compatibility — these methods allow the model to act
     * as if it has SoftDeletes when columns exist, without using the trait.
     */
    public function trashed(): bool
    {
        if (!static::hasSyncColumns()) {
            return false;
        }
        return !is_null($this->{$this->getDeletedAtColumn()});
    }

    public function restore(): bool
    {
        if (!static::hasSyncColumns()) {
            return false;
        }

        $this->{$this->getDeletedAtColumn()} = null;
        $this->exists = true;

        $result = $this->save();

        return $result;
    }

    public function forceDelete(): ?bool
    {
        return $this->newQueryWithoutScopes()
            ->where($this->getKeyName(), $this->getKey())
            ->forceDelete();
    }

    public function getDeletedAtColumn(): string
    {
        return defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }

    public function getQualifiedDeletedAtColumn(): string
    {
        return $this->qualifyColumn($this->getDeletedAtColumn());
    }
}
