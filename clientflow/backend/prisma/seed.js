import { PrismaClient } from '@prisma/client';
import bcrypt from 'bcryptjs';

const prisma = new PrismaClient();

async function main() {
  console.log('Seeding database...');

  // Create organizations
  const org1 = await prisma.organization.create({
    data: { name: 'Acme Corp', slug: 'acme-corp' }
  });
  const org2 = await prisma.organization.create({
    data: { name: 'Globex Inc', slug: 'globex-inc' }
  });

  // Create users
  const passwordHash = await bcrypt.hash('password123', 12);
  const user1 = await prisma.user.create({
    data: { email: 'admin@acme.com', passwordHash, name: 'Acme Admin', role: 'ORG_ADMIN', organizationId: org1.id }
  });
  const user2 = await prisma.user.create({
    data: { email: 'staff@acme.com', passwordHash, name: 'Acme Staff', role: 'STAFF', organizationId: org1.id }
  });
  await prisma.user.create({
    data: { email: 'admin@globex.com', passwordHash, name: 'Globex Admin', role: 'ORG_ADMIN', organizationId: org2.id }
  });

  // Create clients for org1
  const clients = [];
  for (let i = 1; i <= 10; i++) {
    const client = await prisma.client.create({
      data: {
        name: `Client ${i}`,
        email: `client${i}@example.com`,
        phone: `555-000${i}`,
        organizationId: org1.id,
        assignedToId: i % 2 === 0 ? user2.id : null
      }
    });
    clients.push(client);
  }

  // Create products for org1
  const products = [];
  const productNames = ['Web Development', 'Consulting', 'Design', 'Support', 'Training'];
  for (let i = 0; i < 5; i++) {
    const product = await prisma.product.create({
      data: {
        name: productNames[i],
        description: `${productNames[i]} services`,
        unitPrice: (i + 1) * 50,
        organizationId: org1.id
      }
    });
    products.push(product);
  }

  // Create invoices for org1
  for (let i = 0; i < 20; i++) {
    const client = clients[i % 10];
    const statuses = ['DRAFT', 'SENT', 'PAID', 'PAID', 'SENT'];
    const status = statuses[i % 5];

    const items = [{
      productId: products[i % 5].id,
      description: products[i % 5].name,
      quantity: (i % 5) + 1,
      unitPrice: Number(products[i % 5].unitPrice)
    }];

    const subtotal = items.reduce((sum, item) => sum + item.quantity * item.unitPrice, 0);
    const taxAmount = subtotal * 0.1;
    const total = subtotal + taxAmount;

    const invoice = await prisma.invoice.create({
      data: {
        invoiceNumber: `INV-2026-${String(i + 1).padStart(4, '0')}`,
        clientId: client.id,
        organizationId: org1.id,
        createdBy: user1.id,
        status,
        subtotal,
        taxRate: 10,
        taxAmount,
        total,
        sentAt: status !== 'DRAFT' ? new Date() : null,
        paidAt: status === 'PAID' ? new Date() : null
      }
    });

    // Create invoice items
    await prisma.invoiceItem.create({
      data: {
        invoiceId: invoice.id,
        productId: items[0].productId,
        description: items[0].description,
        quantity: items[0].quantity,
        unitPrice: items[0].unitPrice,
        amount: items[0].quantity * items[0].unitPrice
      }
    });

    // Create payments for paid invoices
    if (status === 'PAID') {
      await prisma.payment.create({
        data: {
          invoiceId: invoice.id,
          organizationId: org1.id,
          recordedBy: user2.id,
          amount: total,
          paymentMethod: 'BANK_TRANSFER',
          status: 'COMPLETED'
        }
      });
    }
  }

  // Create activity logs
  for (let i = 0; i < 20; i++) {
    await prisma.activityLog.create({
      data: {
        organizationId: org1.id,
        userId: i % 2 === 0 ? user1.id : user2.id,
        action: ['CREATE', 'UPDATE', 'DELETE'][i % 3],
        entityType: ['CLIENT', 'INVOICE', 'PRODUCT'][i % 3],
        entityId: clients[i % 10].id
      }
    });
  }

  console.log('Seed complete!');
  console.log(`  Organizations: 2`);
  console.log(`  Users: 3`);
  console.log(`  Clients: 10`);
  console.log(`  Products: 5`);
  console.log(`  Invoices: 20`);
  console.log(`  Activity logs: 20`);
}

main()
  .catch(console.error)
  .finally(() => prisma.$disconnect());
