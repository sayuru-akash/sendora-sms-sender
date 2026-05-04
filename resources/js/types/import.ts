export interface Import {
  id: number;
  filename: string;
  original_filename: string;
  file_type: 'csv' | 'xlsx';
  status: 'uploading' | 'mapping' | 'processing' | 'completed' | 'failed' | 'cancelled';
  total_rows: number;
  processed_rows: number;
  successful_rows: number;
  failed_rows: number;
  duplicate_rows: number;
  invalid_rows: number;
  progress: number;
  column_mapping: Record<string, string> | null;
  duplicate_handling: 'skip' | 'update' | 'add';
  list_ids: number[] | null;
  tag_ids: number[] | null;
  phone_column: string | null;
  error_message: string | null;
  failed_rows_data: ImportFailedRow[] | null;
  created_by: number;
  created_by_name: string;
  started_at: string | null;
  completed_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface ImportFailedRow {
  row_number: number;
  data: Record<string, string>;
  error: string;
}

export interface ImportColumnMapping {
  file_columns: string[];
  contact_fields: string[];
  mapping: Record<string, string>;
  preview_data: Record<string, string>[];
}

export interface ImportUploadData {
  file: File;
  duplicate_handling: 'skip' | 'update' | 'add';
  list_ids: number[];
  tag_ids: number[];
}

export interface ImportMappingData {
  mapping: Record<string, string>;
  phone_column: string;
  duplicate_handling: 'skip' | 'update' | 'add';
  list_ids: number[];
  tag_ids: number[];
}
