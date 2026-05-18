## 2024-05-18 - Avoid loading unused taxonomies in controllers
**Learning:** Taxonomy datasets (like `allSkills`, `allPermits`) passed to views but unused or loaded later via API cause unnecessary O(N) memory allocation and DB queries.
**Action:** When passing taxonomy collections to Blade views, strictly verify if they are consumed in the view templates. Remove unused datasets, and cache the necessary ones using `Cache::remember`.
