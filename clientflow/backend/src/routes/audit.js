import { Router } from 'express';
import { prisma } from '../index.js';
import { authorize } from '../middleware/auth.js';

const router = Router();

// List audit entries (admin only)
router.get('/', authorize('ORG_ADMIN', 'PLATFORM_ADMIN'), async (req, res, next) => {
  try {
    const { page = 1, limit = 50, userId, action, entityType, startDate, endDate } = req.query;
    const skip = (parseInt(page) - 1) * parseInt(limit);

    const where = {
      organizationId: req.organizationId,
      ...(userId && { userId }),
      ...(action && { action }),
      ...(entityType && { entityType }),
      ...(startDate && { createdAt: { gte: new Date(startDate) } }),
      ...(endDate && { createdAt: { lte: new Date(endDate) } })
    };

    const [entries, total] = await Promise.all([
      prisma.activityLog.findMany({
        where,
        skip,
        take: parseInt(limit),
        orderBy: { createdAt: 'desc' },
        include: { user: { select: { id: true, name: true, email: true } } }
      }),
      prisma.activityLog.count({ where })
    ]);

    res.json({
      success: true,
      data: entries,
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

export default router;
