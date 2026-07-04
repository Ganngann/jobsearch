## 2026-07-04 - Targeted Selects with Aggregates
**Learning:** When using caching for taxonomies like Employers (which have large logo_base64 fields), fetching all columns causes severe memory bloat. Combining targeted selects with aggregate functions like withCount() requires placing the select() clause before withCount() to ensure the aggregate subquery is appended.
**Action:** Always use targeted selects (e.g. select(['id', 'label'])) on models with large fields when caching for UI, and place them before aggregates.
