<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CleanTaxonomiesCommand extends Command
{
    protected $signature = 'app:clean-taxonomies';
    protected $description = 'Clean and merge duplicate taxonomies (languages, skills, etc.) using slugs';

    public function handle()
    {
        $this->cleanTable('languages', \App\Models\Language::class, [
            'user_language' => 'language_id', 
            'job_offer_language' => 'language_id'
        ]);
        
        $this->cleanTable('skills', \App\Models\Skill::class, [
            'user_skill' => 'skill_id', 
            'job_offer_skill' => 'skill_id'
        ]);

        $this->cleanTable('permits', \App\Models\Permit::class, [
            'user_permit' => 'permit_id', 
            'job_offer_permit' => 'permit_id'
        ]);

        $this->cleanTable('sectors', \App\Models\Sector::class, [
            'job_offer_sector' => 'sector_id'
        ]);

        $this->cleanTable('studies', \App\Models\Study::class, [
            'job_offer_study' => 'study_id'
        ]);
        
        $this->info('Cleanup completed!');
    }

    protected function cleanTable($tableName, $modelClass, $relationships)
    {
        $this->info("Cleaning table: $tableName...");
        
        // 1. Generate slugs for everything
        $items = $modelClass::all();
        foreach ($items as $item) {
            $slug = ($tableName === 'permits') 
                ? \App\Models\Permit::generateSlug($item->label) 
                : Str::slug($item->label);

            $item->slug = $slug;
            try {
                $item->save();
            } catch (\Exception $e) {
                // Conflict will be handled by merging
            }
        }

        // 2. Identify duplicates by slug
        $duplicates = DB::table($tableName)
            ->select('slug', DB::raw('count(*) as count'))
            ->whereNotNull('slug')
            ->groupBy('slug')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $this->warn("Merging duplicates for slug: {$dup->slug}");
            
            // For permits, we prefer the shorter label or the one starting with the code
            $allItems = $modelClass::where('slug', $dup->slug)->get()->sortBy(function($item) {
                return strlen($item->label);
            });
            
            $master = $allItems->shift(); 

            foreach ($allItems as $duplicate) {
                $this->line("  -> Merging [{$duplicate->label}] into [{$master->label}]");
                
                foreach ($relationships as $pivotTable => $foreignKey) {
                    $pivotRows = DB::table($pivotTable)
                        ->where($foreignKey, $duplicate->id)
                        ->get();

                    foreach ($pivotRows as $row) {
                        try {
                            // On essaie de mettre à jour le lien vers le master
                            DB::table($pivotTable)
                                ->where($foreignKey, $duplicate->id)
                                ->whereNotExists(function ($query) use ($pivotTable, $foreignKey, $master, $row) {
                                    // Vérification complexe pour éviter les doublons sur les colonnes composites
                                    $q = $query->from($pivotTable)->where($foreignKey, $master->id);
                                    if (isset($row->user_id)) $q->where('user_id', $row->user_id);
                                    if (isset($row->job_offer_id)) $q->where('job_offer_id', $row->job_offer_id);
                                })
                                ->update([$foreignKey => $master->id]);
                        } catch (\Exception $e) {
                            // En cas d'échec, on nettoie simplement le lien orphelin
                        }
                    }
                    // Nettoyage final des liens qui n'ont pas pu être migrés car déjà existants pour le master
                    DB::table($pivotTable)->where($foreignKey, $duplicate->id)->delete();
                }
                
                $duplicate->delete();
            }
        }
    }
}
