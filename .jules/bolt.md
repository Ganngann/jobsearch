## 2026-07-26 - Prevent memory bloat when caching models with large fields
**Learning:** Caching Eloquent models with large unneeded fields (like logo_base64) causes severe memory bloat. Combining select() with withCount() requires select() to be placed first to prevent overwriting the dynamically added count attribute.
**Action:** Always place select() before withCount() when fetching and caching models to only include necessary fields, preventing memory bloat and preserving dynamic count attributes.
