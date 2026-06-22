## 2026-06-22 - Optimize caching by selecting required columns for Metier and Employer
**Learning:** The Employer and Metier models have huge fields like logo_base64 and descriptions. Caching them wholesale using Model::get() serialized the entire model, consuming significant memory.
**Action:** Use targeted selects like get(['id', 'label', 'logo_base64']) or select(['id', 'label']) before withCount() to restrict payloads and prevent memory bloat during caching.
