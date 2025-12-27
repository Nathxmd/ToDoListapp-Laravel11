<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Statistics (when loaded)
            'todos_count' => $this->when($this->relationLoaded('todos'), function () {
                return $this->todos->count();
            }),
            'completed_todos_count' => $this->when($this->relationLoaded('todos'), function () {
                return $this->todos->where('is_completed', true)->count();
            }),
            'pending_todos_count' => $this->when($this->relationLoaded('todos'), function () {
                return $this->todos->where('is_completed', false)->count();
            }),
        ];
    }
}
