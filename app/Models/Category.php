<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'user_id',
    ];

    /**
     * Get the user that owns the category.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all todos for the category.
     */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    /**
     * Get the count of todos in this category.
     */
    public function getTodosCountAttribute(): int
    {
        return $this->todos()->count();
    }

    /**
     * Get the count of completed todos in this category.
     */
    public function getCompletedTodosCountAttribute(): int
    {
        return $this->todos()->where('is_completed', true)->count();
    }

    /**
     * Get the count of pending todos in this category.
     */
    public function getPendingTodosCountAttribute(): int
    {
        return $this->todos()->where('is_completed', false)->count();
    }
}
