<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Permit;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => 'Jean Dupont',
            'email' => 'jean.dupont@example.com',
            'password' => Hash::make('password'),
            'profile_text' => 'Agent de nettoyage expérimenté avec 3 ans d\'expérience dans le secteur tertiaire. Motivé, ponctuel et appréciant le travail en équipe.',
            'location' => 'Verviers',
        ]);

        // Skills (matching the job competencies)
        $skill1 = Skill::where('code', '16214')->first(); // Nettoyage sols
        $skill2 = Skill::where('type', 'soft')->first(); // Esprit d'équipe

        if ($skill1) $user->skills()->attach($skill1->id, ['level' => 'advanced']);
        if ($skill2) $user->skills()->attach($skill2->id, ['level' => 'expert']);

        // Language
        $lang = Language::where('code', 'FR')->first();
        if ($lang) $user->languages()->attach($lang->id, ['level' => 'B2 - Avancé']);

        // Permit
        $permit = Permit::where('value', 'B')->first();
        if ($permit) $user->permits()->attach($permit->id);
    }
}
