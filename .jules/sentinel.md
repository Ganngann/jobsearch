
## 2024-05-24 - Insecure Debug Configuration in Environment Example
**Vulnerability:** The `.env.example` file shipped with `APP_DEBUG=true` by default.
**Learning:** If this template is used for production deployments without modifying `APP_DEBUG`, detailed error messages containing sensitive information (stack traces, environment variables, database credentials) could be exposed to users/attackers upon application errors.
**Prevention:** Always set secure defaults in example configuration files (e.g., `APP_DEBUG=false`, secure cookie flags, strong password requirements) assuming they might be deployed as-is.
