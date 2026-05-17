## 2024-05-16 - N+1 query problem in MatchingService
**Learning:** Found N+1 queries in `MatchingService::calculatePreScore` due to dynamic relationship calls (`$jobOffer->permits()->...->get()`) instead of using pre-loaded relationships (`$jobOffer->permits->where(...)`).
**Action:** Replace `()->where...()->get()` calls with collection methods on preloaded properties (`->where(...)`) to utilize eager loading when matching chunks of jobs.

## 2024-05-17 - Prevent N+1 Query Regressions in Cross-Product Chunking
**Learning:** When attempting to optimize memory usage by chunking two large datasets for a cross-product comparison (e.g., matching all Users against all JobOffers), placing a `Model::chunk()` call inside a `foreach` loop over another chunked result causes a massive N+1 query explosion (e.g., executing the entire JobOffer chunk sequence for *every individual user*).
**Action:** Always implement block-level nested chunking by nesting the `Model::chunk()` closures, and then nesting the `foreach` iteration loops inside the innermost chunk closure to execute queries as `(N/chunk) * (M/chunk)` rather than `N * (M/chunk)`.

## 2025-01-20 - Collection Filtering in Blade Loop Optimization
**Learning:** Using `$collection->where(...)->first()` inside a Blade `@foreach` loop introduces an O(N*M) time complexity bottleneck, because it linearly scans the collection on every iteration.
**Action:** When cross-referencing collections in templates, always pre-process the reference collection outside the loop using `->keyBy()` to create a hash map. Inside the loop, use `->get()` for O(1) lookups, changing the complexity to O(N+M) and resulting in an order-of-magnitude render speed improvement.

## 2025-02-12 - Optimize Collection Lookups in Blade Loops
**Learning:** Performing `$collection->where('id', $id)->first()` inside a `@forelse` loop on another collection causes O(N*M) complexity, acting as a performance bottleneck when collections are relatively large or looping frequently.
**Action:** Pre-process the source collection outside the loop into a keyed array using `$keyed = $collection->keyBy('id');`, allowing O(1) lookups via `$keyed->get($id)` inside the loop. This can drastically reduce execution time.

## 2025-02-12 - Prevent Memory Exhaustion with Chunking
**Learning:** Using `Model::all()` in loops for mass operations loads the entire table into memory at once, causing significant memory spikes (e.g., ~22MB for 5000 records).
**Action:** Always use `Model::chunk($size, $callback)` or `Model::cursor()` for bulk processing tasks (like `ResetAndImportJobOffers` command) to maintain low, stable memory overhead regardless of table size.

## 2026-05-17 - Optimize JobOfferService Sector Sync
**Learning:** Using `Model::updateOrCreate` inside a loop causes an N+1 query problem, making bulk operations extremely slow and generating heavy DB load. The performance can be vastly improved by using bulk operations.
**Action:** Use `Model::upsert()` followed by `whereIn()->pluck('id')` and `syncWithoutDetaching()` for related models when handling multiple elements, eliminating redundant DB queries.

## 2026-05-17 - Optimize Repeated Writes and Reads inside Loops
**Learning:** Performing `create()`, `find()`, or `update()` inside a loop for deeply nested arrays causes O(N) database queries which is slow and an N+1 issue.
**Action:** Use a keyed array from `whereIn('id', $ids)->get()->keyBy('id')` before the loop to do lookups in `O(1)`, and accumulate inserts into an array (being mindful of timestamp and foreign key injection) to perform a single `Model::insert()` after the loop.
