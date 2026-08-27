# Security Threat Model — ClientFlow

**Version**: 1.0
**Date**: 2026-08-23

---

## Threats and Mitigations

### 1. SQL Injection

| | |
|---|---|
| **Threat** | Attacker injects SQL via input fields |
| **Impact** | Data breach, data manipulation |
| **Mitigation** | Prisma ORM uses parameterized queries |
| **Verification** | Automated injection tests in Phase 4 |

### 2. Cross-Site Scripting (XSS)

| | |
|---|---|
| **Threat** | Attacker injects malicious scripts |
| **Impact** | Session hijacking, data theft |
| **Mitigation** | React auto-escaping, CSP headers |
| **Verification** | Automated XSS tests in Phase 4 |

### 3. Cross-Site Request Forgery (CSRF)

| | |
|---|---|
| **Threat** | Attacker tricks user into making requests |
| **Impact** | Unauthorized actions |
| **Mitigation** | SameSite cookies, CORS policy |
| **Verification** | CSRF tests in Phase 4 |

### 4. Insecure Direct Object Reference (IDOR)

| | |
|---|---|
| **Threat** | Attacker accesses resources by guessing IDs |
| **Impact** | Cross-tenant data access |
| **Mitigation** | Tenant-scoped queries, UUID primary keys |
| **Verification** | IDOR tests in Phase 4 |

### 5. Mass Assignment

| | |
|---|---|
| **Threat** | Attacker modifies protected fields |
| **Impact** | Privilege escalation, data corruption |
| **Mitigation** | Explicit field selection in Prisma |
| **Verification** | Mass assignment tests in Phase 4 |

### 6. Broken Authentication

| | |
|---|---|
| **Threat** | Attacker gains unauthorized access |
| **Impact** | Account compromise |
| **Mitigation** | bcrypt passwords, JWT expiry, token blacklist |
| **Verification** | Auth tests in Phase 4 |

### 7. Sensitive Data Exposure

| | |
|---|---|
| **Threat** | Passwords or secrets exposed |
| **Impact** | Account compromise, system breach |
| **Mitigation** | bcrypt hashing, env vars, no secrets in code |
| **Verification** | Secret scan, auth tests |

### 8. Security Misconfiguration

| | |
|---|---|
| **Threat** | Default configs, debug mode in production |
| **Impact** | Information disclosure, attack surface |
| **Mitigation** | Environment-based config, health checks |
| **Verification** | Deployment tests in Phase 7 |

### 9. Insufficient Logging

| | |
|---|---|
| **Threat** | Attacks go undetected |
| **Impact** | Delayed response, forensic difficulty |
| **Mitigation** | Activity log, structured logging |
| **Verification** | Audit trail tests |

### 10. Denial of Service

| | |
|---|---|
| **Threat** | Resource exhaustion attacks |
| **Impact** | Service unavailability |
| **Mitigation** | Rate limiting, pagination, input validation |
| **Verification** | Pagination abuse tests in Phase 4 |

## Security Testing Checklist

| Test | Method | Phase |
|------|--------|-------|
| SQL injection | Automated scanner + manual | Phase 4 |
| XSS | Automated scanner + manual | Phase 4 |
| CSRF | Token validation | Phase 4 |
| IDOR | Cross-tenant API calls | Phase 4 |
| Mass assignment | Modified request bodies | Phase 4 |
| Auth bypass | Unauthenticated requests | Phase 4 |
| Privilege escalation | Low-role admin requests | Phase 4 |
| Secret exposure | Code scan + env check | Phase 4 |
| Session management | Token expiry + blacklist | Phase 4 |
| Input validation | Invalid/malformed input | Phase 4 |
