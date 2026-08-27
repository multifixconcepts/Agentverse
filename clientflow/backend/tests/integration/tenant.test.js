import { describe, it, expect, beforeAll } from 'vitest';
import request from 'supertest';
import app from '../../src/index.js';
import { prisma } from '../../src/index.js';
import bcrypt from 'bcryptjs';

describe('Tenant Isolation', () => {
  let org1Token, org2Token, org1ClientId;

  beforeAll(async () => {
    // Clean database
    await prisma.activityLog.deleteMany();
    await prisma.payment.deleteMany();
    await prisma.invoiceItem.deleteMany();
    await prisma.invoice.deleteMany();
    await prisma.client.deleteMany();
    await prisma.product.deleteMany();
    await prisma.tokenBlacklist.deleteMany();
    await prisma.user.deleteMany();
    await prisma.organization.deleteMany();

    // Create two organizations
    const org1 = await prisma.organization.create({
      data: { name: 'Org One', slug: 'org-one' }
    });
    const org2 = await prisma.organization.create({
      data: { name: 'Org Two', slug: 'org-two' }
    });

    // Create users for each org
    const passwordHash = await bcrypt.hash('password123', 12);
    await prisma.user.create({
      data: { email: 'user1@org1.com', passwordHash, name: 'User 1', role: 'ORG_ADMIN', organizationId: org1.id }
    });
    await prisma.user.create({
      data: { email: 'user2@org2.com', passwordHash, name: 'User 2', role: 'ORG_ADMIN', organizationId: org2.id }
    });

    // Login both users
    const login1 = await request(app).post('/api/auth/login').send({ email: 'user1@org1.com', password: 'password123' });
    const login2 = await request(app).post('/api/auth/login').send({ email: 'user2@org2.com', password: 'password123' });
    org1Token = login1.body.data.token;
    org2Token = login2.body.data.token;

    // Create client in org1
    const client = await prisma.client.create({
      data: { name: 'Org1 Client', email: 'client@org1.com', organizationId: org1.id }
    });
    org1ClientId = client.id;
  });

  it('should prevent cross-tenant client access', async () => {
    const res = await request(app)
      .get(`/api/clients/${org1ClientId}`)
      .set('Authorization', `Bearer ${org2Token}`);

    expect(res.status).toBe(404);
  });

  it('should prevent cross-tenant client listing', async () => {
    const res = await request(app)
      .get('/api/clients')
      .set('Authorization', `Bearer ${org2Token}`);

    expect(res.status).toBe(200);
    expect(res.body.data).toHaveLength(0);
  });

  it('should allow same-tenant client access', async () => {
    const res = await request(app)
      .get(`/api/clients/${org1ClientId}`)
      .set('Authorization', `Bearer ${org1Token}`);

    expect(res.status).toBe(200);
    expect(res.body.data.name).toBe('Org1 Client');
  });

  it('should reject unauthenticated requests', async () => {
    const res = await request(app).get('/api/clients');
    expect(res.status).toBe(401);
  });
});
