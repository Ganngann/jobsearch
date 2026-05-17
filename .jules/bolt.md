## 2026-05-17 - Optimize Repeated Writes and Reads inside Loops
**Learning:** Performing `create()`, `find()`, or `update()` inside a loop for deeply nested arrays causes O(N) database queries which is slow and an N+1 issue.
**Action:** Use a keyed array from `whereIn('id', $ids)->get()->keyBy('id')` before the loop to do lookups in `O(1)`, and accumulate inserts into an array (being mindful of timestamp and foreign key injection) to perform a single `Model::insert()` after the loop.
