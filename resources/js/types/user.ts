export interface UserFormData {
  name: string;
  email: string;
  password?: string;
  password_confirmation?: string;
  role: 'owner' | 'admin' | 'manager' | 'staff' | 'viewer';
  status: 'active' | 'inactive' | 'suspended';
}

export const USER_ROLES = [
  { value: 'owner', label: 'Owner', description: 'Full access to all features and billing' },
  { value: 'admin', label: 'Admin', description: 'Full access except billing and owner management' },
  { value: 'manager', label: 'Manager', description: 'Manage contacts, campaigns, and templates' },
  { value: 'staff', label: 'Staff', description: 'View and manage assigned contacts' },
  { value: 'viewer', label: 'Viewer', description: 'Read-only access to dashboards and reports' },
] as const;

export const ROLE_COLORS: Record<string, string> = {
  owner: 'bg-purple-100 text-purple-700 border-purple-200',
  admin: 'bg-blue-100 text-blue-700 border-blue-200',
  manager: 'bg-emerald-100 text-emerald-700 border-emerald-200',
  staff: 'bg-amber-100 text-amber-700 border-amber-200',
  viewer: 'bg-gray-100 text-gray-700 border-gray-200',
};
