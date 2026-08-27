import { useAuth } from '../hooks/useAuth';

export default function Settings() {
  const { user } = useAuth();

  if (user?.role !== 'ORG_ADMIN') return <div className="text-center py-8 text-red-600">Access denied</div>;

  return (
    <div>
      <h1 className="text-2xl font-bold mb-6">Organization Settings</h1>
      <div className="bg-white rounded-lg shadow p-6">
        <h2 className="text-lg font-semibold mb-4">Organization Info</h2>
        <div className="space-y-4">
          <div><label className="block text-sm font-medium text-gray-700">Organization ID</label><p className="mt-1 text-sm text-gray-900 font-mono">{user.organizationId}</p></div>
          <div><label className="block text-sm font-medium text-gray-700">Your Role</label><p className="mt-1 text-sm text-gray-900">{user.role}</p></div>
          <div><label className="block text-sm font-medium text-gray-700">Your Email</label><p className="mt-1 text-sm text-gray-900">{user.email}</p></div>
        </div>
      </div>
    </div>
  );
}
