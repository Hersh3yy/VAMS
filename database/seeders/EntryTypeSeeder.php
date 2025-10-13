<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EntryType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EntryTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create "I AM" entry type
        EntryType::create([
            'id' => Str::uuid(),
            'name' => 'I AM',
            'slug' => 'i-am',
            'description' => 'Personal affirmations and identity statements',
            'field_config' => [
                [
                    'name' => 'statement',
                    'type' => 'textarea',
                    'label' => 'I AM...',
                    'required' => true,
                    'placeholder' => 'I AM...',
                    'rows' => 6,
                ],
            ],
            'is_active' => true,
        ]);

        $this->command->info('Created "I AM" entry type');
    }
}
