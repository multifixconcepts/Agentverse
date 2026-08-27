# PHASE 1 — PROJECT DEFINITION

**Project**: ClientFlow — Multi-Tenant Client Management & Billing SaaS
**Evaluation**: AGENTVERSE_2_0_1_PRODUCTION_DELIVERY_CAPABILITY_V1
**Date**: 2026-08-23

---

## Project Overview

ClientFlow is a production-ready multi-tenant SaaS application for managing clients, creating invoices, tracking payments, and generating financial reports. It exercises every capability required by the evaluation.

## Technology Stack

| Layer | Technology | Rationale |
|-------|------------|-----------|
| Backend | Node.js + Express | Fast development, good ORM support |
| Database | PostgreSQL | Relational, ACID, multi-tenant capable |
| ORM | Prisma | Type-safe, migration support |
| Frontend | React + Vite | Modern SPA, fast builds |
| Auth | JWT + bcrypt | Stateless auth, secure passwords |
| Validation | Zod | Type-safe validation |
| Testing | Vitest | Fast, modern test runner |
| Docker | docker-compose | Local development + deployment |
| Reverse Proxy | Nginx | Production-grade |

## Core Features

### Authentication
- User registration with email/password
- Login with JWT token
- Logout (token invalidation)
- Password reset flow
- Session management
- Secure password storage (bcrypt)

### Authorization (RBAC)
- Platform Administrator: full system access
- Organization Administrator: manage org users, billing, settings
- Staff/User: manage own clients, invoices, payments

### Multi-Tenancy
- Organization-based isolation
- Database-level tenant separation
- Cross-tenant access blocked at API level
- Tenant context in all queries

### Core Business Domain
- **Clients**: CRUD, search, filtering
- **Products/Services**: catalog management
- **Invoices**: create, send, mark paid, recurring
- **Payments**: record, track, reconcile
- **Dashboard**: financial overview, recent activity
- **Activity/History**: audit trail

### Database
- Relational schema with foreign keys
- Indexed columns for performance
- Constraints for data integrity
- Migrations for schema evolution
- Seed data for demo

### Backend API
- RESTful endpoints
- Input validation (Zod)
- Authorization middleware
- Error handling
- Pagination, filtering, sorting
- Transactional operations

### Frontend
- Responsive dashboard
- Authentication UI (login, register, reset)
- CRUD screens for all entities
- Forms with validation feedback
- Loading/empty/error states
- Mobile-responsive layout

### Security
- SQL injection prevention (Prisma ORM)
- XSS prevention (output encoding)
- CSRF protection (SameSite cookies)
- Authorization bypass prevention
- IDOR prevention (tenant-scoped queries)
- Mass assignment prevention (explicit selects)
- Secrets management (environment variables)
- Input validation on all endpoints

### Testing
- Unit tests for business logic
- Integration tests for API endpoints
- Authorization tests
- Tenant-isolation tests
- Regression tests
- Negative tests (invalid input, unauthorized access)

### Production Readiness
- Docker deployment
- Environment configuration
- Health check endpoint
- Logging (structured)
- Database migrations
- Backup strategy
- Rollback capability
- Deployment documentation

## Project Structure

```
clientflow/
├── backend/
│   ├── src/
│   │   ├── middleware/
│   │   ├── routes/
│   │   ├── services/
│   │   ├── utils/
│   │   └── index.js
│   ├── prisma/
│   │   ├── schema.prisma
│   │   └── migrations/
│   ├── tests/
│   ├── Dockerfile
│   └── package.json
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   ├── hooks/
│   │   ├── utils/
│   │   └── App.jsx
│   ├── Dockerfile
│   └── package.json
├── docker-compose.yml
├── nginx/
│   └── nginx.conf
├── .env.example
├── README.md
└── DEPLOYMENT.md
```

## Definition of Done

- [ ] All API endpoints implemented and tested
- [ ] All frontend screens implemented and responsive
- [ ] Multi-tenant isolation verified
- [ ] Authorization enforced on all endpoints
- [ ] Security tests pass
- [ ] Docker deployment works
- [ ] Health check responds
- [ ] Database migrations apply cleanly
- [ ] Seed data populates demo account
- [ ] Documentation complete

---

**Project Selected**: ClientFlow — Multi-Tenant Client Management & Billing SaaS
**Ready for Phase 2**: Requirements and Architecture
