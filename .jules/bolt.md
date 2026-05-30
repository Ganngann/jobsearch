## 2026-05-30 - Unify Taxonomy Caching
**Learning:** When caching taxonomy datasets to pass to views, use Cache::remember() with targeted selects (e.g., ->get(['id', 'label', 'code'])) and explicitly order them. Avoid using Model::all() to prevent serializing unnecessary columns (like large relations or timestamps) into the cache payload. Unify cache keys across controllers requiring the same dataset to maximize cache hits.
**Action:** Use unified cache keys like 'all_languages_ordered' and targeted queries instead of duplicate caches or Model::all() when loading taxonomies.
