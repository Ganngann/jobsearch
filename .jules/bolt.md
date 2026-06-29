## 2024-05-18 - Optimized Dashboard Sidebars Memory Overhead
**Learning:** `Metier` and `Employer` models are cached during the dashboard rendering with `JobOfferController`, loading all columns. `Employer` in particular includes `logo_base64`, causing high memory usage. Views only need `id` and `label`.
**Action:** Always constrain sidebar queries using `select(['id', 'label'])` before aggregate functions like `withCount()` to prevent memory bloat from heavy `longText` payload when caching.
