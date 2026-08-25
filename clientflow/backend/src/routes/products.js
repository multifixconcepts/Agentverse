import { Router } from 'express';
import { z } from 'zod';
import { prisma } from '../index.js';
import { authorize } from '../middleware/auth.js';
import { validate } from '../middleware/validate.js';

const router = Router();

const createProductSchema = z.object({
  name: z.string().min(1),
  description: z.string().optional(),
  unitPrice: z.number().positive(),
  active: z.boolean().optional()
});

const updateProductSchema = z.object({
  name: z.string().min(1).optional(),
  description: z.string().optional(),
  unitPrice: z.number().positive().optional(),
  active: z.boolean().optional()
});

// List products
router.get('/', async (req, res, next) => {
  try {
    const { page = 1, limit = 20, sort = 'name', order = 'asc', search } = req.query;
    const skip = (parseInt(page) - 1) * parseInt(limit);

    const where = {
      organizationId: req.organizationId,
      ...(search && {
        OR: [
          { name: { contains: search, mode: 'insensitive' } },
          { description: { contains: search, mode: 'insensitive' } }
        ]
      })
    };

    const [products, total] = await Promise.all([
      prisma.product.findMany({
        where,
        skip,
        take: parseInt(limit),
        orderBy: { [sort]: order }
      }),
      prisma.product.count({ where })
    ]);

    res.json({
      success: true,
      data: products,
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

// Create product (admin only)
router.post('/', authorize('ORG_ADMIN', 'PLATFORM_ADMIN'), validate(createProductSchema), async (req, res, next) => {
  try {
    const product = await prisma.product.create({
      data: {
        ...req.body,
        organizationId: req.organizationId
      }
    });

    res.status(201).json({ success: true, data: product });
  } catch (error) {
    next(error);
  }
});

// Get product
router.get('/:id', async (req, res, next) => {
  try {
    const product = await prisma.product.findFirst({
      where: {
        id: req.params.id,
        organizationId: req.organizationId
      }
    });

    if (!product) {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'Product not found' }
      });
    }

    res.json({ success: true, data: product });
  } catch (error) {
    next(error);
  }
});

// Update product (admin only)
router.put('/:id', authorize('ORG_ADMIN', 'PLATFORM_ADMIN'), validate(updateProductSchema), async (req, res, next) => {
  try {
    const product = await prisma.product.findFirst({
      where: {
        id: req.params.id,
        organizationId: req.organizationId
      }
    });

    if (!product) {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'Product not found' }
      });
    }

    const updated = await prisma.product.update({
      where: { id: req.params.id },
      data: req.body
    });

    res.json({ success: true, data: updated });
  } catch (error) {
    next(error);
  }
});

// Delete product (admin only)
router.delete('/:id', authorize('ORG_ADMIN', 'PLATFORM_ADMIN'), async (req, res, next) => {
  try {
    const product = await prisma.product.findFirst({
      where: {
        id: req.params.id,
        organizationId: req.organizationId
      }
    });

    if (!product) {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'Product not found' }
      });
    }

    await prisma.product.delete({ where: { id: req.params.id } });

    res.json({ success: true, data: { message: 'Product deleted' } });
  } catch (error) {
    next(error);
  }
});

export default router;
