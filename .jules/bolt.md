## 2024-05-18 - Optimize Eloquent withCount Memory Payload
**Learning:** When fetching models to cache for UI filters, Eloquent's default SELECT * will load large columns like longText `logo_base64` into memory and cache, causing massive memory bloat.
**Action:** Always prepend `->select(['id', 'label'])` before aggregate functions like `withCount()` on taxonomy models for UI lists.
