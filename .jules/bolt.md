## 2026-07-24 - Eloquent Select and withCount Ordering
**Learning:** In Laravel Eloquent, calling `select()` after `withCount()` or `whereHas()` overwrites the select array and destroys the dynamically generated count attribute (e.g., `job_offers_count`), breaking subsequent `orderBy` clauses and UI logic.
**Action:** Always place `select()` before `withCount()` when combining them to ensure dynamic attributes are preserved.
