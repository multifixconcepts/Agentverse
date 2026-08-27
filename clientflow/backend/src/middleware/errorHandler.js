export const errorHandler = (err, req, res, next) => {
  console.error('Error:', err);

  if (err.type === 'entity.parse.failed') {
    return res.status(400).json({
      success: false,
      error: { code: 'BAD_REQUEST', message: 'Invalid JSON in request body' }
    });
  }

  if (err.statusCode && err.statusCode < 500) {
    return res.status(err.statusCode).json({
      success: false,
      error: { code: 'BAD_REQUEST', message: err.message }
    });
  }

  if (err.name === 'PrismaClientKnownRequestError') {
    if (err.code === 'P2002') {
      return res.status(409).json({
        success: false,
        error: { code: 'CONFLICT', message: 'Resource already exists' }
      });
    }
    if (err.code === 'P2025') {
      return res.status(404).json({
        success: false,
        error: { code: 'NOT_FOUND', message: 'Resource not found' }
      });
    }
  }

  if (err.name === 'PrismaClientUnknownRequestError' || err.name === 'PrismaClientValidationError') {
    return res.status(400).json({
      success: false,
      error: { code: 'BAD_REQUEST', message: 'Invalid request parameters' }
    });
  }

  if (err.name === 'ZodError') {
    return res.status(422).json({
      success: false,
      error: {
        code: 'VALIDATION_ERROR',
        message: 'Invalid input',
        details: err.errors
      }
    });
  }

  res.status(500).json({
    success: false,
    error: { code: 'INTERNAL_ERROR', message: 'Internal server error' }
  });
};
