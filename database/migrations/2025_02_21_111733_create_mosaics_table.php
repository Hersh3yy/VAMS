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
        Schema::create('landing_mosaics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('theme_settings')->nullable(); // Store color theme, logo path, etc
            $table->timestamps();
        });

        Schema::create('mosaic_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('landing_mosaic_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('album_id')->nullable()->constrained()->nullOnDelete();
            $table->string('image_path')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('link_url')->nullable();
            $table->json('desktop_position')->nullable(); // Store x, y, width, height
            $table->json('mobile_position')->nullable();  // Store mobile-specific layout
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mosaic_items');
        Schema::dropIfExists('landing_mosaics');
    }
};
