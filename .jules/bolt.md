## 2026-05-25 - Avoiding all() for Cached Datasets
**Learning:** Caching Model::all() pulls in unnecessary columns (like timestamps or heavy relations) into the serialized cache payload, inflating memory usage. Caching mechanisms can also become fragmented if different controllers use different keys for the exact same dataset.
**Action:** When caching taxonomies or large datasets to pass to views, use targeted selects (e.g., `get(['id', 'label'])`), explicitly order them if needed in the UI, and align the cache keys across controllers to maximize cache hits.
