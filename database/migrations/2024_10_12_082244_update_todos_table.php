<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            // Rename existing columns
            $table->renameColumn('task', 'title');
            $table->renameColumn('is_done', 'is_completed');
        });

        Schema::table('todos', function (Blueprint $table) {
            // Add new columns
            $table->text('description')->nullable()->after('title');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium')->after('description');
            $table->timestamp('due_date')->nullable()->after('priority');
            $table->boolean('is_overdue')->default(false)->after('is_completed');
            $table->unsignedBigInteger('category_id')->nullable()->after('is_overdue');
            $table->unsignedBigInteger('user_id')->after('category_id');
            $table->softDeletes()->after('user_id');

            // Foreign key constraints
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes for better query performance
            $table->index('is_completed');
            $table->index('is_overdue');
            $table->index('priority');
            $table->index('due_date');
            $table->index(['user_id', 'is_completed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['category_id']);
            $table->dropForeign(['user_id']);
            
            // Drop indexes
            $table->dropIndex(['is_completed']);
            $table->dropIndex(['is_overdue']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['due_date']);
            $table->dropIndex(['user_id', 'is_completed']);
            
            // Drop new columns
            $table->dropColumn([
                'description',
                'priority',
                'due_date',
                'is_overdue',
                'category_id',
                'user_id',
                'deleted_at'
            ]);
        });

        Schema::table('todos', function (Blueprint $table) {
            // Rename back to original
            $table->renameColumn('title', 'task');
            $table->renameColumn('is_completed', 'is_done');
        });
    }
};
