import { useState, useEffect } from 'react';
import { useAuth } from '../hooks/useAuth.jsx';
import api from '../utils/api.js';

export default function Dashboard() {
  const { user, logout } = useAuth();
  const [summary, setSummary] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get('/dashboard/summary')
      .then(res => setSummary(res.data.data))
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <div className="p-8">Loading...</div>;

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-white shadow p-4 flex justify-between items-center">
        <h1 className="text-xl font-bold">ClientFlow</h1>
        <div className="flex items-center gap-4">
          <span>{user?.name || user?.email}</span>
          <button onClick={logout} className="text-red-600">Logout</button>
        </div>
      </nav>
      <main className="p-8">
        <h2 className="text-2xl font-bold mb-6">Dashboard</h2>
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
          <div className="bg-white p-6 rounded-lg shadow">
            <div className="text-sm text-gray-500">Total Clients</div>
            <div className="text-3xl font-bold">{summary?.totalClients || 0}</div>
          </div>
          <div className="bg-white p-6 rounded-lg shadow">
            <div className="text-sm text-gray-500">Outstanding</div>
            <div className="text-3xl font-bold">${summary?.outstandingAmount?.toLocaleString() || 0}</div>
          </div>
          <div className="bg-white p-6 rounded-lg shadow">
            <div className="text-sm text-gray-500">Monthly Revenue</div>
            <div className="text-3xl font-bold">${summary?.monthlyRevenue?.toLocaleString() || 0}</div>
          </div>
          <div className="bg-white p-6 rounded-lg shadow">
            <div className="text-sm text-gray-500">Yearly Revenue</div>
            <div className="text-3xl font-bold">${summary?.yearlyRevenue?.toLocaleString() || 0}</div>
          </div>
        </div>
        <div className="bg-white p-6 rounded-lg shadow">
          <h3 className="text-lg font-bold mb-4">Recent Activity</h3>
          {summary?.recentActivity?.length > 0 ? (
            <ul className="space-y-2">
              {summary.recentActivity.map(activity => (
                <li key={activity.id} className="text-sm">
                  <span className="font-medium">{activity.user?.name}</span> {activity.action.toLowerCase()} {activity.entityType.toLowerCase()}
                  <span className="text-gray-400 ml-2">{new Date(activity.createdAt).toLocaleString()}</span>
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-gray-500">No recent activity</p>
          )}
        </div>
      </main>
    </div>
  );
}
