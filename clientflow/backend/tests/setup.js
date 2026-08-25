import dotenv from 'dotenv';
import { resolve } from 'path';
dotenv.config({ path: resolve(process.cwd(), '.env') });

import { PrismaClient } from '@prisma/client';
import { beforeAll, afterAll } from 'vitest';

export const prisma = new PrismaClient();

export async function cleanDatabase() {
  await prisma.activityLog.deleteMany();
  await prisma.payment.deleteMany();
  await prisma.invoiceItem.deleteMany();
  await prisma.invoice.deleteMany();
  await prisma.recurringSchedule.deleteMany();
  await prisma.client.deleteMany();
  await prisma.product.deleteMany();
  await prisma.tokenBlacklist.deleteMany();
  await prisma.user.deleteMany();
  await prisma.organization.deleteMany();
}

beforeAll(async () => {
  await prisma.$connect();
  await cleanDatabase();
});

afterAll(async () => {
  await cleanDatabase();
  await prisma.$disconnect();
});
