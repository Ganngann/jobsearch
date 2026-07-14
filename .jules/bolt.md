## 2026-07-14 - Prevent Eloquent Aggregate Subquery Overwrite
**Learning:** When combining targeted selects with aggregate functions like withCount() on Eloquent models, placing select() after withCount() can overwrite the aggregate subquery. In this codebase, avoiding the massive logo_base64 column on the Employer model requires targeted selects.
**Action:** Always place the select() clause before withCount() to ensure the aggregate subquery is correctly appended and memory payload is reduced.
