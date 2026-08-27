# Deployment Architecture — ClientFlow

**Version**: 1.0
**Date**: 2026-08-23

---

## Docker Compose Stack

```yaml
version: '3.8'

services:
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./nginx/nginx.conf:/etc/nginx/nginx.conf
    depends_on:
      - backend
      - frontend

  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    environment:
      - VITE_API_URL=http://localhost/api

  backend:
    build:
      context: ./backend
      dockerfile: Dockerfile
    environment:
      - DATABASE_URL=postgresql://clientflow:password@db:5432/clientflow
      - JWT_SECRET=your-secret-key
      - JWT_EXPIRY=24h
      - NODE_ENV=production
      - PORT=3000
    depends_on:
      db:
        condition: service_healthy

  db:
    image: postgres:16-alpine
    environment:
      - POSTGRES_USER=clientflow
      - POSTGRES_PASSWORD=password
      - POSTGRES_DB=clientflow
    volumes:
      - postgres_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U clientflow"]
      interval: 5s
      timeout: 5s
      retries: 5

volumes:
  postgres_data:
```

## Environment Configuration

### Development
```
DATABASE_URL=postgresql://clientflow:password@localhost:5432/clientflow
JWT_SECRET=dev-secret-key
JWT_EXPIRY=24h
NODE_ENV=development
PORT=3000
```

### Production
```
DATABASE_URL=postgresql://clientflow:strong-password@db:5432/clientflow
JWT_SECRET=<generated-secret>
JWT_EXPIRY=24h
NODE_ENV=production
PORT=3000
```

## Deployment Steps

### 1. Clone and Build
```bash
git clone https://github.com/multifixconcepts/Agentverse.git
cd Agentverse/clientflow
docker-compose build
```

### 2. Run Migrations
```bash
docker-compose run backend npx prisma migrate deploy
```

### 3. Seed Database
```bash
docker-compose run backend node prisma/seed.js
```

### 4. Start Services
```bash
docker-compose up -d
```

### 5. Verify Health
```bash
curl http://localhost/health
```

## Health Check

```json
{
  "status": "healthy",
  "timestamp": "2026-08-23T00:00:00Z",
  "services": {
    "database": "connected",
    "backend": "running"
  }
}
```

## Backup Strategy

### Database Backup
```bash
docker-compose exec db pg_dump -U clientflow clientflow > backup_$(date +%Y%m%d).sql
```

### Restore
```bash
cat backup_20260823.sql | docker-compose exec -T db psql -U clientflow clientflow
```

## Rollback Strategy

1. Stop current version
2. Restore database from backup
3. Deploy previous version
4. Verify health

## Logging

Structured JSON logging to stdout:
```json
{
  "timestamp": "2026-08-23T00:00:00Z",
  "level": "info",
  "message": "Request processed",
  "method": "POST",
  "path": "/api/invoices",
  "statusCode": 201,
  "userId": "uuid",
  "organizationId": "uuid",
  "duration": 45
}
```
