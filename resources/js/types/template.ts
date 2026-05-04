export interface Template {
  id: number;
  name: string;
  category: string;
  body: string;
  character_count: number;
  sms_segments: number;
  status: 'active' | 'inactive' | 'draft';
  variables: string[];
  usage_count: number;
  created_by: number;
  created_by_name: string;
  created_at: string;
  updated_at: string;
}

export interface TemplateFormData {
  name: string;
  category: string;
  body: string;
  status?: string;
}

export const TEMPLATE_VARIABLES = [
  { key: '{first_name}', label: 'First Name' },
  { key: '{last_name}', label: 'Last Name' },
  { key: '{full_name}', label: 'Full Name' },
  { key: '{company}', label: 'Company' },
  { key: '{city}', label: 'City' },
  { key: '{district}', label: 'District' },
] as const;

export const TEMPLATE_CATEGORIES = [
  'Marketing',
  'Transactional',
  'OTP',
  'Notification',
  'Reminder',
  'Custom',
] as const;

export function calculateSmsSegments(body: string): number {
  if (!body) return 0;
  const hasUnicode = /[^\x00-\x7F]/.test(body);
  if (hasUnicode) {
    if (body.length <= 70) return 1;
    return Math.ceil(body.length / 67);
  }
  if (body.length <= 160) return 1;
  return Math.ceil(body.length / 153);
}
