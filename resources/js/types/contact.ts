import type { Tag, ListModel } from "@/types";

export interface Contact {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    phone: string;
    email: string | null;
    company: string | null;
    job_title: string | null;
    city: string | null;
    district: string | null;
    address: string | null;
    source: "manual" | "import" | "api" | "web" | "referral" | null;
    status: "active" | "inactive" | "unsubscribed" | "blocked" | "invalid";
    notes: string | null;
    custom_fields: Record<string, unknown> | null;
    tags: Tag[];
    lists: ListModel[];
    created_at: string;
    updated_at: string;
    last_contacted_at: string | null;
    sms_count: number;
}

export interface ContactFormData {
    first_name: string;
    last_name: string;
    phone: string;
    email?: string;
    company?: string;
    job_title?: string;
    city?: string;
    district?: string;
    address?: string;
    source?: string;
    status?: string;
    notes?: string;
    tags?: number[];
    lists?: number[];
}

export interface ContactFilters {
    search?: string;
    status?: string;
    tag_id?: number;
    list_id?: number;
    source?: string;
    city?: string;
    district?: string;
    date_from?: string;
    date_to?: string;
    sort?: string;
    direction?: "asc" | "desc";
    per_page?: number;
    page?: number;
}

export interface ContactBulkAction {
    action:
        | "add_tag"
        | "remove_tag"
        | "add_to_list"
        | "remove_from_list"
        | "change_status"
        | "delete"
        | "export";
    contact_ids: number[];
    value?: string | number;
    tag_id?: number;
    list_id?: number;
    status?: string;
}
