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
        // Users table
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('is_admin')->default(false);
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->string('password');
            $table->json('album_display_settings')->nullable();
            $table->string('logo_url')->nullable();
            $table->json('theme_settings')->nullable();
            $table->json('site_settings')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Albums table
        Schema::create('albums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->integer('order')->default(0);
            $table->json('metadata')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Album images table
        Schema::create('album_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('album_id');
            $table->string('path');
            $table->string('title')->nullable();
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->text('author')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->string('location')->nullable();
            $table->string('tags')->nullable();
            $table->json('properties')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->foreign('album_id')->references('id')->on('albums')->onDelete('cascade');
        });

        // Mosaics table
        Schema::create('mosaics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('layout_settings')->nullable(); // Settings for the overall layout
            $table->integer('columns')->default(4); // Default number of columns for the grid
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Mosaic items table
        Schema::create('mosaic_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('mosaic_id');
            $table->uuid('parent_id')->nullable(); // For tracking parent-child relationships for split tiles
            $table->enum('split_direction', ['horizontal', 'vertical', 'none'])->default('none'); // How this tile is split
            $table->enum('type', ['album', 'image', 'video', 'text', 'container'])->default('container'); // Type of content
            $table->uuid('reference_id')->nullable(); // Reference to album, image, etc.
            $table->string('content')->nullable(); // Text content if type is text
            $table->json('properties')->nullable(); // Additional properties
            $table->string('link_url')->nullable(); // Link URL when the tile is clicked
            $table->string('link_target')->nullable(); // Target for the link (_blank, _self, etc.)
            $table->json('desktop_position')->nullable(); // Position and size for desktop
            $table->json('tablet_position')->nullable();  // Position and size for tablet
            $table->json('mobile_position')->nullable();  // Position and size for mobile
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('mosaic_id')->references('id')->on('mosaics')->onDelete('cascade');
        });
        
        // Add parent_id foreign key after the table is created
        Schema::table('mosaic_items', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('mosaic_items')->onDelete('cascade');
        });

        // Jobs table for queues
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        // Job batches table
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        // Sessions table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Password reset tokens table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Failed jobs table
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // Personal access tokens table
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Cache table
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('mosaic_items');
        Schema::dropIfExists('mosaics');
        Schema::dropIfExists('album_images');
        Schema::dropIfExists('albums');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('personal_access_tokens');
    }
}; 