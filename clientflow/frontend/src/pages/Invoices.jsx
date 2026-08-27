import { useState, useEffect } from 'react';
import api from '../utils/api.js';

export default function Invoices() {
  const [invoices, setInvoices] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get('/invoices').then(res => setInvoices(res.data.data)).finally(() => setLoading(false));
  }, []);

  const getStatusColor = (status) => {
    const colors = { DRAFT: 'bg-gray-100', SENT: 'bg-blue-100', PAID: 'bg-green-100', OVERDUE: 'bg-red-100', CANCELLED: 'bg-gray-200' };
    return colors[status] || 'bg-gray-100';
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-white shadow p-4 flex justify-between items-center">
        <h1 className="text-xl font-bold">Invoices</h1>
      </nav>
      <main className="p-8">
        {loading ? <p>Loading...</p> : (
          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="p-3 text-left">Invoice #</th>
                  <th className="p-3 text-left">Client</th>
                  <th className="p-3 text-left">Total</th>
                  <th className="p-3 text-left">Status</th>
                  <th className="p-3 text-left">Date</th>
                </tr>
              </thead>
              <tbody>
                {invoices.map(invoice => (
                  <tr key={invoice.id} className="border-t">
                    <td className="p-3 font-mono">{invoice.invoiceNumber}</td>
                    <td className="p-3">{invoice.client?.name}</td>
                    <td className="p-3">${Number(invoice.total).toLocaleString()}</td>
                    <td className="p-3">
                      <span className={`px-2 py-1 rounded text-sm ${getStatusColor(invoice.status)}`}>
                        {invoice.status}
                      </span>
                    </td>
                    <td className="p-3">{new Date(invoice.createdAt).toLocaleDateString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </main>
    </div>
  );
}
