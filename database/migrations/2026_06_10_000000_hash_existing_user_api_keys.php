<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Hash all existing plain-text API keys in place. Plain-text keys already
     * distributed to integrations keep working because lookups now compare
     * sha256 hashes of the presented key.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('api_key')
            ->orderBy('id')
            ->get(['id', 'api_key'])
            ->each(function (object $user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['api_key' => hash('sha256', $user->api_key)]);
            });
    }

    /**
     * Hashing is one-way; the original keys cannot be restored.
     */
    public function down(): void
    {
        //
    }
};
