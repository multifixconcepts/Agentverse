import { useState, useEffect } from 'react';
import api from '../utils/api.js';

export default function Products() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ name: '', description: '', unitPrice: '' });

  useEffect(() => { loadProducts(); }, []);

  const loadProducts = () => {
    setLoading(true);
    api.get('/products').then(res => setProducts(res.data.data)).finally(() => setLoading(false));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    await api.post('/products', { ...form, unitPrice: parseFloat(form.unitPrice) });
    setShowForm(false);
    setForm({ name: '', description: '', unitPrice: '' });
    loadProducts();
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-white shadow p-4 flex justify-between items-center">
        <h1 className="text-xl font-bold">Products</h1>
        <button onClick={() => setShowForm(true)} className="bg-blue-600 text-white px-4 py-2 rounded">Add Product</button>
      </nav>
      <main className="p-8">
        {showForm && (
          <div className="bg-white p-6 rounded-lg shadow mb-6">
            <h3 className="text-lg font-bold mb-4">New Product</h3>
            <form onSubmit={handleSubmit} className="space-y-4">
              <input placeholder="Name" value={form.name} onChange={e => setForm({...form, name: e.target.value})} className="block w-full border rounded p-2" required />
              <input placeholder="Description" value={form.description} onChange={e => setForm({...form, description: e.target.value})} className="block w-full border rounded p-2" />
              <input placeholder="Unit Price" type="number" step="0.01" value={form.unitPrice} onChange={e => setForm({...form, unitPrice: e.target.value})} className="block w-full border rounded p-2" required />
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
                <tr><th className="p-3 text-left">Name</th><th className="p-3 text-left">Description</th><th className="p-3 text-left">Price</th><th className="p-3 text-left">Active</th></tr>
              </thead>
              <tbody>
                {products.map(product => (
                  <tr key={product.id} className="border-t">
                    <td className="p-3">{product.name}</td>
                    <td className="p-3">{product.description || '-'}</td>
                    <td className="p-3">${Number(product.unitPrice).toFixed(2)}</td>
                    <td className="p-3">{product.active ? 'Yes' : 'No'}</td>
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
