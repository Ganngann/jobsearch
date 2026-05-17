## 2024-05-16 - Missing Explicit File Extension Allowlist
**Vulnerability:** File upload endpoints (like `uploadResume` and `uploadDocument`) were lacking `mimes` validation despite only handling documents.
**Learning:** Generic 'file' validation does not protect against malicious script uploads.
**Prevention:** Always combine `file` and `mimes` (or `mimetypes`) constraints on upload endpoints.
## 2026-05-17 - Added missing input validation in Controllers
**Vulnerability:** Missing maximum length constraints and validation on specific fields (`page_url` in `FeedbackController`, `content` in `ProfileChatController`).
**Learning:** Even internal or non-critical fields must be validated to prevent mass assignment, type juggling, or resource exhaustion (DoS) through oversized payloads.
**Prevention:** Always ensure every user-provided input, including seemingly trivial ones, has strict validation rules (like `max:` limits) before processing.
