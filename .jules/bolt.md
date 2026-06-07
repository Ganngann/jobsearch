
## 2026-06-07 - Prevent OOM by restricting cache memory payload
**Learning:** The Employer model contains a potentially massive `logo_base64` field. Caching `Employer::all()` or omitting a `select` clause pulls this field into memory and inflates the cache payload.
**Action:** When fetching Employer records for lists or caching, use targeted selects (e.g., `select(['id', 'label'])`) combined with `withCount` to prevent severe memory bloat.
