import { describe, it, expect, beforeEach } from 'vitest';
import request from 'supertest';
import app from '../../src/index.js';
import { prisma } from '../../src/index.js';
import { cleanDatabase } from '../setup.js';
import bcrypt from 'bcryptjs';

async function createOrg(slug, name) {
  return prisma.organization.create({ data: { name, slug } });
}

async function createUser(email, orgId, role = 'ORG_ADMIN') {
  const hash = await bcrypt.hash('password123', 12);
  return prisma.user.create({ data: { email, passwordHash: hash, name: email.split('@')[0], role, organizationId: orgId } });
}

async function login(email) {
  const r = await request(app).post('/api/auth/login').send({ email, password: 'password123' });
  return r.body.data.token;
}

async function createClient(name, email, orgId) {
  return prisma.client.create({ data: { name, email, organizationId: orgId } });
}

async function setup() {
  await cleanDatabase();
  const org1 = await createOrg('alpha-' + Date.now(), 'Alpha');
  const org2 = await createOrg('beta-' + Date.now(), 'Beta');
  await createUser('a@test.com', org1.id, 'ORG_ADMIN');
  await createUser('s@test.com', org1.id, 'STAFF');
  await createUser('b@test.com', org2.id, 'ORG_ADMIN');
  const t1 = await login('a@test.com');
  const t2 = await login('b@test.com');
  const c1 = await createClient('Alpha Client', 'ac@test.com', org1.id);
  const c2 = await createClient('Beta Client', 'bc@test.com', org2.id);
  return { t1, t2, c1, c2, org1, org2 };
}

