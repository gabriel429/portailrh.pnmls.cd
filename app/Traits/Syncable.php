<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Syncable Trait
 *
 * Provides sync capabilities to Eloquent models:
 * - Auto-generates UUID on creation
 * - Marks records as dirty on update
 * - Provides scopes for querying sync state
 * - Adds soft delete support
 */
trait Syncable
{
    use SoftDeletes;

    /**
     * Boot the Syncable trait.
     */
    public static function bootSyncable(): void
    {
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
        // Ensure these columns are in fillable
        $this->mergeFillable(['uuid', 'synced_at', 'is_dirty']);

        // Cast types
        $this->mergeCasts([
            'synced_at' => 'datetime',
            'is_dirty' => 'boolean',
        ]);
    }

    /**
     * Flag to prevent marking as dirty during sync operations.
     */
    protected bool $syncing = false;

    /**
     * Set the model into sync mode (prevents marking as dirty).
     */
    public function setSyncing(bool $syncing = true): static
    {
        $this->syncing = $syncing;
        return $this;
    }

    /**
     * Check if the model is currently being synced.
     */
    public function isSyncing(): bool
    {
        return $this->syncing;
    }

    /**
     * Mark the record as synced.
     */
    public function markSynced(): static
    {
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
        return $query->where('is_dirty', true);
    }

    /**
     * Scope: records not yet synced.
     */
    public function scopeUnsynced($query)
    {
        return $query->whereNull('synced_at');
    }

    /**
     * Scope: records synced before a given date.
     */
    public function scopeSyncedBefore($query, $date)
    {
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
}
