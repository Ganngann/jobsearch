
## 2026-07-06 - Optimize Memory by Targeting Selects in Caching
**Learning:** Fetching full models (especially with large fields like logo_base64) for cached taxonomies causes severe memory and cache bloat.
**Action:** Always use targeted selects (e.g. select(['id', 'label'])) before withCount() when querying taxonomy datasets for caching.
