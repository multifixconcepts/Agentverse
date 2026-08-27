import { useState, useEffect } from 'react';
import api from '../utils/api';

export default function Recurring() {
  const [schedules, setSchedules] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [clients, setClients] = useState([]);
  const [form, setForm] = useState({ clientId: '', name: '', items: [{ description: '', quantity: 1, unitPrice: 0 }], frequency: 'MONTHLY', startDate: '' });

  useEffect(() => {
    Promise.all([api.get('/recurring'), api.get('/clients?limit=100')])
      .then(([s, c]) => { setSchedules(s.data.data); setClients(c.data.data); })
      .finally(() => setLoading(false));
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    await api.post('/recurring', { ...form, startDate: new Date(form.startDate).toISOString() });
    setShowForm(false);
    const res = await api.get('/recurring');
    setSchedules(res.data.data);
  };

  const handleDeactivate = async (id) => {
    if (!confirm('Deactivate this schedule?')) return;
    await api.delete('/recurring/' + id);
    setSchedules(schedules.filter(s => s.id !== id));
  };

  if (loading) return <div className="text-center py-8">Loading...</div>;

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Recurring Invoices</h1>
        <button onClick={() => setShowForm(true)} className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">New Schedule</button>
      </div>
      {showForm && (
        <div className="bg-white rounded-lg shadow p-6 mb-6">
          <h2 className="text-lg font-semibold mb-4">Create Recurring Schedule</h2>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <select value={form.clientId} onChange={e => setForm({...form, clientId: e.target.value})} required className="border rounded px-3 py-2">
                <option value="">Select Client</option>
                {clients.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select>
              <input type="text" placeholder="Schedule Name" value={form.name} onChange={e => setForm({...form, name: e.target.value})} required className="border rounded px-3 py-2" />
              <select value={form.frequency} onChange={e => setForm({...form, frequency: e.target.value})} className="border rounded px-3 py-2">
                {['WEEKLY','BIWEEKLY','MONTHLY','QUARTERLY','YEARLY'].map(f => <option key={f} value={f}>{f}</option>)}
              </select>
              <input type="date" value={form.startDate} onChange={e => setForm({...form, startDate: e.target.value})} required className="border rounded px-3 py-2" />
            </div>
            <div className="flex space-x-2">
              <button type="submit" className="bg-green-600 text-white px-4 py-2 rounded">Create</button>
              <button type="button" onClick={() => setShowForm(false)} className="bg-gray-300 px-4 py-2 rounded">Cancel</button>
            </div>
          </form>
        </div>
      )}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50"><tr><th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th><th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th><th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Frequency</th><th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Next Run</th><th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th></tr></thead>
          <tbody className="divide-y divide-gray-200">
            {schedules.map(s => (
              <tr key={s.id}><td className="px-6 py-4 whitespace-nowrap">{s.name}</td><td className="px-6 py-4 whitespace-nowrap">{s.client?.name}</td><td className="px-6 py-4 whitespace-nowrap">{s.frequency}</td><td className="px-6 py-4 whitespace-nowrap">{s.nextRunDate ? new Date(s.nextRunDate).toLocaleDateString() : '-'}</td><td className="px-6 py-4 whitespace-nowrap"><button onClick={() => handleDeactivate(s.id)} className="text-red-600 hover:text-red-800 text-sm">Deactivate</button></td></tr>
            ))}
            {schedules.length === 0 && <tr><td colSpan={5} className="px-6 py-8 text-center text-gray-500">No recurring schedules</td></tr>}
          </tbody>
        </table>
      </div>
    </div>
  );
}
