import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';

const navItems = [
  { path: '/dashboard', label: 'Dashboard' },
  { path: '/clients', label: 'Clients' },
  { path: '/invoices', label: 'Invoices' },
  { path: '/recurring', label: 'Recurring' },
  { path: '/payments', label: 'Payments' },
  { path: '/products', label: 'Products' },
];

const adminItems = [
  { path: '/users', label: 'Users' },
  { path: '/settings', label: 'Settings' },
];

export default function Layout({ children }) {
  const { user, logout } = useAuth();
  const location = useLocation();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            <div className="flex items-center space-x-8">
              <Link to="/dashboard" className="text-xl font-bold text-blue-600">ClientFlow</Link>
              <div className="hidden md:flex space-x-1">
                {navItems.map(item => (
                  <Link key={item.path} to={item.path}
                    className={`px-3 py-2 rounded-md text-sm font-medium ${location.pathname === item.path ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'}`}>
                    {item.label}
                  </Link>
                ))}
                {user?.role === 'ORG_ADMIN' && adminItems.map(item => (
                  <Link key={item.path} to={item.path}
                    className={`px-3 py-2 rounded-md text-sm font-medium ${location.pathname === item.path ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'}`}>
                    {item.label}
                  </Link>
                ))}
              </div>
            </div>
            <div className="flex items-center space-x-4">
              <span className="text-sm text-gray-500">{user?.name}</span>
              <span className="text-xs px-2 py-1 bg-gray-100 rounded">{user?.role}</span>
              <button onClick={handleLogout} className="text-sm text-red-600 hover:text-red-800">Logout</button>
            </div>
          </div>
        </div>
      </nav>
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {children}
      </main>
    </div>
  );
}
