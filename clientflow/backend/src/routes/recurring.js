import { Router } from 'express';
import { z } from 'zod';
import { prisma } from '../index.js';
import { authorize } from '../middleware/auth.js';
import { validate } from '../middleware/validate.js';

const router = Router();

const createRecurringSchema = z.object({
  clientId: z.string().uuid(),
  name: z.string().min(1),
  items: z.array(z.object({
    productId: z.string().uuid().optional(),
    description: z.string().min(1),
    quantity: z.number().int().positive(),
    unitPrice: z.number().positive()
  })).min(1),
  taxRate: z.number().min(0).max(100).default(0),
  frequency: z.enum(['WEEKLY', 'BIWEEKLY', 'MONTHLY', 'QUARTERLY', 'YEARLY']),
  dayOfMonth: z.number().int().min(1).max(28).optional(),
  startDate: z.string().datetime(),
  endDate: z.string().datetime().optional()
});

// List recurring invoice schedules
router.get('/', async (req, res, next) => {
  try {
    const { page = 1, limit = 20 } = req.query;
    const skip = (Math.max(1, parseInt(page)) - 1) * Math.min(100, parseInt(limit));

    const [schedules, total] = await Promise.all([
      prisma.recurringSchedule.findMany({
        where: { organizationId: req.organizationId, active: true },
        skip,
        take: Math.min(100, parseInt(limit)),
        include: { client: { select: { id: true, name: true } } },
        orderBy: { nextRunDate: 'asc' }
      }),
      prisma.recurringSchedule.count({
        where: { organizationId: req.organizationId, active: true }
      })
    ]);

    res.json({
      success: true,
      data: schedules,
      meta: { page: parseInt(page), limit: parseInt(limit), total, totalPages: Math.ceil(total / parseInt(limit)) }
    });
  } catch (error) {
    next(error);
  }
});

// Create recurring schedule
router.post('/', authorize('ORG_ADMIN', 'PLATFORM_ADMIN'), validate(createRecurringSchema), async (req, res, next) => {
  try {
    const { clientId, name, items, taxRate, frequency, dayOfMonth, startDate, endDate } = req.body;

    const schedule = await prisma.recurringSchedule.create({
      data: {
        organizationId: req.organizationId,
        clientId,
        name,
        items: JSON.stringify(items),
        taxRate: taxRate || 0,
        frequency,
        dayOfMonth,
        startDate: new Date(startDate),
        endDate: endDate ? new Date(endDate) : null,
        nextRunDate: new Date(startDate),
        active: true
      }
    });

    await prisma.activityLog.create({
      data: {
        organizationId: req.organizationId,
        userId: req.user.id,
        action: 'CREATE',
        entityType: 'RECURRING_SCHEDULE',
        entityId: schedule.id,
        details: JSON.stringify({ frequency, name })
      }
    });

    res.status(201).json({ success: true, data: schedule });
  } catch (error) {
    next(error);
  }
});

// Deactivate recurring schedule
router.delete('/:id', authorize('ORG_ADMIN', 'PLATFORM_ADMIN'), async (req, res, next) => {
  try {
    const schedule = await prisma.recurringSchedule.findFirst({
      where: { id: req.params.id, organizationId: req.organizationId, active: true }
    });

    if (!schedule) {
      return res.status(404).json({ success: false, error: { code: 'NOT_FOUND', message: 'Schedule not found' } });
    }

    await prisma.recurringSchedule.update({
      where: { id: req.params.id },
      data: { active: false }
    });

    res.json({ success: true, data: { message: 'Schedule deactivated' } });
  } catch (error) {
    next(error);
  }
});

export default router;
