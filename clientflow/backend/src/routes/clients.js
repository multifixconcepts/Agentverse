import { Router } from 'express';
import { z } from 'zod';
import { prisma } from '../index.js';
import { authorize } from '../middleware/auth.js';
import { validate } from '../middleware/validate.js';

const router = Router();

const createClientSchema = z.object({
  name: z.string().min(1),
  email: z.string().email(),
  phone: z.string().optional(),
  address: z.string().optional(),
  notes: z.string().optional(),
  assignedToId: z.string().uuid().optional()
});

const updateClientSchema = z.object({
  name: z.string().min(1).optional(),
  email: z.string().email().optional(),
  phone: z.string().optional(),
  address: z.string().optional(),
  notes: z.string().optional(),
  assignedToId: z.string().uuid().nullable().optional()
});

// List clients
router.get('/', async (req, res, next) => {
  try {
    const page = Math.max(1, parseInt(req.query.page) || 1);
    const limit = Math.max(1, Math.min(100, parseInt(req.query.limit) || 20));
    const sort = req.query.sort || 'name';
    const order = req.query.order || 'asc';
    const search = req.query.search;
    const skip = (page - 1) * limit;

    const where = {
      deletedAt: null,
      organizationId: req.organizationId,
      ...(search && {
        OR: [
          { name: { contains: search } },
          { email: { contains: search } }
        ]
      })
    };

    const [clients, total] = await Promise.all([
      prisma.client.findMany({
        where,
        skip,
        take: limit,
        orderBy: { [sort]: order },
        include: { assignedTo: { select: { id: true, name: true } } }
      }),
      prisma.client.count({ where })
    ]);

    res.json({
      success: true,
      data: clients,
      meta: {
        page,
        limit,
        total,
        totalPages: Math.ceil(total / limit)
      }
    });
  } catch (error) {
    next(error);
  }
});

// Create client
router.post('/', validate(createClientSchema), async (req, res, next) => {
  try {
    const client = await prisma.client.create({
      data: {
        ...req.body,
        organizationId: req.organizationId
      }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        organizationId: req.organizationId,
        userId: req.user.id,
        action: 'CREATE',
        entityType: 'CLIENT',
        entityId: client.id
      }
    });

    res.status(201).json({ success: true, data: client });
  } catch (error) {
    next(error);
  }
});

// Get client
router.get('/:id', async (req, res, next) => {
  try {
    const client = await prisma.client.findFirst({
      where: {
        id: req.params.id,
        deletedAt: null,
        organizationId: req.organizationId
      },
      include: { assignedTo: { select: { id: true, name: true } } }
    });

    if (!client) {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'Client not found' }
      });
    }

    res.json({ success: true, data: client });
  } catch (error) {
    next(error);
  }
});

// Update client
router.put('/:id', validate(updateClientSchema), async (req, res, next) => {
  try {
    const client = await prisma.client.findFirst({
      where: {
        id: req.params.id,
        deletedAt: null,
        organizationId: req.organizationId
      }
    });

    if (!client) {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'Client not found' }
      });
    }

    const updated = await prisma.client.update({
      where: { id: req.params.id },
      data: req.body
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        organizationId: req.organizationId,
        userId: req.user.id,
        action: 'UPDATE',
        entityType: 'CLIENT',
        entityId: client.id
      }
    });

    res.json({ success: true, data: updated });
  } catch (error) {
    next(error);
  }
});

// Delete client (soft delete)
router.delete('/:id', authorize('ORG_ADMIN', 'PLATFORM_ADMIN'), async (req, res, next) => {
  try {
    const client = await prisma.client.findFirst({
      where: {
        id: req.params.id,
        deletedAt: null,
        organizationId: req.organizationId
      }
    });

    if (!client) {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'Client not found' }
      });
    }

    // Check for active invoices
    const activeInvoices = await prisma.invoice.count({
      where: {
        clientId: req.params.id,
        status: { in: ['DRAFT', 'SENT'] },
        deletedAt: null
      }
    });

    if (activeInvoices > 0) {
      return res.status(409).json({
        success: false,
        error: { code: 'CONFLICT', message: 'Cannot delete client with active invoices' }
      });
    }

    await prisma.client.update({
      where: { id: req.params.id },
      data: { deletedAt: new Date() }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        organizationId: req.organizationId,
        userId: req.user.id,
        action: 'DELETE',
        entityType: 'CLIENT',
        entityId: client.id
      }
    });

    res.json({ success: true, data: { message: 'Client deleted' } });
  } catch (error) {
    next(error);
  }
});

export default router;
