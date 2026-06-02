## 2026-06-02 - Optimize Model::all() to targeted select for cache payload reduction
**Learning:** Using `Model::all()` inside a cache closure serializes unnecessary model columns (like large relations, timestamps, or `longText` fields) into the cache payload, wasting memory and potentially causing memory limit issues.
**Action:** Always use targeted selects (e.g., `->orderBy('label')->get(['id', 'label', 'code'])`) when caching taxonomy datasets for view hydration to reduce cache size and execution time.
