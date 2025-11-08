Security checklist# Pre-Launch Security Audit

## Authentication
- [ ] Passwords hashed with bcrypt/argon2
- [ ] Login rate limiting implemented
- [ ] Password reset flow secure (token expiry)
- [ ] Session tokens stored server-side
- [ ] Logout invalidates tokens properly

## Authorization
- [ ] Role-based access control enforced
- [ ] All admin endpoints check capabilities
- [ ] Direct object references protected (user can't access other user's data)

## Input Validation
- [ ] All user inputs sanitized
- [ ] File uploads validate MIME type with finfo
- [ ] Max file size enforced
- [ ] SQL queries use prepared statements
- [ ] No eval() or system() calls with user input

## Output Encoding
- [ ] HTML output uses esc_html()
- [ ] URLs use esc_url()
- [ ] JavaScript uses textContent or DOMPurify
- [ ] JSON responses properly structured

## CSRF Protection
- [ ] All state-changing forms have nonces
- [ ] REST endpoints validate nonces
- [ ] HTTP method validation (POST only for mutations)

## API Security
- [ ] Authentication required on sensitive endpoints
- [ ] Rate limiting on public endpoints
- [ ] CORS properly configured
- [ ] API versioning implemented
- [ ] Error messages don't leak sensitive info

## Data Protection
- [ ] Sensitive data encrypted at rest
- [ ] HTTPS enforced in production
- [ ] Database credentials not in code
- [ ] API keys stored securely (not in frontend)

## Logging & Monitoring
- [ ] Failed login attempts logged
- [ ] Sensitive actions audited
- [ ] Error logs don't expose stack traces in production
- [ ] Security events trigger alerts

## Infrastructure
- [ ] WordPress core updated
- [ ] PHP version ≥ 8.0
- [ ] File permissions correct (644 files, 755 folders)
- [ ] .htaccess protects sensitive files
- [ ] Database backups automated

## Testing
- [ ] Tested with non-admin roles
- [ ] Attempted SQL injection on all forms
- [ ] Tried XSS in text fields
- [ ] Verified file upload restrictions
- [ ] Checked for broken access control