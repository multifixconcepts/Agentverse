# API Contract — ClientFlow

**Version**: 1.0
**Date**: 2026-08-23

---

## Base URL

```
/api
```

## Authentication

All endpoints except `/api/auth/*` require JWT token in Authorization header:

```
Authorization: Bearer <token>
```

## Endpoints

### Auth

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | /api/auth/register | Register new user | No |
| POST | /api/auth/login | Login | No |
| POST | /api/auth/logout | Logout | Yes |
| POST | /api/auth/password-reset | Request password reset | No |
| POST | /api/auth/password-reset/confirm | Confirm password reset | No |

### Users (Admin only)

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /api/users | List users | org_admin+ |
| POST | /api/users | Create user | org_admin+ |
| GET | /api/users/:id | Get user | org_admin+ |
| PUT | /api/users/:id | Update user | org_admin+ |
| DELETE | /api/users/:id | Delete user | org_admin+ |

### Clients

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /api/clients | List clients | staff+ |
| POST | /api/clients | Create client | staff+ |
| GET | /api/clients/:id | Get client | staff+ |
| PUT | /api/clients/:id | Update client | staff+ |
| DELETE | /api/clients/:id | Delete client | org_admin+ |

### Products

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /api/products | List products | staff+ |
| POST | /api/products | Create product | org_admin+ |
| GET | /api/products/:id | Get product | staff+ |
| PUT | /api/products/:id | Update product | org_admin+ |
| DELETE | /api/products/:id | Delete product | org_admin+ |

### Invoices

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /api/invoices | List invoices | staff+ |
| POST | /api/invoices | Create invoice | staff+ |
| GET | /api/invoices/:id | Get invoice | staff+ |
| PUT | /api/invoices/:id | Update invoice | staff+ |
| DELETE | /api/invoices/:id | Delete invoice | org_admin+ |
| POST | /api/invoices/:id/send | Send invoice | staff+ |
| POST | /api/invoices/:id/pay | Mark as paid | staff+ |
| POST | /api/invoices/:id/cancel | Cancel invoice | org_admin+ |

### Payments

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /api/payments | List payments | staff+ |
| POST | /api/payments | Record payment | staff+ |
| GET | /api/payments/:id | Get payment | staff+ |

### Dashboard

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /api/dashboard/summary | Financial summary | staff+ |
| GET | /api/dashboard/activity | Recent activity | staff+ |

### Audit

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /api/audit | List audit entries | org_admin+ |

## Request/Response Examples

### POST /api/auth/register

**Request:**
```json
{
  "email": "user@example.com",
  "password": "securePassword123",
  "name": "John Doe",
  "organizationName": "Acme Corp"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": "uuid",
      "email": "user@example.com",
      "name": "John Doe",
      "role": "ORG_ADMIN",
      "organizationId": "uuid"
    },
    "token": "jwt-token"
  }
}
```

### POST /api/invoices

**Request:**
```json
{
  "clientId": "client-uuid",
  "items": [
    {
      "description": "Web Development",
      "quantity": 40,
      "unitPrice": 150.00
    }
  ],
  "taxRate": 10,
  "notes": "Project phase 1",
  "dueDate": "2026-09-23"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": "invoice-uuid",
    "invoiceNumber": "INV-2026-0001",
    "status": "DRAFT",
    "subtotal": 6000.00,
    "taxRate": 10,
    "taxAmount": 600.00,
    "total": 6600.00,
    "items": [...]
  }
}
```

### Error Response

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid input",
    "details": [
      {
        "field": "email",
        "message": "Invalid email format"
      }
    ]
  }
}
```

## Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 204 | No Content |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 409 | Conflict |
| 422 | Unprocessable Entity |
| 500 | Internal Server Error |

## Pagination

All list endpoints support pagination:

```
GET /api/clients?page=1&limit=20&sort=name&order=asc&search=john
```

**Response:**
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "page": 1,
    "limit": 20,
    "total": 100,
    "totalPages": 5
  }
}
```
