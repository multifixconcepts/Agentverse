import { describe, it, expect, beforeEach } from 'vitest';
import request from 'supertest';
import app from '../../src/index.js';
import { prisma } from '../../src/index.js';
import { cleanDatabase } from '../setup.js';
import bcrypt from 'bcryptjs';

let token, orgId, clientId;

beforeEach(async () => {
  await cleanDatabase();
  const org = await prisma.organization.create({ data: { name: 'Test Org', slug: 'test-' + Date.now() } });
  orgId = org.id;
  const hash = await bcrypt.hash('password123', 12);
  await prisma.user.create({ data: { email: 'admin@test.com', passwordHash: hash, name: 'Admin', role: 'ORG_ADMIN', organizationId: orgId } });
  const loginRes = await request(app).post('/api/auth/login').send({ email: 'admin@test.com', password: 'password123' });
  token = loginRes.body.data.token;
  const client = await prisma.client.create({ data: { name: 'Test Client', email: 'client@test.com', organizationId: orgId } });
  clientId = client.id;
});

describe('Recurring Invoice Schedules', () => {
  it('should create a recurring schedule', async () => {
    const res = await request(app)
      .post('/api/recurring')
      .set('Authorization', 'Bearer ' + token)
      .send({
        clientId,
        name: 'Monthly Retainer',
        items: [{ description: 'Consulting', quantity: 1, unitPrice: 5000 }],
        frequency: 'MONTHLY',
        startDate: new Date().toISOString()
      });
    expect(res.status).toBe(201);
    expect(res.body.data.frequency).toBe('MONTHLY');
    expect(res.body.data.active).toBe(true);
  });

  it('should list recurring schedules', async () => {
    await request(app).post('/api/recurring').set('Authorization', 'Bearer ' + token)
      .send({ clientId, name: 'Weekly', items: [{ description: 'Service', quantity: 1, unitPrice: 100 }], frequency: 'WEEKLY', startDate: new Date().toISOString() });
    const res = await request(app).get('/api/recurring').set('Authorization', 'Bearer ' + token);
    expect(res.status).toBe(200);
    expect(res.body.data).toHaveLength(1);
  });

  it('should deactivate a schedule', async () => {
    const createRes = await request(app).post('/api/recurring').set('Authorization', 'Bearer ' + token)
      .send({ clientId, name: 'To Cancel', items: [{ description: 'Service', quantity: 1, unitPrice: 100 }], frequency: 'MONTHLY', startDate: new Date().toISOString() });
    const scheduleId = createRes.body.data.id;
    const res = await request(app).delete('/api/recurring/' + scheduleId).set('Authorization', 'Bearer ' + token);
    expect(res.status).toBe(200);
  });

  it('prevents staff from creating schedule', async () => {
    const hash = await bcrypt.hash('password123', 12);
    await prisma.user.create({ data: { email: 'staff@test.com', passwordHash: hash, name: 'Staff', role: 'STAFF', organizationId: orgId } });
    const staffLogin = await request(app).post('/api/auth/login').send({ email: 'staff@test.com', password: 'password123' });
    const staffToken = staffLogin.body.data.token;
    const res = await request(app).post('/api/recurring').set('Authorization', 'Bearer ' + staffToken)
      .send({ clientId, name: 'Nope', items: [{ description: 'X', quantity: 1, unitPrice: 1 }], frequency: 'MONTHLY', startDate: new Date().toISOString() });
    expect(res.status).toBe(403);
  });
});
