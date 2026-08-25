import { useState, useEffect } from 'react';
import api from '../utils/api.js';

export default function Payments() {
  const [payments, setPayments] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get('/payments').then(res => setPayments(res.data.data)).finally(() => setLoading(false));
  }, []);

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-white shadow p-4 flex justify-between items-center">
        <h1 className="text-xl font-bold">Payments</h1>
      </nav>
      <main className="p-8">
        {loading ? <p>Loading...</p> : (
          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="p-3 text-left">Invoice</th>
                  <th className="p-3 text-left">Amount</th>
                  <th className="p-3 text-left">Method</th>
                  <th className="p-3 text-left">Status</th>
                  <th className="p-3 text-left">Date</th>
                </tr>
              </thead>
              <tbody>
                {payments.map(payment => (
                  <tr key={payment.id} className="border-t">
                    <td className="p-3">{payment.invoice?.invoiceNumber}</td>
                    <td className="p-3">${Number(payment.amount).toLocaleString()}</td>
                    <td className="p-3">{payment.paymentMethod}</td>
                    <td className="p-3">{payment.status}</td>
                    <td className="p-3">{new Date(payment.recordedAt).toLocaleDateString()}</td>
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
