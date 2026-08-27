import { Router } from 'express';
import { z } from 'zod';
import { prisma } from '../index.js';
import { authorize } from '../middleware/auth.js';
import { validate } from '../middleware/validate.js';

const router = Router();

const createInvoiceSchema = z.object({
  clientId: z.string().uuid(),
  items: z.array(z.object({
    productId: z.string().uuid().optional(),
    description: z.string().min(1),
    quantity: z.number().int().positive(),
    unitPrice: z.number().positive()
  })).min(1),
  taxRate: z.number().min(0).max(100).optional(),
  notes: z.string().optional(),
  dueDate: z.string().datetime().optional()
});

// List invoices
router.get('/', async (req, res, next) => {
  try {
    const { page = 1, limit = 20, sort = 'createdAt', order = 'desc', status, clientId } = req.query;
    const skip = (parseInt(page) - 1) * parseInt(limit);

    const where = {
      deletedAt: null,
      organizationId: req.organizationId,
      ...(status && { status }),
      ...(clientId && { clientId })
    };

    const [invoices, total] = await Promise.all([
      prisma.invoice.findMany({
        where,
        skip,
        take: parseInt(limit),
        orderBy: { [sort]: order },
        include: {
          client: { select: { id: true, name: true, email: true } },
          items: true,
          payments: true
        }
      }),
      prisma.invoice.count({ where })
    ]);

    res.json({
      success: true,
      data: invoices,
      meta: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        totalPages: Math.ceil(total / parseInt(limit))
      }
    });
  } catch (error) {
    next(error);
  }
});

// Create invoice
router.post('/', validate(createInvoiceSchema), async (req, res, next) => {
  try {
    const { clientId, items, taxRate = 0, notes, dueDate } = req.body;

    // Verify client belongs to same org
    const client = await prisma.client.findFirst({
      where: { id: clientId, organizationId: req.organizationId, deletedAt: null }
    });
    if (!client) {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'Client not found' }
      });
    }

    // Calculate totals
    const subtotal = items.reduce((sum, item) => sum + (item.quantity * item.unitPrice), 0);
    const taxAmount = subtotal * (taxRate / 100);
    const total = subtotal + taxAmount;

    // Generate invoice number
    const count = await prisma.invoice.count({
      where: { organizationId: req.organizationId }
    });
    const invoiceNumber = `INV-${new Date().getFullYear()}-${String(count + 1).padStart(4, '0')}`;

    const invoice = await prisma.invoice.create({
      data: {
        invoiceNumber,
        clientId,
        organizationId: req.organizationId,
        createdBy: req.user.id,
        subtotal,
        taxRate,
        taxAmount,
        total,
        notes,
        dueDate: dueDate ? new Date(dueDate) : null,
        items: {
          create: items.map(item => ({
            productId: item.productId,
            description: item.description,
            quantity: item.quantity,
            unitPrice: item.unitPrice,
            amount: item.quantity * item.unitPrice
          }))
        }
      },
      include: { items: true }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        organizationId: req.organizationId,
        userId: req.user.id,
        action: 'CREATE',
        entityType: 'INVOICE',
        entityId: invoice.id
      }
    });

    res.status(201).json({ success: true, data: invoice });
  } catch (error) {
    next(error);
  }
});

// Get invoice
router.get('/:id', async (req, res, next) => {
  try {
    const invoice = await prisma.invoice.findFirst({
      where: {
        id: req.params.id,
        deletedAt: null,
        organizationId: req.organizationId
      },
      include: {
        client: { select: { id: true, name: true, email: true } },
        items: true,
        payments: true,
        creator: { select: { id: true, name: true } }
      }
    });

    if (!invoice) {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'Invoice not found' }
      });
    }

    res.json({ success: true, data: invoice });
  } catch (error) {
    next(error);
  }
});

// Send invoice
router.post('/:id/send', async (req, res, next) => {
  try {
    const invoice = await prisma.invoice.findFirst({
      where: {
        id: req.params.id,
        status: 'DRAFT',
        deletedAt: null,
        organizationId: req.organizationId
      }
    });

    if (!invoice) {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'Invoice not found or not in DRAFT status' }
      });
    }

    const updated = await prisma.invoice.update({
      where: { id: req.params.id },
      data: { status: 'SENT', sentAt: new Date() }
    });

    res.json({ success: true, data: updated });
  } catch (error) {
    next(error);
  }
});

// Mark as paid
router.post('/:id/pay', async (req, res, next) => {
  try {
    const invoice = await prisma.invoice.findFirst({
      where: {
        id: req.params.id,
        status: { in: ['SENT', 'OVERDUE'] },
        deletedAt: null,
        organizationId: req.organizationId
      }
    });

    if (!invoice) {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'Invoice not found or not payable' }
      });
    }

    const updated = await prisma.invoice.update({
      where: { id: req.params.id },
      data: { status: 'PAID', paidAt: new Date() }
    });

    // Create payment record
    await prisma.payment.create({
      data: {
        invoiceId: req.params.id,
        organizationId: req.organizationId,
        recordedBy: req.user.id,
        amount: invoice.total,
        paymentMethod: 'BANK_TRANSFER',
        status: 'COMPLETED'
      }
    });

    res.json({ success: true, data: updated });
  } catch (error) {
    next(error);
  }
});

// Cancel invoice
router.post('/:id/cancel', authorize('ORG_ADMIN', 'PLATFORM_ADMIN'), async (req, res, next) => {
  try {
    const invoice = await prisma.invoice.findFirst({
      where: {
        id: req.params.id,
        status: { in: ['DRAFT', 'SENT'] },
        deletedAt: null,
        organizationId: req.organizationId
      }
    });

    if (!invoice) {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'Invoice not found or cannot be cancelled' }
      });
    }

    const updated = await prisma.invoice.update({
      where: { id: req.params.id },
      data: { status: 'CANCELLED' }
    });

    res.json({ success: true, data: updated });
  } catch (error) {
    next(error);
  }
});

export default router;
