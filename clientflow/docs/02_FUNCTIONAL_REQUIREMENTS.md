# Functional Requirements — ClientFlow

**Version**: 1.0
**Date**: 2026-08-23

---

## FR-AUTH: Authentication

### FR-AUTH-001: User Registration
- **Given** a visitor on the registration page
- **When** they submit valid email and password
- **Then** a new user account is created
- **And** they receive a JWT token
- **And** password is stored as bcrypt hash

### FR-AUTH-002: User Login
- **Given** a registered user
- **When** they submit correct email and password
- **Then** they receive a JWT token
- **And** token expires in 24 hours

### FR-AUTH-003: User Logout
- **Given** an authenticated user
- **When** they request logout
- **Then** their token is blacklisted
- **And** subsequent requests with that token are rejected

### FR-AUTH-004: Password Reset
- **Given** a user who forgot their password
- **When** they request password reset with their email
- **Then** a reset token is generated
- **And** when they submit new password with valid token
- **Then** password is updated
- **And** reset token is invalidated

## FR-RBAC: Role-Based Access Control

### FR-RBAC-001: Platform Administrator
- **Can** manage all organizations
- **Can** manage all users across organizations
- **Can** access system-wide reports
- **Cannot** modify organization-specific data directly

### FR-RBAC-002: Organization Administrator
- **Can** manage users within their organization
- **Can** manage all clients, invoices, payments in their org
- **Can** manage organization settings
- **Cannot** access other organizations' data
- **Cannot** manage platform-level settings

### FR-RBAC-003: Staff/User
- **Can** manage clients assigned to them
- **Can** create and manage invoices
- **Can** record payments
- **Cannot** manage organization settings
- **Cannot** manage other users
- **Cannot** access other organizations' data

## FR-TENANT: Multi-Tenancy

### FR-TENANT-001: Organization Isolation
- All data queries are scoped to the current organization
- Cross-tenant queries return 403 Forbidden
- Database queries include organization_id filter

### FR-TENANT-002: Tenant Context
- JWT token includes organization_id
- All API requests validate tenant context
- Tenant context cannot be overridden by client

## FR-CLIENT: Client Management

### FR-CLIENT-001: Create Client
- **Required fields**: name, email
- **Optional fields**: phone, address, notes
- **Validations**: email unique within org, name not empty

### FR-CLIENT-002: List Clients
- Paginated (default 20 per page)
- Filterable by name, email, status
- Sortable by name, created_at
- Searchable by name or email

### FR-CLIENT-003: Update Client
- All fields updatable
- Email uniqueness maintained
- Audit log entry created

### FR-CLIENT-004: Delete Client
- Soft delete (set deleted_at)
- Cannot delete client with active invoices
- Audit log entry created

## FR-INVOICE: Invoice Management

### FR-INVOICE-001: Create Invoice
- **Required**: client_id, line items (description, quantity, unit_price)
- **Auto-calculated**: subtotal, tax, total
- **Default status**: draft
- **Invoice number**: auto-generated (INV-YYYY-NNNN)

### FR-INVOICE-002: Send Invoice
- Status changes from draft to sent
- Timestamp recorded
- Audit log entry created

### FR-INVOICE-003: Mark Invoice Paid
- Status changes to paid
- Payment record created
- Audit log entry created

### FR-INVOICE-004: Cancel Invoice
- Status changes to cancelled
- Cannot cancel paid invoices
- Audit log entry created

## FR-PAYMENT: Payment Tracking

### FR-PAYMENT-001: Record Payment
- **Required**: invoice_id, amount, payment_method
- **Payment methods**: cash, check, bank_transfer, card
- **Status**: pending → completed/failed

### FR-PAYMENT-002: Partial Payment
- Amount can be less than invoice total
- Invoice status remains sent if partial
- Remaining balance tracked

## FR-DASHBOARD: Dashboard

### FR-DASHBOARD-001: Financial Summary
- Total revenue (current month, current year)
- Outstanding amount
- Overdue amount
- Payment collection rate

### FR-DASHBOARD-002: Activity Feed
- Recent invoices, payments, clients
- Last 20 activities
- User who performed action
- Timestamp

## FR-AUDIT: Activity Trail

### FR-AUDIT-001: Log Actions
- All CRUD operations logged
- User ID, action, entity type, entity ID, timestamp
- Optional details (JSON)

### FR-AUDIT-002: Query Activity
- Filter by user, action type, date range
- Paginated results
- Exportable (stretch goal)
