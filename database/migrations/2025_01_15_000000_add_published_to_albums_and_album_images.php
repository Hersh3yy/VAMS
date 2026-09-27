<?php

declare(strict_types=1);

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
        Schema::table('albums', function (Blueprint $table): void {
            $table->boolean('published')->default(true)->after('order');
        });

        Schema::table('album_images', function (Blueprint $table): void {
            $table->boolean('published')->default(true)->after('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('albums', function (Blueprint $table): void {
            $table->dropColumn('published');
        });

        Schema::table('album_images', function (Blueprint $table): void {
            $table->dropColumn('published');
        });
    }
};
