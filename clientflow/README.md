# ClientFlow

Multi-tenant Client Management & Billing SaaS application.

## Features

- Authentication (register, login, logout)
- Role-based access control (Platform Admin, Org Admin, Staff)
- Multi-tenant organization isolation
- Client management
- Product catalog
- Invoice creation and management
- Payment tracking
- Dashboard with financial summary
- Activity audit trail

## Tech Stack

- **Backend**: Node.js, Express, Prisma ORM
- **Database**: PostgreSQL
- **Frontend**: React, Vite
- **Auth**: JWT + bcrypt
- **Validation**: Zod
- **Testing**: Vitest
- **Deployment**: Docker Compose

## Quick Start

### Development

```bash
# Backend
cd backend
npm install
npx prisma migrate dev
node prisma/seed.js
npm run dev

# Frontend
cd frontend
npm install
npm run dev
```

### Docker

```bash
docker-compose up -d
```

## Environment Variables

See `backend/.env.example`

## Testing

```bash
cd backend
npm test
```

## API Documentation

See `docs/05_API_CONTRACT.md`
