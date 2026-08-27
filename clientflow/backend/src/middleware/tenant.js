export const tenantScope = (req, res, next) => {
  if (!req.user) {
    return res.status(401).json({
      success: false,
      error: { code: 'UNAUTHORIZED', message: 'Not authenticated' }
    });
  }

  // Platform admins can access any tenant
  if (req.user.role === 'PLATFORM_ADMIN') {
    req.tenantFilter = {};
    return next();
  }

  // All other users are scoped to their organization
  req.tenantFilter = { organizationId: req.user.organizationId };
  next();
};
