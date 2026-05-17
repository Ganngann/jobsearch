## 2024-05-17 - Prevent N+1 Query Regressions in Cross-Product Chunking
**Learning:** When attempting to optimize memory usage by chunking two large datasets for a cross-product comparison (e.g., matching all Users against all JobOffers), placing a `Model::chunk()` call inside a `foreach` loop over another chunked result causes a massive N+1 query explosion (e.g., executing the entire JobOffer chunk sequence for *every individual user*).
**Action:** Always implement block-level nested chunking by nesting the `Model::chunk()` closures, and then nesting the `foreach` iteration loops inside the innermost chunk closure to execute queries as `(N/chunk) * (M/chunk)` rather than `N * (M/chunk)`.

## 2026-05-17 - Optimize Repeated Writes and Reads inside Loops
**Learning:** Performing `create()`, `find()`, or `update()` inside a loop for deeply nested arrays causes O(N) database queries which is slow and an N+1 issue.
**Action:** Use a keyed array from `whereIn('id', $ids)->get()->keyBy('id')` before the loop to do lookups in `O(1)`, and accumulate inserts into an array (being mindful of timestamp and foreign key injection) to perform a single `Model::insert()` after the loop.
