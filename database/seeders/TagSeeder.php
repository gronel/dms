<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    private array $systemTags = [
        ['name' => 'Confidential', 'color' => '#ef4444'],
        ['name' => 'Legal',        'color' => '#8b5cf6'],
        ['name' => 'Financial',    'color' => '#f59e0b'],
        ['name' => 'HR',           'color' => '#10b981'],
        ['name' => 'Draft',        'color' => '#6b7280'],
    ];

    public function run(): void
    {
        foreach ($this->systemTags as $tag) {
            Tag::firstOrCreate(
                ['slug' => Str::slug($tag['name'])],
                [
                    'name'      => $tag['name'],
                    'color'     => $tag['color'],
                    'is_system' => true,
                ]
            );
        }
    }
}
