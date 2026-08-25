import { Router } from 'express';
import { z } from 'zod';
import { prisma } from '../index.js';
import { validate } from '../middleware/validate.js';

const router = Router();

const createPaymentSchema = z.object({
  invoiceId: z.string().uuid(),
  amount: z.number().positive(),
  paymentMethod: z.enum(['CASH', 'CHECK', 'BANK_TRANSFER', 'CARD']),
  reference: z.string().optional(),
  notes: z.string().optional()
});

// List payments
router.get('/', async (req, res, next) => {
  try {
    const { page = 1, limit = 20, sort = 'recordedAt', order = 'desc', invoiceId } = req.query;
    const skip = (parseInt(page) - 1) * parseInt(limit);

    const where = {
      organizationId: req.organizationId,
      ...(invoiceId && { invoiceId })
    };

    const [payments, total] = await Promise.all([
      prisma.payment.findMany({
        where,
        skip,
        take: parseInt(limit),
        orderBy: { [sort]: order },
        include: {
          invoice: { select: { id: true, invoiceNumber: true, total: true } },
          recorder: { select: { id: true, name: true } }
        }
      }),
      prisma.payment.count({ where })
    ]);

    res.json({
      success: true,
      data: payments,
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

// Create payment
router.post('/', validate(createPaymentSchema), async (req, res, next) => {
  try {
    const { invoiceId, amount, paymentMethod, reference, notes } = req.body;

    // Verify invoice belongs to same org
    const invoice = await prisma.invoice.findFirst({
      where: { id: invoiceId, organizationId: req.organizationId, deletedAt: null }
    });
    if (!invoice) {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'Invoice not found' }
      });
    }

    // Check amount doesn't exceed invoice total
    const existingPayments = await prisma.payment.aggregate({
      where: { invoiceId, status: 'COMPLETED' },
      _sum: { amount: true }
    });
    const totalPaid = Number(existingPayments._sum.amount || 0);
    if (totalPaid + amount > Number(invoice.total)) {
      return res.status(422).json({
        success: false,
        error: { code: 'VALIDATION_ERROR', message: 'Payment amount exceeds invoice balance' }
      });
    }

    const payment = await prisma.payment.create({
      data: {
        invoiceId,
        organizationId: req.organizationId,
        recordedBy: req.user.id,
        amount,
        paymentMethod,
        reference,
        notes,
        status: 'COMPLETED'
      }
    });

    // Update invoice status if fully paid
    if (totalPaid + amount >= Number(invoice.total)) {
      await prisma.invoice.update({
        where: { id: invoiceId },
        data: { status: 'PAID', paidAt: new Date() }
      });
    }

    res.status(201).json({ success: true, data: payment });
  } catch (error) {
    next(error);
  }
});

// Get payment
router.get('/:id', async (req, res, next) => {
  try {
    const payment = await prisma.payment.findFirst({
      where: {
        id: req.params.id,
        organizationId: req.organizationId
      },
      include: {
        invoice: { select: { id: true, invoiceNumber: true, total: true } },
        recorder: { select: { id: true, name: true } }
      }
    });

    if (!payment) {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'Payment not found' }
      });
    }

    res.json({ success: true, data: payment });
  } catch (error) {
    next(error);
  }
});

export default router;
