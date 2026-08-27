import { Router } from 'express';
import { prisma } from '../index.js';

const router = Router();

// Financial summary
router.get('/summary', async (req, res, next) => {
  try {
    const orgId = req.organizationId;
    const now = new Date();
    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
    const startOfYear = new Date(now.getFullYear(), 0, 1);

    const [
      totalClients,
      outstandingInvoices,
      monthlyRevenue,
      yearlyRevenue,
      recentActivity
    ] = await Promise.all([
      prisma.client.count({
        where: { organizationId: orgId, deletedAt: null }
      }),
      prisma.invoice.aggregate({
        where: {
          organizationId: orgId,
          status: { in: ['SENT', 'OVERDUE'] },
          deletedAt: null
        },
        _sum: { total: true },
        _count: true
      }),
      prisma.payment.aggregate({
        where: {
          organizationId: orgId,
          status: 'COMPLETED',
          recordedAt: { gte: startOfMonth }
        },
        _sum: { amount: true }
      }),
      prisma.payment.aggregate({
        where: {
          organizationId: orgId,
          status: 'COMPLETED',
          recordedAt: { gte: startOfYear }
        },
        _sum: { amount: true }
      }),
      prisma.activityLog.findMany({
        where: { organizationId: orgId },
        orderBy: { createdAt: 'desc' },
        take: 20,
        include: { user: { select: { id: true, name: true } } }
      })
    ]);

    res.json({
      success: true,
      data: {
        totalClients,
        outstandingAmount: Number(outstandingInvoices._sum.total || 0),
        outstandingCount: outstandingInvoices._count,
        monthlyRevenue: Number(monthlyRevenue._sum.amount || 0),
        yearlyRevenue: Number(yearlyRevenue._sum.amount || 0),
        recentActivity
      }
    });
  } catch (error) {
    next(error);
  }
});

// Recent activity
router.get('/activity', async (req, res, next) => {
  try {
    const activity = await prisma.activityLog.findMany({
      where: { organizationId: req.organizationId },
      orderBy: { createdAt: 'desc' },
      take: 50,
      include: { user: { select: { id: true, name: true } } }
    });

    res.json({ success: true, data: activity });
  } catch (error) {
    next(error);
  }
});

export default router;
