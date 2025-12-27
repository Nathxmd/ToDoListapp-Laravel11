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
        Schema::table('users', function (Blueprint $table) {
            // Add new columns for user profile and preferences
            $table->string('avatar')->nullable()->after('name');
            $table->boolean('email_notifications')->default(true)->after('email_verified_at');
            $table->string('timezone')->default('Asia/Jakarta')->after('email_notifications');
            $table->string('theme_color', 7)->default('#3B82F6')->after('timezone'); // Accent color for UI
            $table->enum('font_size', ['small', 'medium', 'large'])->default('medium')->after('theme_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar',
                'email_notifications',
                'timezone',
                'theme_color',
                'font_size'
            ]);
        });
    }
};
