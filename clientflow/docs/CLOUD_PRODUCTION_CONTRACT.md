# ClientFlow Cloud Production Contract

**Version:** 2.0.2
**Effective Date:** 2026-08-24
**Owner:** Platform Division

> **Scope claim:** This document describes the **ClientFlow** target-project infrastructure (Node.js backend API, PostgreSQL, Nginx, Docker) as deployed to `edunaija.online`. It does **not** describe AgentVerse itself.
>
> AgentVerse is a local multi-agent engineering and orchestration framework that runs inside OpenCode. It is not a deployed SaaS/service and has no hostname, browser UI, or server stack of its own. ClientFlow, ScholarPro, and other systems are **target projects** that AgentVerse can develop, test, and deploy; their infrastructure is documented separately here and under `clientflow/docs/`.

---

## 1. Infrastructure Requirements

### Minimum Production Environment
- **OS:** Linux (Ubuntu 22.04+ / Debian 12+)
- **CPU:** 2 vCPUs (ARM64 or AMD64)
- **RAM:** 4 GB minimum, 8 GB recommended
- **Storage:** 40 GB SSD
- **Network:** Public IP with HTTPS termination

### Required Services
| Service | Version | Port | Purpose |
|---------|---------|------|---------|
| Node.js | 20+ | 3000 | Backend API |
| PostgreSQL | 16+ | 5432 | Primary database |
| Nginx | 1.25+ | 80/443 | Reverse proxy + TLS |
| Docker | 24+ | - | Container runtime |

### Optional Services
| Service | Version | Port | Purpose |
|---------|---------|------|---------|
| Redis | 7+ | 6379 | Session cache |
| Prometheus | 2.x | 9090 | Metrics collection |
| Grafana | 10+ | 3000 | Metrics visualization |

---

## 2. Security Requirements

### TLS Termination
- **Certificate:** Let's Encrypt (auto-renewal via certbot or NPM)
- **Protocol:** TLS 1.2+ minimum
- **HSTS:** Enabled with `max-age=31536000; includeSubDomains`
- **HTTP/2:** Enabled

### Authentication
- **JWT Tokens:** RS256 signing, 24-hour expiry
- **Refresh Tokens:** 30-day expiry, rotation on use
- **Rate Limiting:** 10 requests/15 minutes for auth endpoints
- **Password Policy:** Minimum 8 characters, bcrypt hashing

### Network Security
- **CORS:** Restrictive origin policy
- **Helmet:** Security headers enabled
- **SQL Injection:** Parameterized queries only (Prisma ORM)
- **XSS:** HTML entity sanitization on all inputs

### Secret Management
- **No hardcoded secrets** in source code
- **Environment variables** for all configuration
- **Pre-commit hooks** for secret scanning
- **Git history scanning** for leaked credentials

---

## 3. Deployment Contract

### Container Requirements
```yaml
Services:
  frontend:
    image: nginx:alpine
    ports: ["80:80"]
    healthcheck: curl -f http://localhost:80 || exit 1
    
  backend:
    image: node:20-alpine
    ports: ["3000:3000"]
    healthcheck: curl -f http://localhost:3000/health || exit 1
    
  db:
    image: postgres:16-alpine
    ports: ["5432:5432"]
    healthcheck: pg_isready -U postgres
    
  nginx:
    image: nginx:alpine
    ports: ["80:80", "443:443"]
    depends_on: [frontend, backend]
```

### Health Checks
- **Endpoint:** `GET /health`
- **Response:** `{"status": "healthy", "version": "2.0.2", "uptime": <seconds>}`
- **Interval:** 30 seconds
- **Timeout:** 10 seconds
- **Retries:** 3

### Graceful Shutdown
- **SIGTERM** handling with 30-second timeout
- **Connection draining** for in-flight requests
- **Database connection cleanup**

---

## 4. Monitoring Contract

### Required Metrics
| Metric | Type | Description |
|--------|------|-------------|
| `http_requests_total` | Counter | Total HTTP requests |
| `http_request_duration_seconds` | Histogram | Request latency |
| `db_connections_active` | Gauge | Active DB connections |
| `memory_usage_bytes` | Gauge | Process memory usage |
| `uptime_seconds` | Gauge | Process uptime |

### Log Format
```json
{
  "timestamp": "ISO8601",
  "level": "info|warn|error",
  "message": "string",
  "context": {
    "request_id": "uuid",
    "user_id": "string",
    "action": "string"
  }
}
```

### Alerting Thresholds
| Condition | Severity | Action |
|-----------|----------|--------|
| Error rate > 5% | Critical | Page on-call |
| Latency p99 > 2s | Warning | Notify team |
| Disk usage > 80% | Warning | Notify team |
| DB connections > 80% pool | Critical | Page on-call |

---

## 5. Backup Contract

### Database Backups
- **Frequency:** Daily at 02:00 UTC
- **Retention:** 30 days
- **Encryption:** AES-256 at rest
- **Testing:** Monthly restore verification

### Application State
- **AGENTVERSE/ directory:** Daily snapshot
- **Git repository:** Immutable (GitHub)
- **Configuration:** Version-controlled

---

## 6. Scaling Contract

### Horizontal Scaling
- **Backend:** Stateless, scale behind load balancer
- **Frontend:** Static assets, CDN-suitable
- **Database:** Read replicas for read-heavy workloads

### Vertical Scaling
- **Memory:** Increase JVM/Node heap as needed
- **CPU:** Scale based on request latency
- **Storage:** Expand volume mounts as needed

---

## 7. Disaster Recovery

### RTO/RPO Targets
| Metric | Target |
|--------|--------|
| Recovery Time Objective (RTO) | 4 hours |
| Recovery Point Objective (RPO) | 24 hours |
| Availability Target | 99.5% |

### Recovery Procedures
1. **Database:** Restore from latest backup
2. **Application:** Redeploy from git tag
3. **Configuration:** Reapply environment variables
4. **DNS:** Update if IP changes

---

## 8. Compliance Checklist

- [ ] TLS termination on all public endpoints
- [ ] No hardcoded secrets in source
- [ ] Pre-commit secret scanning enabled
- [ ] Health checks configured
- [ ] Graceful shutdown handling
- [ ] Logging to structured format
- [ ] Database backups configured
- [ ] Monitoring alerts configured
- [ ] Documentation up to date

---

## 9. Version History

| Version | Date | Changes |
|---------|------|---------|
| 2.0.2 | 2026-08-24 | Initial cloud production contract |
| 2.0.1 | 2026-08-19 | ClientFlow deployment |
