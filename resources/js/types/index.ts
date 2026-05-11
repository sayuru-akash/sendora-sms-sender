export interface User {
    id: number;
    name: string;
    email: string;
    role: "owner" | "admin" | "manager" | "staff" | "viewer";
    status: "active" | "inactive" | "suspended";
    last_login_at: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface PaginatedData<T> {
    data: T[];
    meta: Pagination;
}

export interface PageProps {
    auth: {
        user: User;
    };
    flash: {
        success?: string;
        error?: string;
        info?: string;
    };
}

export interface BreadcrumbItem {
    label: string;
    href?: string;
}

export interface SelectOption {
    label: string;
    value: string | number;
}

export interface FilterOption {
    key: string;
    label: string;
    type: "text" | "select" | "multiselect" | "date" | "daterange" | "boolean";
    options?: SelectOption[];
    placeholder?: string;
}

export type SortDirection = "asc" | "desc";

export interface SortState {
    column: string;
    direction: SortDirection;
}

export interface TableAction {
    label: string;
    icon?: string;
    handler: (row: unknown) => void;
    variant?: "default" | "destructive";
    show?: (row: unknown) => boolean;
}

export interface Tag {
    id: number;
    name: string;
    color: string;
    description: string | null;
    contacts_count: number;
    created_at: string;
    updated_at: string;
}

export interface TagFormData {
    name: string;
    color: string;
    description?: string;
}

export interface ListModel {
    id: number;
    name: string;
    color: string;
    description: string | null;
    contacts_count: number;
    status: "active" | "inactive" | "archived";
    created_at: string;
    updated_at: string;
}

export interface ListFormData {
    name: string;
    color: string;
    description?: string;
    status?: string;
}

export interface SavedSegment {
    id: number;
    name: string;
    description: string | null;
    filters: {
        status?: string;
        source?: string;
        district?: string;
        city?: string;
        gender?: string;
        date_from?: string;
        date_to?: string;
        tag_ids?: number[];
        list_ids?: number[];
    };
    created_by: number;
    creator?: User;
    created_at: string;
    updated_at: string;
}

export interface SmsRecord {
    id: number;
    campaign_id: number | null;
    campaign_name: string | null;
    contact_id: number;
    contact_name: string;
    contact_phone: string;
    message: string;
    status: "sent" | "delivered" | "failed" | "pending";
    error_message: string | null;
    sent_at: string | null;
    delivered_at: string | null;
    segments: number;
    cost: number | null;
    created_at: string;
}

export interface ActivityLog {
    id: number;
    event: string;
    description: string;
    subject_type: string;
    subject_id: number;
    subject_name: string | null;
    subject_url: string | null;
    subject_action_label: string | null;
    causer_id: number | null;
    causer_name: string | null;
    properties: Record<string, unknown>;
    created_at: string;
}

export interface DashboardProps {
    stats: {
        total_contacts: number;
        sms_sent_this_month: number;
        failed_sms: number;
        active_campaigns: number;
    };
    contact_growth: { month: string; count: number }[];
    campaign_performance: { name: string; sent: number; failed: number }[];
    recent_campaigns: Array<{
        id: number;
        name: string;
        status: string;
        total_recipients: number;
        sent_count: number;
        failed_count: number;
        success_rate: number;
        created_at: string;
    }>;
    recent_imports: Array<{
        id: number;
        original_filename: string;
        status: string;
        total_rows: number;
        successful_rows: number;
        created_at: string;
    }>;
    top_lists: ListModel[];
    activity_log: ActivityLog[];
}
