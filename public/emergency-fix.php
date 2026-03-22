<?php
/**
 * Emergency fix v2 — standalone PHP script (no Laravel bootstrap).
 * Fixes Syncable.php + resets OPcache.
 * DELETE THIS FILE AFTER USE.
 */
header('Content-Type: text/plain; charset=utf-8');

$baseDir = dirname(__DIR__);
$syncablePath = $baseDir . '/app/Traits/Syncable.php';
$output = [];

// Step 0: Show current state
$output[] = '=== Current Syncable.php ===';
if (file_exists($syncablePath)) {
    $current = file_get_contents($syncablePath);
    if (strpos($current, 'use SoftDeletes;') !== false) {
        $output[] = 'STATUS: BROKEN (contains "use SoftDeletes;")';
    } elseif (strpos($current, 'hasSyncColumns') !== false) {
        $output[] = 'STATUS: ALREADY FIXED (contains "hasSyncColumns")';
    } else {
        $output[] = 'STATUS: UNKNOWN';
    }
    $output[] = 'Size: ' . strlen($current) . ' bytes';
} else {
    $output[] = 'STATUS: FILE NOT FOUND!';
}

// Step 1: Write the fixed file
$output[] = '';
$output[] = '=== Writing Fixed Syncable.php ===';

$fixed = '<?php

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

$written = file_put_contents($syncablePath, $fixed);
$output[] = $written ? "Written {$written} bytes OK" : "WRITE FAILED!";

// Step 2: Reset OPcache
$output[] = '';
$output[] = '=== OPcache Reset ===';
if (function_exists('opcache_invalidate')) {
    opcache_invalidate($syncablePath, true);
    $output[] = 'opcache_invalidate: done';
}
if (function_exists('opcache_reset')) {
    opcache_reset();
    $output[] = 'opcache_reset: done';
}
if (!function_exists('opcache_invalidate') && !function_exists('opcache_reset')) {
    $output[] = 'OPcache not available';
}

// Step 3: Clear Laravel bootstrap caches
$output[] = '';
$output[] = '=== Clear Caches ===';
$cacheFiles = glob($baseDir . '/bootstrap/cache/*.php');
foreach ($cacheFiles as $f) {
    unlink($f);
    $output[] = 'Deleted: bootstrap/cache/' . basename($f);
}
if (empty($cacheFiles)) {
    $output[] = 'No cache files to delete';
}

// Clear compiled views
$viewsDir = $baseDir . '/storage/framework/views';
if (is_dir($viewsDir)) {
    $count = 0;
    foreach (glob($viewsDir . '/*.php') as $v) {
        unlink($v);
        $count++;
    }
    $output[] = "Deleted {$count} compiled views";
}

// Step 4: Verify file was written correctly
$output[] = '';
$output[] = '=== Verification ===';
$reread = file_get_contents($syncablePath);
if (strpos($reread, 'use SoftDeletes;') !== false) {
    $output[] = 'PROBLEM: File still contains "use SoftDeletes;"!';
} elseif (strpos($reread, 'hasSyncColumns') !== false) {
    $output[] = 'OK: File contains "hasSyncColumns" (fixed version)';
} else {
    $output[] = 'UNKNOWN: unexpected content';
}
$output[] = 'File size after write: ' . strlen($reread) . ' bytes';

// Step 5: Try to load Laravel and test
$output[] = '';
$output[] = '=== Laravel Boot Test ===';
try {
    require $baseDir . '/vendor/autoload.php';
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $output[] = 'Laravel booted successfully!';

    // Test DB
    try {
        $userCount = \App\Models\User::count();
        $output[] = "DB test: {$userCount} users found";
    } catch (\Throwable $e) {
        $output[] = 'DB test FAILED: ' . $e->getMessage();
    }
} catch (\Throwable $e) {
    $output[] = 'Laravel boot FAILED: ' . $e->getMessage();
    $output[] = 'File: ' . $e->getFile() . ':' . $e->getLine();
}

echo implode("\n", $output);
