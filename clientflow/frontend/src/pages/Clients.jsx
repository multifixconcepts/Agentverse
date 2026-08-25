import { useState, useEffect } from 'react';
import api from '../utils/api.js';

export default function Clients() {
  const [clients, setClients] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ name: '', email: '', phone: '', address: '' });

  useEffect(() => { loadClients(); }, []);

  const loadClients = () => {
    setLoading(true);
    api.get('/clients').then(res => setClients(res.data.data)).finally(() => setLoading(false));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    await api.post('/clients', form);
    setShowForm(false);
    setForm({ name: '', email: '', phone: '', address: '' });
    loadClients();
  };

  const handleDelete = async (id) => {
    if (confirm('Delete this client?')) {
      await api.delete(`/clients/${id}`);
      loadClients();
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-white shadow p-4 flex justify-between items-center">
        <h1 className="text-xl font-bold">Clients</h1>
        <button onClick={() => setShowForm(true)} className="bg-blue-600 text-white px-4 py-2 rounded">Add Client</button>
      </nav>
      <main className="p-8">
        {showForm && (
          <div className="bg-white p-6 rounded-lg shadow mb-6">
            <h3 className="text-lg font-bold mb-4">New Client</h3>
            <form onSubmit={handleSubmit} className="space-y-4">
              <input placeholder="Name" value={form.name} onChange={e => setForm({...form, name: e.target.value})} className="block w-full border rounded p-2" required />
              <input placeholder="Email" type="email" value={form.email} onChange={e => setForm({...form, email: e.target.value})} className="block w-full border rounded p-2" required />
              <input placeholder="Phone" value={form.phone} onChange={e => setForm({...form, phone: e.target.value})} className="block w-full border rounded p-2" />
              <input placeholder="Address" value={form.address} onChange={e => setForm({...form, address: e.target.value})} className="block w-full border rounded p-2" />
              <div className="flex gap-2">
                <button type="submit" className="bg-green-600 text-white px-4 py-2 rounded">Save</button>
                <button type="button" onClick={() => setShowForm(false)} className="bg-gray-300 px-4 py-2 rounded">Cancel</button>
              </div>
            </form>
          </div>
        )}
        {loading ? <p>Loading...</p> : (
          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr><th className="p-3 text-left">Name</th><th className="p-3 text-left">Email</th><th className="p-3 text-left">Phone</th><th className="p-3">Actions</th></tr>
              </thead>
              <tbody>
                {clients.map(client => (
                  <tr key={client.id} className="border-t">
                    <td className="p-3">{client.name}</td>
                    <td className="p-3">{client.email}</td>
                    <td className="p-3">{client.phone || '-'}</td>
                    <td className="p-3 text-center">
                      <button onClick={() => handleDelete(client.id)} className="text-red-600">Delete</button>
                    </td>
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
