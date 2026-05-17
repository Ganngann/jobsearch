## 2026-05-17 - Optimize JobOfferService Sector Sync
**Learning:** Using `Model::updateOrCreate` inside a loop causes an N+1 query problem, making bulk operations extremely slow and generating heavy DB load. The performance can be vastly improved by using bulk operations.
**Action:** Use `Model::upsert()` followed by `whereIn()->pluck('id')` and `syncWithoutDetaching()` for related models when handling multiple elements, eliminating redundant DB queries.
