## 2026-06-19 - OOM due to Model::all and large relation models in Cache
**Learning:** When using Cache::remember with Model::all(), Eloquent will select and load all columns, including massive fields like logo_base64 on Employers, taking huge memory payload.
**Action:** Use targeted select() on specific columns like id and label BEFORE withCount() or aggregate functions to prevent cache memory exhaustion.
