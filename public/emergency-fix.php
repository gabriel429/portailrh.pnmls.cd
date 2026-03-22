<?php
/**
 * Emergency fix — standalone PHP script (no Laravel bootstrap).
 * Writes the corrected Syncable.php directly to disk.
 * DELETE THIS FILE AFTER USE.
 */

$baseDir = dirname(__DIR__);
$syncablePath = $baseDir . '/app/Traits/Syncable.php';

$output = [];
$output[] = '=== Emergency Fix: Syncable.php ===';
$output[] = 'Base dir: ' . $baseDir;
$output[] = 'Target: ' . $syncablePath;
$output[] = '';

$fixedContent = '<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\SoftDeletingScope;

trait Syncable
{
    protected static array $syncColumnsCache = [];

    public static function hasSyncColumns(): bool
    {
        $table = (new static)->getTable();
        if (!isset(static::$syncColumnsCache[$table])) {
            try {
                static::$syncColumnsCache[$table] = Schema::hasColumn($table, \'uuid\');
            } catch (\Throwable) {
                static::$syncColumnsCache[$table] = false;
            }
        }
        return static::$syncColumnsCache[$table];
    }

    public static function bootSyncable(): void
    {
        if (!static::hasSyncColumns()) {
            return;
        }
        static::addGlobalScope(new SoftDeletingScope);
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
        static::updating(function ($model) {
            if (!$model->isSyncing()) {
                $model->is_dirty = true;
            }
        });
    }

    public function initializeSyncable(): void
    {
        if (!static::hasSyncColumns()) {
            return;
        }
        $this->mergeFillable([\'uuid\', \'synced_at\', \'is_dirty\']);
        $this->mergeCasts([
            \'synced_at\' => \'datetime\',
            \'is_dirty\' => \'boolean\',
            \'deleted_at\' => \'datetime\',
        ]);
    }

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

    public function markSynced(): static
    {
        if (!static::hasSyncColumns()) { return $this; }
        $this->syncing = true;
        $this->update([\'is_dirty\' => false, \'synced_at\' => now()]);
        $this->syncing = false;
        return $this;
    }

    public function markDirty(): static
    {
        if (!static::hasSyncColumns()) { return $this; }
        $this->syncing = true;
        $this->update([\'is_dirty\' => true]);
        $this->syncing = false;
        return $this;
    }

    public function scopeDirty($query)
    {
        return static::hasSyncColumns() ? $query->where(\'is_dirty\', true) : $query;
    }

    public function scopeUnsynced($query)
    {
        return static::hasSyncColumns() ? $query->whereNull(\'synced_at\') : $query;
    }

    public function scopeSyncedBefore($query, $date)
    {
        return static::hasSyncColumns() ? $query->where(\'synced_at\', \'<\', $date) : $query;
    }

    public function scopeUpdatedSince($query, $date)
    {
        return $query->where(\'updated_at\', \'>\', $date);
    }

    public static function findByUuid(string $uuid)
    {
        return static::hasSyncColumns() ? static::where(\'uuid\', $uuid)->first() : null;
    }

    public static function findByUuidOrFail(string $uuid)
    {
        return static::where(\'uuid\', $uuid)->firstOrFail();
    }

    public function toSyncArray(): array
    {
        $data = $this->toArray();
        unset($data[\'is_dirty\'], $data[\'syncing\']);
        return $data;
    }

    public function trashed(): bool
    {
        if (!static::hasSyncColumns()) { return false; }
        return !is_null($this->{$this->getDeletedAtColumn()});
    }

    public function restore(): bool
    {
        if (!static::hasSyncColumns()) { return false; }
        $this->{$this->getDeletedAtColumn()} = null;
        $this->exists = true;
        return $this->save();
    }

    public function forceDelete(): ?bool
    {
        return $this->newQueryWithoutScopes()
            ->where($this->getKeyName(), $this->getKey())
            ->forceDelete();
    }

    public function getDeletedAtColumn(): string
    {
        return defined(\'static::DELETED_AT\') ? static::DELETED_AT : \'deleted_at\';
    }

    public function getQualifiedDeletedAtColumn(): string
    {
        return $this->qualifyColumn($this->getDeletedAtColumn());
    }
}
';

// Write the file
$written = file_put_contents($syncablePath, $fixedContent);

if ($written) {
    $output[] = "SUCCESS: Written {$written} bytes";

    // Also clear cached files
    $cacheDirs = [
        $baseDir . '/bootstrap/cache/config.php',
        $baseDir . '/bootstrap/cache/routes-v7.php',
        $baseDir . '/bootstrap/cache/services.php',
    ];
    foreach ($cacheDirs as $cacheFile) {
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            $output[] = "Deleted cache: " . basename($cacheFile);
        }
    }

    // Clear compiled views
    $viewsDir = $baseDir . '/storage/framework/views';
    if (is_dir($viewsDir)) {
        $count = 0;
        foreach (glob($viewsDir . '/*.php') as $view) {
            unlink($view);
            $count++;
        }
        $output[] = "Deleted {$count} compiled views";
    }

    $output[] = '';
    $output[] = 'FIX APPLIED! Try logging in now.';
    $output[] = 'DELETE this file (public/emergency-fix.php) when done.';
} else {
    $output[] = "FAILED to write file!";
    $output[] = "Dir writable: " . (is_writable(dirname($syncablePath)) ? 'yes' : 'no');
    $output[] = "File exists: " . (file_exists($syncablePath) ? 'yes' : 'no');
    if (file_exists($syncablePath)) {
        $output[] = "File writable: " . (is_writable($syncablePath) ? 'yes' : 'no');
    }
}

header('Content-Type: text/plain; charset=utf-8');
echo implode("\n", $output);
