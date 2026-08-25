# Database Schema — ClientFlow

**Version**: 1.0
**Date**: 2026-08-23

---

## Schema Definition (Prisma)

```prisma
generator client {
  provider = "prisma-client-js"
}

datasource db {
  provider = "postgresql"
  url      = env("DATABASE_URL")
}

model Organization {
  id        String   @id @default(uuid())
  name      String
  slug      String   @unique
  createdAt DateTime @default(now()) @map("created_at")
  updatedAt DateTime @updatedAt @map("updated_at")

  users     User[]
  clients   Client[]
  products  Product[]
  invoices  Invoice[]
  payments  Payment[]
  activityLog ActivityLog[]

  @@map("organizations")
}

model User {
  id             String   @id @default(uuid())
  email          String
  passwordHash   String   @map("password_hash")
  name           String
  role           Role     @default(STAFF)
  organizationId String   @map("organization_id")
  createdAt      DateTime @default(now()) @map("created_at")
  updatedAt      DateTime @updatedAt @map("updated_at")
  deletedAt      DateTime? @map("deleted_at")

  organization   Organization @relation(fields: [organizationId], references: [id])
  assignedClients Client[]     @relation("ClientAssignment")
  createdInvoices Invoice[]    @relation("InvoiceCreator")
  recordedPayments Payment[]   @relation("PaymentRecorder")
  activityLog    ActivityLog[]

  @@unique([email, organizationId])
  @@index([organizationId])
  @@index([email])
  @@map("users")
}

enum Role {
  PLATFORM_ADMIN
  ORG_ADMIN
  STAFF
}

model Client {
  id             String   @id @default(uuid())
  name           String
  email          String
  phone          String?
  address        String?
  notes          String?
  organizationId String   @map("organization_id")
  assignedToId   String?  @map("assigned_to_id")
  createdAt      DateTime @default(now()) @map("created_at")
  updatedAt      DateTime @updatedAt @map("updated_at")
  deletedAt      DateTime? @map("deleted_at")

  organization   Organization @relation(fields: [organizationId], references: [id])
  assignedTo     User?        @relation("ClientAssignment", fields: [assignedToId], references: [id])
  invoices       Invoice[]

  @@unique([email, organizationId])
  @@index([organizationId])
  @@index([assignedToId])
  @@map("clients")
}

model Product {
  id             String   @id @default(uuid())
  name           String
  description    String?
  unitPrice      Decimal  @map("unit_price") @db.Decimal(10, 2)
  active         Boolean  @default(true)
  organizationId String   @map("organization_id")
  createdAt      DateTime @default(now()) @map("created_at")
  updatedAt      DateTime @updatedAt @map("updated_at")

  organization   Organization @relation(fields: [organizationId], references: [id])
  invoiceItems   InvoiceItem[]

  @@index([organizationId])
  @@map("products")
}

model Invoice {
  id             String        @id @default(uuid())
  invoiceNumber  String        @map("invoice_number")
  clientId       String        @map("client_id")
  organizationId String        @map("organization_id")
  createdBy      String        @map("created_by")
  status         InvoiceStatus @default(DRAFT)
  subtotal       Decimal       @db.Decimal(10, 2)
  taxRate        Decimal       @default(0) @map("tax_rate") @db.Decimal(5, 2)
  taxAmount      Decimal       @default(0) @map("tax_amount") @db.Decimal(10, 2)
  total          Decimal       @db.Decimal(10, 2)
  notes          String?
  dueDate        DateTime?     @map("due_date")
  sentAt         DateTime?     @map("sent_at")
  paidAt         DateTime?     @map("paid_at")
  createdAt      DateTime      @default(now()) @map("created_at")
  updatedAt      DateTime      @updatedAt @map("updated_at")
  deletedAt      DateTime?     @map("deleted_at")

  client         Client        @relation(fields: [clientId], references: [id])
  organization   Organization  @relation(fields: [organizationId], references: [id])
  creator        User          @relation("InvoiceCreator", fields: [createdBy], references: [id])
  items          InvoiceItem[]
  payments       Payment[]

  @@unique([invoiceNumber, organizationId])
  @@index([organizationId])
  @@index([clientId])
  @@index([status])
  @@map("invoices")
}

enum InvoiceStatus {
  DRAFT
  SENT
  PAID
  OVERDUE
  CANCELLED
}

model InvoiceItem {
  id          String  @id @default(uuid())
  invoiceId   String  @map("invoice_id")
  productId   String? @map("product_id")
  description String
  quantity    Int
  unitPrice   Decimal @map("unit_price") @db.Decimal(10, 2)
  amount      Decimal @db.Decimal(10, 2)

  invoice     Invoice @relation(fields: [invoiceId], references: [id])
  product     Product? @relation(fields: [productId], references: [id])

  @@index([invoiceId])
  @@map("invoice_items")
}

model Payment {
  id              String        @id @default(uuid())
  invoiceId       String        @map("invoice_id")
  organizationId  String        @map("organization_id")
  recordedBy      String        @map("recorded_by")
  amount          Decimal       @db.Decimal(10, 2)
  paymentMethod   PaymentMethod @map("payment_method")
  status          PaymentStatus @default(COMPLETED)
  reference       String?
  notes           String?
  recordedAt      DateTime      @default(now()) @map("recorded_at")
  createdAt       DateTime      @default(now()) @map("created_at")

  invoice         Invoice       @relation(fields: [invoiceId], references: [id])
  organization    Organization  @relation(fields: [organizationId], references: [id])
  recorder        User          @relation("PaymentRecorder", fields: [recordedBy], references: [id])

  @@index([organizationId])
  @@index([invoiceId])
  @@map("payments")
}

enum PaymentMethod {
  CASH
  CHECK
  BANK_TRANSFER
  CARD
}

enum PaymentStatus {
  PENDING
  COMPLETED
  FAILED
  REFUNDED
}

model ActivityLog {
  id             String   @id @default(uuid())
  organizationId String   @map("organization_id")
  userId         String   @map("user_id")
  action         String
  entityType     String   @map("entity_type")
  entityId       String   @map("entity_id")
  details        Json?
  createdAt      DateTime @default(now()) @map("created_at")

  organization   Organization @relation(fields: [organizationId], references: [id])
  user           User         @relation(fields: [userId], references: [id])

  @@index([organizationId])
  @@index([userId])
  @@index([entityType, entityId])
  @@index([createdAt])
  @@map("activity_log")
}

model TokenBlacklist {
  id        String   @id @default(uuid())
  token     String   @unique
  expiresAt DateTime @map("expires_at")
  createdAt DateTime @default(now()) @map("created_at")

  @@index([token])
  @@map("token_blacklist")
}
```

## Indexes Summary

| Table | Index | Columns | Purpose |
|-------|-------|---------|---------|
| users | idx_users_org | organization_id | Tenant isolation |
| users | idx_users_email | email | Login lookup |
| clients | idx_clients_org | organization_id | Tenant isolation |
| clients | idx_clients_assigned | assigned_to_id | Assignment queries |
| products | idx_products_org | organization_id | Tenant isolation |
| invoices | idx_invoices_org | organization_id | Tenant isolation |
| invoices | idx_invoices_client | client_id | Client invoices |
| invoices | idx_invoices_status | status | Status filtering |
| payments | idx_payments_org | organization_id | Tenant isolation |
| payments | idx_payments_invoice | invoice_id | Invoice payments |
| activity_log | idx_audit_org | organization_id | Tenant isolation |
| activity_log | idx_audit_user | user_id | User activity |
| activity_log | idx_audit_entity | entity_type, entity_id | Entity history |
| activity_log | idx_audit_time | created_at | Time-range queries |
| token_blacklist | idx_token | token | Token lookup |
