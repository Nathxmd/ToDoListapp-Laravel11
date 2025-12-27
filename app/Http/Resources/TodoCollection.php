<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TodoCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->collection->count(),
                'completed' => $this->collection->where('is_completed', true)->count(),
                'pending' => $this->collection->where('is_completed', false)->count(),
                'overdue' => $this->collection->where('is_overdue', true)->count(),
            ],
        ];
    }
}