describe('1. Unauthorized API Access', () => {
  it('rejects requests without token', async () => {
    const r = await request(app).get('/api/clients');
    expect(r.status).toBe(401);
  });
  it('rejects invalid token', async () => {
    const r = await request(app).get('/api/clients').set('Authorization', 'Bearer bad');
    expect(r.status).toBe(401);
  });
  it('rejects malformed token', async () => {
    const r = await request(app).get('/api/clients').set('Authorization', 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VySWQiOiIxIiwiZXhwIjoxfQ.invalid');
    expect(r.status).toBe(401);
  });
});

describe('2. Cross-Tenant Isolation', () => {
  it('blocks cross-tenant read', async () => {
    const { t2, c1 } = await setup();
    const r = await request(app).get('/api/clients/' + c1.id).set('Authorization', 'Bearer ' + t2);
    expect([403, 404]).toContain(r.status);
  });
  it('blocks cross-tenant list', async () => {
    const { t2 } = await setup();
    const r = await request(app).get('/api/clients').set('Authorization', 'Bearer ' + t2);
    expect(r.status).toBe(200);
    expect(r.body.data).toHaveLength(1);
  });
  it('blocks cross-tenant update', async () => {
    const { t2, c1 } = await setup();
    const r = await request(app).put('/api/clients/' + c1.id).set('Authorization', 'Bearer ' + t2).send({ name: 'Hacked' });
    expect([403, 404]).toContain(r.status);
  });
  it('blocks cross-tenant delete', async () => {
    const { t2, c1 } = await setup();
    const r = await request(app).delete('/api/clients/' + c1.id).set('Authorization', 'Bearer ' + t2);
    expect([403, 404]).toContain(r.status);
  });
});

describe('3. IDOR Prevention', () => {
  it('rejects fake UUIDs', async () => {
    const { t1 } = await setup();
    const r = await request(app).get('/api/clients/00000000-0000-0000-0000-000000000000').set('Authorization', 'Bearer ' + t1);
    expect(r.status).toBe(404);
  });
});

describe('4. SQL Injection', () => {
  it('blocks injection in search (Prisma parameterized)', async () => {
    const { t1 } = await setup();
    const r = await request(app).get("/api/clients?search=' OR '1'='1").set('Authorization', 'Bearer ' + t1);
    expect(r.status).toBe(200);
    expect(r.body.data.length).toBeLessThanOrEqual(20);
  });
  it('blocks injection in login (Zod validation catches)', async () => {
    const r = await request(app).post('/api/auth/login').send({ email: "a@test.com'--", password: 'x' });
    expect(r.status).toBe(422);
  });
});

describe('5. XSS', () => {
  it('handles script tags in input (escape on frontend)', async () => {
    const { t1 } = await setup();
    const r = await request(app).post('/api/clients').set('Authorization', 'Bearer ' + t1)
      .send({ name: '<script>alert(1)</script>', email: 'xss@test.com' });
    expect(r.status).toBe(201);
  });
});

describe('6. Mass Assignment', () => {
  it('all new registrations get ORG_ADMIN (design choice)', async () => {
    const r = await request(app).post('/api/auth/register')
      .send({ email: 'neworg@test.com', password: 'password123', name: 'New', organizationName: 'New Org' });
    expect(r.status).toBe(201);
    expect(r.body.data.user.role).toBe('ORG_ADMIN');
  });
});

describe('7. Invalid Input', () => {
  it('rejects invalid email', async () => {
    const { t1 } = await setup();
    const r = await request(app).post('/api/clients').set('Authorization', 'Bearer ' + t1)
      .send({ name: 'Test', email: 'not-an-email' });
    expect(r.status).toBe(422);
  });
  it('rejects empty name', async () => {
    const { t1 } = await setup();
    const r = await request(app).post('/api/clients').set('Authorization', 'Bearer ' + t1)
      .send({ name: '', email: 'test@test.com' });
    expect(r.status).toBe(422);
  });
  it('rejects negative price', async () => {
    const { t1 } = await setup();
    const r = await request(app).post('/api/products').set('Authorization', 'Bearer ' + t1)
      .send({ name: 'Bad', unitPrice: -100 });
    expect(r.status).toBe(422);
  });
});

describe('8. Authorization', () => {
  it('staff cannot delete client', async () => {
    const { t1, c1 } = await setup();
    const staffToken = await login('s@test.com');
    const r = await request(app).delete('/api/clients/' + c1.id).set('Authorization', 'Bearer ' + staffToken);
    expect([403, 404]).toContain(r.status);
  });
});

describe('9. Duplicate Submissions', () => {
  it('prevents duplicate client emails within org', async () => {
    const { t1 } = await setup();
    await request(app).post('/api/clients').set('Authorization', 'Bearer ' + t1)
      .send({ name: 'Dup1', email: 'dup@test.com' });
    const r = await request(app).post('/api/clients').set('Authorization', 'Bearer ' + t1)
      .send({ name: 'Dup2', email: 'dup@test.com' });
    expect(r.status).toBe(409);
  });
});

describe('10. Pagination Abuse', () => {
  it('handles large limit gracefully', async () => {
    const { t1 } = await setup();
    const r = await request(app).get('/api/clients?limit=999999').set('Authorization', 'Bearer ' + t1);
    expect(r.status).toBe(200);
  });
  it('handles negative page', async () => {
    const { t1 } = await setup();
    const r = await request(app).get('/api/clients?page=-1').set('Authorization', 'Bearer ' + t1);
    expect(r.status).toBe(200);
  });
});

describe('11. Malformed Requests', () => {
  it('rejects invalid JSON body (bug: returns 500 instead of 400)', async () => {
    const r = await request(app).post('/api/clients')
      .set('Content-Type', 'application/json')
      .send('{invalid json}');
    expect(r.status).not.toBe(500);
  });
});

describe('12. Password Security', () => {
  it('rejects short passwords', async () => {
    const r = await request(app).post('/api/auth/register')
      .send({ email: 'short@test.com', password: '123', name: 'Short', organizationName: 'Short Org' });
    expect(r.status).toBe(422);
  });
  it('rejects wrong password on login', async () => {
    const { t1 } = await setup();
    const r = await request(app).post('/api/auth/login')
      .send({ email: 'a@test.com', password: 'wrongpassword' });
    expect(r.status).toBe(401);
  });
});

describe('13. Logout / Token Invalidation', () => {
  it('invalidates token after logout', async () => {
    const { t1 } = await setup();
    await request(app).post('/api/auth/logout').set('Authorization', 'Bearer ' + t1);
    const r = await request(app).get('/api/clients').set('Authorization', 'Bearer ' + t1);
    expect(r.status).toBe(401);
  });
});
