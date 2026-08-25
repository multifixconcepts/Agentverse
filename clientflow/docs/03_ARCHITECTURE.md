# Architecture Document — ClientFlow

**Version**: 1.0
**Date**: 2026-08-23

---

## 1. High-Level Architecture

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Browser   │────▶│   Nginx     │────▶│   Backend   │
│  (React)    │◀────│  (Reverse   │◀────│  (Express)  │
└─────────────┘     │   Proxy)    │     └──────┬──────┘
                    └─────────────┘            │
                                               │
                                        ┌──────▼──────┐
                                        │  PostgreSQL  │
                                        │  (Database)  │
                                        └─────────────┘
```

## 2. Technology Decisions

### 2.1 Backend: Node.js + Express
**Decision**: Use Node.js with Express framework.
**Rationale**: Fast development, excellent ORM support, large ecosystem.
**Alternatives considered**: Python/FastAPI (rejected: slower iteration), Go (rejected: higher complexity).

### 2.2 Database: PostgreSQL
**Decision**: Use PostgreSQL as primary database.
**Rationale**: ACID compliance, robust multi-tenant support, mature tooling.
**Alternatives considered**: MySQL (rejected: weaker JSON support), SQLite (rejected: not production-grade).

### 2.3 ORM: Prisma
**Decision**: Use Prisma as database ORM.
**Rationale**: Type-safe queries, excellent migrations, good DX.
**Alternatives considered**: TypeORM (rejected: less intuitive), raw SQL (rejected: more error-prone).

### 2.4 Frontend: React + Vite
**Decision**: Use React with Vite build tool.
**Rationale**: Modern, fast builds, component-based architecture.
**Alternatives considered**: Vue (rejected: smaller ecosystem), Angular (rejected: heavier).

### 2.5 Authentication: JWT
**Decision**: Use JWT for stateless authentication.
**Rationale**: Scalable, no server-side session storage needed.
**Trade-offs**: Token invalidation requires blacklist.

### 2.6 Validation: Zod
**Decision**: Use Zod for request validation.
**Rationale**: Type-safe, composable, good error messages.
**Alternatives considered**: Joi (rejected: less type-safe), express-validator (rejected: less ergonomic).

### 2.7 Testing: Vitest
**Decision**: Use Vitest as test runner.
**Rationale**: Fast, modern, Jest-compatible API.
**Alternatives considered**: Jest (rejected: slower), Mocha (rejected: less features).

## 3. Multi-Tenant Architecture

### 3.1 Tenant Isolation Strategy
**Decision**: Shared database, shared schema, tenant_id column.
**Rationale**: Cost-effective, simpler operations, sufficient for scale.
**Implementation**: Every table includes `organization_id` column. All queries filter by this column.

### 3.2 Tenant Context Flow
```
Request → JWT Token → Extract org_id → Middleware sets context → DB queries filter by org_id
```

### 3.3 Tenant Isolation Enforcement
1. **API Level**: Middleware validates JWT org_id matches request context
2. **Query Level**: Prisma middleware adds organization_id filter
3. **Database Level**: Foreign keys ensure referential integrity

## 4. Security Architecture

### 4.1 Authentication Flow
```
Register → Hash password → Store user → Issue JWT
Login → Verify password → Issue JWT
API Request → Extract JWT → Verify signature → Set context
```

### 4.2 Authorization Flow
```
Request → Extract user role → Check permission → Allow/Deny
```

### 4.3 Security Controls

| Threat | Control |
|--------|---------|
| SQL Injection | Prisma ORM parameterized queries |
| XSS | React auto-escaping, CSP headers |
| CSRF | SameSite cookies, CORS policy |
| IDOR | Tenant-scoped queries, UUID primary keys |
| Mass Assignment | Explicit field selection in Prisma |
| Secrets | Environment variables, not in code |
| Session Fixation | JWT with expiry, token blacklist |

## 5. API Design

### 5.1 Endpoint Structure
```
/api/auth/*          - Authentication endpoints
/api/users/*         - User management (admin only)
/api/clients/*       - Client CRUD
/api/products/*      - Product catalog
/api/invoices/*      - Invoice management
/api/payments/*      - Payment tracking
/api/dashboard/*     - Dashboard data
/api/audit/*         - Audit trail
```

### 5.2 Response Format
```json
{
  "success": true,
  "data": {},
  "meta": {
    "page": 1,
    "limit": 20,
    "total": 100
  }
}
```

### 5.3 Error Format
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid input",
    "details": []
  }
}
```

## 6. Database Schema

### 6.1 Core Tables
- `organizations` - Tenant organizations
- `users` - User accounts (belongs to org)
- `clients` - Client records (belongs to org)
- `products` - Product/service catalog (belongs to org)
- `invoices` - Invoice records (belongs to org)
- `invoice_items` - Invoice line items
- `payments` - Payment records
- `activity_log` - Audit trail
- `token_blacklist` - Blacklisted JWTs

### 6.2 Relationships
```
Organization 1──┬── N Users
             1──┬── N Clients
             1──┬── N Products
             1──┬── N Invoices
             1──┬── N Payments
             1──┬── N ActivityLog

User 1──┬── N Clients (assigned_to)
       1──┬── N Invoices (created_by)
       1──┬── N Payments (recorded_by)

Client 1──┬── N Invoices
Invoice 1──┬── N InvoiceItems
Invoice 1──┬── N Payments
```

## 7. Deployment Architecture

### 7.1 Docker Compose Services
```
┌─────────────────────────────────────────────┐
│                 Docker Compose               │
├─────────────────────────────────────────────┤
│  ┌─────────┐  ┌─────────┐  ┌─────────────┐ │
│  │  Nginx   │  │ Backend │  │ PostgreSQL  │ │
│  │  :80/443 │  │  :3000  │  │    :5432    │ │
│  └─────────┘  └─────────┘  └─────────────┘ │
│       │            │               │        │
│       └────────────┴───────────────┘        │
│              Internal Network               │
└─────────────────────────────────────────────┘
```

### 7.2 Environment Variables
```
DATABASE_URL=postgresql://user:pass@db:5432/clientflow
JWT_SECRET=secret-key
JWT_EXPIRY=24h
NODE_ENV=production
PORT=3000
```

## 8. Testing Strategy

### 8.1 Test Pyramid
```
        ┌─────────┐
        │  E2E    │  Few, slow, high confidence
        ├─────────┤
        │ Integration │  Some, medium speed
        ├─────────┤
        │  Unit   │  Many, fast, focused
        └─────────┘
```

### 8.2 Test Categories
- **Unit tests**: Business logic, utilities
- **Integration tests**: API endpoints, database operations
- **Authorization tests**: Role-based access control
- **Tenant-isolation tests**: Cross-tenant access blocked
- **Security tests**: Injection, XSS, IDOR
- **Negative tests**: Invalid input, unauthorized access
