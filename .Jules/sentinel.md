## 2024-05-16 - Missing Explicit File Extension Allowlist
**Vulnerability:** File upload endpoints (like `uploadResume` and `uploadDocument`) were lacking `mimes` validation despite only handling documents.
**Learning:** Generic 'file' validation does not protect against malicious script uploads.
**Prevention:** Always combine `file` and `mimes` (or `mimetypes`) constraints on upload endpoints.

## 2026-05-17 - Added missing input validation in Controllers
**Vulnerability:** Missing maximum length constraints and validation on specific fields (`page_url` in `FeedbackController`, `content` in `ProfileChatController`).
**Learning:** Even internal or non-critical fields must be validated to prevent mass assignment, type juggling, or resource exhaustion (DoS) through oversized payloads.
**Prevention:** Always ensure every user-provided input, including seemingly trivial ones, has strict validation rules (like `max:` limits) before processing.

## 2026-05-17 - XSS via Unescaped Output in Job Offer Description
**Vulnerability:** Unescaped HTML rendering ({!! !!}) of external Job Offer descriptions allowed stored XSS.
**Learning:** Rendering complex JSON structures from external APIs (like Forem) directly via Blade's raw unescaped tags is extremely dangerous without prior HTML sanitization. Also, caching directories for third-party libraries (like HTMLPurifier) must be explicitly created in the Provider to avoid 500 errors on fresh deployments.
**Prevention:** Use a robust HTML sanitizer (e.g., HTMLPurifier) combined with a custom Blade directive (like @purify) when you need to render safe HTML from untrusted sources, rather than relying on raw unescaped tags.

## 2024-05-24 - Insecure Debug Configuration in Environment Example
**Vulnerability:** The `.env.example` file shipped with `APP_DEBUG=true` by default.
**Learning:** If this template is used for production deployments without modifying `APP_DEBUG`, detailed error messages containing sensitive information (stack traces, environment variables, database credentials) could be exposed to users/attackers upon application errors.
**Prevention:** Always set secure defaults in example configuration files (e.g., `APP_DEBUG=false`, secure cookie flags, strong password requirements) assuming they might be deployed as-is.
