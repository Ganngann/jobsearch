## 2026-07-05 - Targeted Selects with Aggregate Functions
**Learning:** When combining targeted selects with aggregate functions like withCount() on Eloquent models to reduce memory payload, placing the select() clause after withCount() overwrites the aggregate subquery.
**Action:** Always place the select() clause before withCount() to ensure the aggregate subquery is correctly appended.
