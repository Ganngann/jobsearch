## 2024-05-18 - Optimized Employer Selection for Memory Profiling
**Learning:** Selecting heavy model columns like `logo_base64` in caching aggregates exhausts PHP memory.
**Action:** When mapping over massive data for UI components or cache sets, explicitly define `select(['id', 'label'])` before methods like `withCount()` to prevent Out-Of-Memory (OOM) errors.
