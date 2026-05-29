## 2024-05-14 - Use targeted selects in taxonomy caching
**Learning:** Using `Model::all()` inside `Cache::remember()` for taxonomy datasets serializes all columns (including large unused fields or timestamps) into the cache payload, wasting memory and potentially increasing deserialization time.
**Action:** When caching taxonomy datasets for views, use targeted selects (e.g., `->get(['id', 'label', 'code'])`), order them correctly for the UI (`->orderBy('label')`), and share the same cache key across controllers to maximize cache hits.
