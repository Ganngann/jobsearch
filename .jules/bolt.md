## 2024-06-24 - Optimize Language Cache Loading
**Learning:** Using `Model::all()` inside `Cache::remember` fetches the entire model including unnecessary attributes, increasing memory and cache payload sizes.
**Action:** Use `Model::orderBy('label')->get(['id', 'label', 'code'])` to selectively load only required fields instead of all attributes and unify cache keys to maximize cache hits.
