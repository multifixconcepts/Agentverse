import { Router } from 'express';
import bcrypt from 'bcryptjs';
import { z } from 'zod';
import { prisma } from '../index.js';
import { authorize } from '../middleware/auth.js';
import { validate } from '../middleware/validate.js';

const router = Router();

const createUserSchema = z.object({
  email: z.string().email(),
  password: z.string().min(8),
  name: z.string().min(1),
  role: z.enum(['ORG_ADMIN', 'STAFF']).optional()
});

const updateUserSchema = z.object({
  email: z.string().email().optional(),
  name: z.string().min(1).optional(),
  role: z.enum(['ORG_ADMIN', 'STAFF']).optional()
});

// List users (org admins only)
router.get('/', authorize('ORG_ADMIN', 'PLATFORM_ADMIN'), async (req, res, next) => {
  try {
    const where = req.user.role === 'PLATFORM_ADMIN'
      ? {}
      : { organizationId: req.organizationId };

    const users = await prisma.user.findMany({
      where: { ...where, deletedAt: null },
      select: { id: true, email: true, name: true, role: true, createdAt: true }
    });

    res.json({ success: true, data: users });
  } catch (error) {
    next(error);
  }
});

// Create user
router.post('/', authorize('ORG_ADMIN', 'PLATFORM_ADMIN'), validate(createUserSchema), async (req, res, next) => {
  try {
    const { email, password, name, role } = req.body;
    const passwordHash = await bcrypt.hash(password, 12);

    const user = await prisma.user.create({
      data: {
        email,
        passwordHash,
        name,
        role: role || 'STAFF',
        organizationId: req.organizationId
      }
    });

    res.status(201).json({
      success: true,
      data: { id: user.id, email: user.email, name: user.name, role: user.role }
    });
  } catch (error) {
    next(error);
  }
});

// Get user
router.get('/:id', authorize('ORG_ADMIN', 'PLATFORM_ADMIN'), async (req, res, next) => {
  try {
    const user = await prisma.user.findFirst({
      where: {
        id: req.params.id,
        deletedAt: null,
        ...(req.user.role !== 'PLATFORM_ADMIN' && { organizationId: req.organizationId })
      },
      select: { id: true, email: true, name: true, role: true, createdAt: true }
    });

    if (!user) {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'User not found' }
      });
    }

    res.json({ success: true, data: user });
  } catch (error) {
    next(error);
  }
});

// Update user
router.put('/:id', authorize('ORG_ADMIN', 'PLATFORM_ADMIN'), validate(updateUserSchema), async (req, res, next) => {
  try {
    const user = await prisma.user.findFirst({
      where: {
        id: req.params.id,
        deletedAt: null,
        ...(req.user.role !== 'PLATFORM_ADMIN' && { organizationId: req.organizationId })
      }
    });

    if (!user) {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'User not found' }
      });
    }

    const updated = await prisma.user.update({
      where: { id: req.params.id },
      data: req.body,
      select: { id: true, email: true, name: true, role: true }
    });

    res.json({ success: true, data: updated });
  } catch (error) {
    next(error);
  }
});

// Delete user (soft delete)
router.delete('/:id', authorize('ORG_ADMIN', 'PLATFORM_ADMIN'), async (req, res, next) => {
  try {
    const user = await prisma.user.findFirst({
      where: {
        id: req.params.id,
        deletedAt: null,
        ...(req.user.role !== 'PLATFORM_ADMIN' && { organizationId: req.organizationId })
      }
    });

    if (!user) {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'User not found' }
      });
    }

    await prisma.user.update({
      where: { id: req.params.id },
      data: { deletedAt: new Date() }
    });

    res.json({ success: true, data: { message: 'User deleted' } });
  } catch (error) {
    next(error);
  }
});

export default router;
