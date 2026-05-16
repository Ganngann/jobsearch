## 2026-05-16 - Missing Explicit File Extension Allowlist
**Vulnerability:** File upload endpoints (like  and ) were lacking  validation despite only handling documents.
**Learning:** Generic 'file' validation does not protect against malicious script uploads.
**Prevention:** Always combine  and  (or ) constraints on upload endpoints.
## 2024-05-16 - Missing Explicit File Extension Allowlist
**Vulnerability:** File upload endpoints (like `uploadResume` and `uploadDocument`) were lacking `mimes` validation despite only handling documents.
**Learning:** Generic 'file' validation does not protect against malicious script uploads.
**Prevention:** Always combine `file` and `mimes` (or `mimetypes`) constraints on upload endpoints.
