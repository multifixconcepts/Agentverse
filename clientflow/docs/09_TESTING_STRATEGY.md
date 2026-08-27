# Testing Strategy — ClientFlow

**Version**: 1.0
**Date**: 2026-08-23

---

## Test Pyramid

```
           ┌─────────┐
           │  E2E    │  10% - Critical paths
           ├─────────┤
           │Integration│  30% - API + DB
           ├─────────┤
           │  Unit   │  60% - Business logic
           └─────────┘
```

## Test Categories

### 1. Unit Tests
**Scope**: Business logic, utilities, validators
**Runner**: Vitest
**Coverage target**: >80%

Examples:
- Invoice total calculation
- Tax calculation
- Date formatting
- Email validation
- Password hashing

### 2. Integration Tests
**Scope**: API endpoints, database operations
**Runner**: Vitest + Supertest
**Database**: Test database (isolated)

Examples:
- Create client → verify in DB
- Create invoice → verify total
- Record payment → verify invoice status

### 3. Authorization Tests
**Scope**: Role-based access control
**Method**: API calls with different roles

Examples:
- Staff cannot delete client
- Org admin cannot access other org's data
- Platform admin can access all orgs

### 4. Tenant-Isolation Tests
**Scope**: Cross-tenant access prevention
**Method**: API calls from different orgs

Examples:
- Org A user cannot read Org B's clients
- Org A user cannot create invoice for Org B's client
- JWT token from Org A rejected for Org B resources

### 5. Security Tests
**Scope**: OWASP Top 10
**Method**: Automated + manual

Examples:
- SQL injection via input fields
- XSS via client name
- IDOR via UUID guessing
- Mass assignment via extra fields

### 6. Negative Tests
**Scope**: Invalid input, error handling
**Method**: API calls with bad data

Examples:
- Missing required fields
- Invalid email format
- Negative quantities
- Duplicate emails
- Expired tokens

## Test Data

### Seed Data
- 2 organizations (Acme Corp, Globex)
- 5 users (2 admins, 3 staff)
- 10 clients per org
- 5 products per org
- 20 invoices per org (various statuses)
- 30 payments per org

### Test Isolation
- Each test gets clean database state
- Tests run in transaction rollback
- No shared state between tests

## Test Execution

```bash
# Run all tests
npm test

# Run unit tests only
npm run test:unit

# Run integration tests only
npm run test:integration

# Run security tests only
npm run test:security

# Run with coverage
npm run test:coverage
```

## Quality Gates

| Gate | Threshold |
|------|-----------|
| Unit test coverage | >80% |
| Integration test pass | 100% |
| Security test pass | 100% |
| Authorization test pass | 100% |
| Tenant-isolation test pass | 100% |
