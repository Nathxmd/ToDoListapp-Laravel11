<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'avatar' => $this->avatar,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'email_notifications' => $this->email_notifications,
            'timezone' => $this->timezone,
            'theme_color' => $this->theme_color,
            'font_size' => $this->font_size,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Statistics (optional, only when requested)
            'todos_count' => $this->when($request->has('include_stats'), function () {
                return $this->todos()->count();
            }),
            'completed_todos_count' => $this->when($request->has('include_stats'), function () {
                return $this->todos()->where('is_completed', true)->count();
            }),
            'pending_todos_count' => $this->when($request->has('include_stats'), function () {
                return $this->todos()->where('is_completed', false)->count();
            }),
        ];
    }
}
