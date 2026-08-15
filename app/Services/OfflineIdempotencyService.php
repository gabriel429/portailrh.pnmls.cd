<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Shared idempotency guard for endpoints the offline sync queue can replay
 * (see resources/js/api/client.js OFFLINE_QUEUEABLE_CREATIONS and
 * resources/js/services/syncService.js::syncQueuedOperation). A queued
 * operation resends the same client_operation_id on every retry; without a
 * server-side guard, a retry after a lost response (or a genuine race
 * between two retries) creates a duplicate record. Backed by the same
 * offline_sync_operations table already used for pointage idempotence.
 */
class OfflineIdempotencyService
{
    /**
     * Run $create() at most once per (user, client_operation_id).
     *
     * @param  callable(): array  $create  Must return a plain, JSON-serializable
     *                                     array — it becomes both the stored
     *                                     replay payload and the returned result.
     * @return array{duplicate: bool, result: array}
     */
    public function once(int $userId, ?string $clientOperationId, string $entity, string $operation, callable $create): array
    {
        if (!$clientOperationId) {
            return ['duplicate' => false, 'result' => $create()];
        }

        try {
            return DB::transaction(function () use ($userId, $clientOperationId, $entity, $operation, $create) {
                $existing = DB::table('offline_sync_operations')
                    ->where('user_id', $userId)
                    ->where('client_operation_id', $clientOperationId)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return ['duplicate' => true, 'result' => json_decode($existing->result, true)];
                }

                $result = $create();

                DB::table('offline_sync_operations')->insert([
                    'user_id' => $userId,
                    'client_operation_id' => $clientOperationId,
                    'entity' => $entity,
                    'operation' => $operation,
                    'status' => 'accepted',
                    'result' => json_encode($result),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['duplicate' => false, 'result' => $result];
            }, 3);
        } catch (QueryException $exception) {
            if (!$this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            // Two near-simultaneous retries raced past the initial check;
            // the unique (user_id, client_operation_id) constraint caught it.
            $existing = DB::table('offline_sync_operations')
                ->where('user_id', $userId)
                ->where('client_operation_id', $clientOperationId)
                ->first();

            if (!$existing) {
                throw $exception;
            }

            return ['duplicate' => true, 'result' => json_decode($existing->result, true)];
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array($exception->getCode(), ['23000', '23505'], true);
    }
}
