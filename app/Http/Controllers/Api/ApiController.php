<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiController extends Controller
{
    protected function success(mixed $data = null, array $meta = [], array $extra = [], int $status = 200): JsonResponse
    {
        return response()->json(array_merge([
            'data' => $data,
            'meta' => $meta,
        ], $extra), $status);
    }

    protected function resource(JsonResource $resource, array $meta = [], array $extra = [], int $status = 200): JsonResponse
    {
        return $this->success($resource->resolve(), $meta, $extra, $status);
    }

    protected function resourceCollection(iterable $items, string $resourceClass, array $meta = [], array $extra = [], int $status = 200): JsonResponse
    {
        $data = $resourceClass::collection(collect($items))->resolve();

        return $this->success($data, $meta, $extra, $status);
    }

    protected function paginated(LengthAwarePaginator $paginator, string $resourceClass, array $meta = [], array $extra = [], int $status = 200): JsonResponse
    {
        $pagination = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];

        return $this->resourceCollection(
            $paginator->getCollection(),
            $resourceClass,
            array_merge($pagination, $meta),
            $extra,
            $status,
        );
    }
}