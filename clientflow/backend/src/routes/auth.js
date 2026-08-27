import { Router } from 'express';
import bcrypt from 'bcryptjs';
import { z } from 'zod';
import { prisma } from '../index.js';
import { generateToken, authenticate } from '../middleware/auth.js';
import { validate } from '../middleware/validate.js';

const router = Router();

const registerSchema = z.object({
  email: z.string().email(),
  password: z.string().min(8),
  name: z.string().min(1),
  organizationName: z.string().min(1)
});

const loginSchema = z.object({
  email: z.string().email(),
  password: z.string()
});

// Register
router.post('/register', validate(registerSchema), async (req, res, next) => {
  try {
    const { email, password, name, organizationName } = req.body;

    // Create organization
    const slug = organizationName.toLowerCase().replace(/[^a-z0-9]+/g, '-');
    const org = await prisma.organization.create({
      data: { name: organizationName, slug }
    });

    // Create user
    const passwordHash = await bcrypt.hash(password, 12);
    const user = await prisma.user.create({
      data: {
        email,
        passwordHash,
        name,
        role: 'ORG_ADMIN',
        organizationId: org.id
      }
    });

    const token = generateToken(user.id);

    res.status(201).json({
      success: true,
      data: {
        user: { id: user.id, email: user.email, name: user.name, role: user.role, organizationId: org.id },
        token
      }
    });
  } catch (error) {
    next(error);
  }
});

// Login
router.post('/login', validate(loginSchema), async (req, res, next) => {
  try {
    const { email, password } = req.body;

    const user = await prisma.user.findFirst({
      where: { email, deletedAt: null },
      include: { organization: true }
    });

    if (!user) {
      return res.status(401).json({
        success: false,
        error: { code: 'UNAUTHORIZED', message: 'Invalid credentials' }
      });
    }

    const valid = await bcrypt.compare(password, user.passwordHash);
    if (!valid) {
      return res.status(401).json({
        success: false,
        error: { code: 'UNAUTHORIZED', message: 'Invalid credentials' }
      });
    }

    const token = generateToken(user.id);

    res.json({
      success: true,
      data: {
        user: { id: user.id, email: user.email, name: user.name, role: user.role, organizationId: user.organizationId },
        token
      }
    });
  } catch (error) {
    next(error);
  }
});

// Logout
router.post('/logout', async (req, res, next) => {
  try {
    const authHeader = req.headers.authorization;
    if (authHeader) {
      const token = authHeader.split(' ')[1];
      const decoded = JSON.parse(Buffer.from(token.split('.')[1], 'base64').toString());
      await prisma.tokenBlacklist.create({
        data: {
          token,
          expiresAt: new Date(decoded.exp * 1000)
        }
      });
    }
    res.json({ success: true, data: { message: 'Logged out' } });
  } catch (error) {
    next(error);
  }
});

// Password reset request
router.post('/password-reset', async (req, res, next) => {
  try {
    const { email } = req.body;
    // In production, send email with reset link
    // For now, just acknowledge
    res.json({ success: true, data: { message: 'If account exists, reset email sent' } });
  } catch (error) {
    next(error);
  }
});

// Get current user
router.get('/me', authenticate, (req, res) => {
  res.json({
    success: true,
    data: { id: req.user.id, email: req.user.email, name: req.user.name, role: req.user.role, organizationId: req.user.organizationId }
  });
});

export default router;
