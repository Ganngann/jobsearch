## 2026-07-27 - Avoid Caching Large Unused Eloquent Columns
**Learning:** When caching Eloquent collections with `Cache::remember` that use `withCount()`, large unneeded columns (like `logo_base64`) are pulled into memory and serialized into the cache if not explicitly excluded, causing significant memory bloat.
**Action:** Always use `select(['id', 'label'])` BEFORE `withCount()` when fetching models for UI filters to minimize cache size and memory footprint without overwriting the dynamic count attribute.
