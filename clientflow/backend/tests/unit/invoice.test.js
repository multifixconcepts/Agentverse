import { describe, it, expect } from 'vitest';

// Unit tests for invoice calculation logic

const calculateInvoiceTotal = (items, taxRate = 0) => {
  const subtotal = items.reduce((sum, item) => sum + (item.quantity * item.unitPrice), 0);
  const taxAmount = subtotal * (taxRate / 100);
  const total = subtotal + taxAmount;
  return { subtotal, taxAmount, total };
};

const generateInvoiceNumber = (count, year) => {
  return `INV-${year}-${String(count + 1).padStart(4, '0')}`;
};

describe('Invoice Calculations', () => {
  it('should calculate subtotal correctly', () => {
    const items = [
      { quantity: 10, unitPrice: 100 },
      { quantity: 5, unitPrice: 200 }
    ];
    const result = calculateInvoiceTotal(items);
    expect(result.subtotal).toBe(2000);
  });

  it('should calculate tax correctly', () => {
    const items = [{ quantity: 1, unitPrice: 1000 }];
    const result = calculateInvoiceTotal(items, 10);
    expect(result.taxAmount).toBe(100);
    expect(result.total).toBe(1100);
  });

  it('should handle zero tax', () => {
    const items = [{ quantity: 1, unitPrice: 500 }];
    const result = calculateInvoiceTotal(items, 0);
    expect(result.taxAmount).toBe(0);
    expect(result.total).toBe(500);
  });

  it('should handle single item', () => {
    const items = [{ quantity: 1, unitPrice: 99.99 }];
    const result = calculateInvoiceTotal(items);
    expect(result.subtotal).toBeCloseTo(99.99);
  });

  it('should handle multiple items with different quantities', () => {
    const items = [
      { quantity: 3, unitPrice: 10 },
      { quantity: 2, unitPrice: 25 },
      { quantity: 1, unitPrice: 100 }
    ];
    const result = calculateInvoiceTotal(items, 5);
    expect(result.subtotal).toBe(180);
    expect(result.taxAmount).toBe(9);
    expect(result.total).toBe(189);
  });
});

describe('Invoice Number Generation', () => {
  it('should generate correct format', () => {
    const result = generateInvoiceNumber(0, 2026);
    expect(result).toBe('INV-2026-0001');
  });

  it('should pad with zeros', () => {
    const result = generateInvoiceNumber(99, 2026);
    expect(result).toBe('INV-2026-0100');
  });

  it('should handle large numbers', () => {
    const result = generateInvoiceNumber(9999, 2026);
    expect(result).toBe('INV-2026-10000');
  });
});
