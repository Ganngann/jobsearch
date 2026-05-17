## 2024-05-16 - N+1 query problem in MatchingService
**Learning:** Found N+1 queries in `MatchingService::calculatePreScore` due to dynamic relationship calls (`$jobOffer->permits()->...->get()`) instead of using pre-loaded relationships (`$jobOffer->permits->where(...)`).
**Action:** Replace `()->where...()->get()` calls with collection methods on preloaded properties (`->where(...)`) to utilize eager loading when matching chunks of jobs.

## 2025-02-12 - Optimize Collection Lookups in Blade Loops
**Learning:** Performing `$collection->where('id', $id)->first()` inside a `@forelse` loop on another collection causes O(N*M) complexity, acting as a performance bottleneck when collections are relatively large or looping frequently.
**Action:** Pre-process the source collection outside the loop into a keyed array using `$keyed = $collection->keyBy('id');`, allowing O(1) lookups via `$keyed->get($id)` inside the loop. This can drastically reduce execution time.

## 2025-02-12 - Prevent Memory Exhaustion with Chunking
**Learning:** Using `Model::all()` in loops for mass operations loads the entire table into memory at once, causing significant memory spikes (e.g., ~22MB for 5000 records).
**Action:** Always use `Model::chunk($size, $callback)` or `Model::cursor()` for bulk processing tasks (like `ResetAndImportJobOffers` command) to maintain low, stable memory overhead regardless of table size.
