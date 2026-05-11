export interface Template {
  id: number;
  name: string;
  category: string;
  body: string;
  character_count: number;
  sms_segments: number;
  sms_encoding: 'GSM-7' | 'Unicode';
  status: 'active' | 'inactive';
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

const GSM_BASIC_CHARS = new Set([
  '@', '£', '$', '¥', 'è', 'é', 'ù', 'ì', 'ò', 'Ç', '\n', 'Ø', 'ø', '\r', 'Å', 'å',
  'Δ', '_', 'Φ', 'Γ', 'Λ', 'Ω', 'Π', 'Ψ', 'Σ', 'Θ', 'Ξ',
  ' ', '!', '"', '#', '¤', '%', '&', "'", '(', ')', '*', '+', ',', '-', '.', '/',
  '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ':', ';', '<', '=', '>', '?',
  '¡', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
  'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'Ä', 'Ö', 'Ñ', 'Ü', '§',
  '¿', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o',
  'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'ä', 'ö', 'ñ', 'ü', 'à',
]);

const GSM_EXTENDED_CHARS = new Set(['^', '{', '}', '\\', '[', '~', ']', '|', '€']);

export interface SmsMetrics {
  encoding: 'GSM-7' | 'Unicode';
  characterCount: number;
  smsSegments: number;
  perSegment: number;
  remainingInSegment: number;
}

export function calculateSmsMetrics(body: string): SmsMetrics {
  const encoding = isGsm7(body) ? 'GSM-7' : 'Unicode';
  const characterCount = encoding === 'GSM-7' ? countGsmCharacters(body) : countUtf16Units(body);

  if (characterCount === 0) {
    return {
      encoding,
      characterCount: 0,
      smsSegments: 0,
      perSegment: 160,
      remainingInSegment: 160,
    };
  }

  const singleLimit = encoding === 'GSM-7' ? 160 : 70;
  const multiLimit = encoding === 'GSM-7' ? 153 : 67;

  if (characterCount <= singleLimit) {
    return {
      encoding,
      characterCount,
      smsSegments: 1,
      perSegment: singleLimit,
      remainingInSegment: singleLimit - characterCount,
    };
  }

  const smsSegments = Math.ceil(characterCount / multiLimit);

  return {
    encoding,
    characterCount,
    smsSegments,
    perSegment: multiLimit,
    remainingInSegment: (smsSegments * multiLimit) - characterCount,
  };
}

export function calculateSmsSegments(body: string): number {
  return calculateSmsMetrics(body).smsSegments;
}

function isGsm7(body: string): boolean {
  return Array.from(body).every((character) => GSM_BASIC_CHARS.has(character) || GSM_EXTENDED_CHARS.has(character));
}

function countGsmCharacters(body: string): number {
  return Array.from(body).reduce((count, character) => count + (GSM_EXTENDED_CHARS.has(character) ? 2 : 1), 0);
}

function countUtf16Units(body: string): number {
  return body.length;
}
