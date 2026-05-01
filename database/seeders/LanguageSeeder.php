<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            ['label' => 'Français', 'code' => 'FR', 'slug' => 'francais'],
            ['label' => 'Anglais', 'code' => 'EN', 'slug' => 'anglais'],
            ['label' => 'Néerlandais', 'code' => 'NL', 'slug' => 'neerlandais'],
            ['label' => 'Allemand', 'code' => 'DE', 'slug' => 'allemand'],
            ['label' => 'Espagnol', 'code' => 'ES', 'slug' => 'espagnol'],
            ['label' => 'Italien', 'code' => 'IT', 'slug' => 'italien'],
            ['label' => 'Arabe', 'code' => 'AR', 'slug' => 'arabe'],
            ['label' => 'Chinois', 'code' => 'ZH', 'slug' => 'chinois'],
            ['label' => 'Japonais', 'code' => 'JA', 'slug' => 'japonais'],
            ['label' => 'Russe', 'code' => 'RU', 'slug' => 'russe'],
        ];

        foreach ($languages as $lang) {
            \App\Models\Language::updateOrCreate(
                ['slug' => $lang['slug']],
                ['label' => $lang['label'], 'code' => $lang['code']]
            );
        }
    }
}
