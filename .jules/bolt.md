## 2024-06-05 - Avoid Caching Large Unused Columns
**Learning:** Caching models that contain large columns (like base64 images) without a targeted select results in massive cache payloads and memory overhead.
**Action:** Always use targeted select() BEFORE withCount() when caching taxonomy datasets.
