# RBAC Model — ClientFlow

**Version**: 1.0
**Date**: 2026-08-23

---

## Roles

| Role | Description | Hierarchy |
|------|-------------|-----------|
| PLATFORM_ADMIN | System-wide access | Highest |
| ORG_ADMIN | Organization management | Medium |
| STAFF | Day-to-day operations | Lowest |

## Permission Matrix

| Resource | Action | PLATFORM_ADMIN | ORG_ADMIN | STAFF |
|----------|--------|----------------|-----------|-------|
| Organization | read | ✅ | ✅ (own) | ❌ |
| Organization | update | ✅ | ❌ | ❌ |
| User | create | ✅ | ✅ (own org) | ❌ |
| User | read | ✅ | ✅ (own org) | ❌ |
| User | update | ✅ | ✅ (own org) | ❌ |
| User | delete | ✅ | ✅ (own org) | ❌ |
| Client | create | ✅ | ✅ | ✅ |
| Client | read | ✅ | ✅ | ✅ (assigned) |
| Client | update | ✅ | ✅ | ✅ (assigned) |
| Client | delete | ✅ | ✅ | ❌ |
| Product | create | ✅ | ✅ | ❌ |
| Product | read | ✅ | ✅ | ✅ |
| Product | update | ✅ | ✅ | ❌ |
| Product | delete | ✅ | ✅ | ❌ |
| Invoice | create | ✅ | ✅ | ✅ |
| Invoice | read | ✅ | ✅ | ✅ (created) |
| Invoice | update | ✅ | ✅ | ✅ (created, draft) |
| Invoice | delete | ✅ | ✅ | ❌ |
| Invoice | send | ✅ | ✅ | ✅ (created) |
| Invoice | pay | ✅ | ✅ | ✅ (created) |
| Invoice | cancel | ✅ | ✅ | ❌ |
| Payment | create | ✅ | ✅ | ✅ |
| Payment | read | ✅ | ✅ | ✅ (recorded) |
| Dashboard | read | ✅ | ✅ | ✅ |
| Audit | read | ✅ | ✅ | ❌ |

## Authorization Rules

### Rule 1: Tenant Isolation
Every data access must include organization_id filter.
Platform admins can access any organization.
Non-platform admins can only access their own organization.

### Rule 2: Role Hierarchy
Higher roles inherit permissions of lower roles.
ORG_ADMIN can do everything STAFF can do.
PLATFORM_ADMIN can do everything ORG_ADMIN can do.

### Rule 3: Resource Ownership
STAFF can only access resources they created or are assigned to.
ORG_ADMIN can access all resources in their organization.
PLATFORM_ADMIN can access all resources.

### Rule 4: Self-Modification
Users can update their own profile (name, password).
Users cannot change their own role.
Only ORG_ADMIN+ can change user roles.

## Implementation

### Middleware Stack
```
Request → Auth Middleware (verify JWT) → Role Middleware (check permission) → Route Handler
```

### Authorization Check
```javascript
// Example: Can this user access this client?
const canAccess = (
  user.role === 'PLATFORM_ADMIN' ||
  (user.role === 'ORG_ADMIN' && client.organizationId === user.organizationId) ||
  (user.role === 'STAFF' && client.assignedToId === user.id)
);
```

## Security Properties

| Property | Enforcement |
|----------|-------------|
| Tenant isolation | JWT org_id + DB queries |
| Role enforcement | Middleware + service layer |
| Resource ownership | Query filters |
| Privilege escalation | Role field immutable by user |
| Cross-tenant access | Blocked at API + DB level |
