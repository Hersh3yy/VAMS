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
        // Entry types (admin-defined types like "I AM", "Blog Post", etc.)
        Schema::create('entry_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // "I AM", "Blog Post", "Recipe", etc.
            $table->string('slug')->unique(); // "i-am", "blog-post", "recipe"
            $table->text('description')->nullable();
            $table->json('field_config'); // What fields users can fill (title + text for "I AM")
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('slug');
        });

        // Add permissions column to users table
        Schema::table('users', function (Blueprint $table) {
            $table->json('entry_type_permissions')->nullable()->after('api_key'); // Which entry types user can access
        });

        // Entries - user-created content based on entry types
        Schema::create('entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('entry_type_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->json('content'); // Dynamic content based on entry_type field_config
            $table->string('status')->default('draft'); // draft, published
            $table->timestamp('published_at')->nullable();
            $table->integer('order')->default(0); // For drag & drop ordering
            $table->timestamps();

            $table->index(['user_id', 'entry_type_id', 'status']);
            $table->index(['entry_type_id', 'order']);
            $table->index('published_at');
        });

        // Entry images (for entry types that have image fields)
        Schema::create('entry_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('entry_id')->constrained()->onDelete('cascade');
            $table->string('field_name'); // which field this image belongs to
            $table->string('path');
            $table->string('title')->nullable();
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->integer('order')->default(0); // for multiple images in one field
            $table->json('properties')->nullable(); // video info, dimensions, etc.
            $table->timestamps();

            $table->index(['entry_id', 'field_name', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_images');
        Schema::dropIfExists('entries');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('entry_type_permissions');
        });

        Schema::dropIfExists('entry_types');
    }
};
