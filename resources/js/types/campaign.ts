export interface Campaign {
    id: number;
    name: string;
    status:
        | "draft"
        | "scheduled"
        | "queued"
        | "sending"
        | "paused"
        | "completed"
        | "failed"
        | "cancelled";
    sender_id: string;
    message_body: string;
    target_type:
        | "all_contacts"
        | "list"
        | "tag"
        | "saved_segment"
        | "manual_selection"
        | "advanced_filter";
    target_config: CampaignTargetConfig;
    template_id: number | null;
    template_name: string | null;
    total_recipients: number;
    sent_count: number;
    failed_count: number;
    skipped_count: number;
    pending_count: number;
    success_rate: number;
    estimated_cost: number | null;
    scheduled_at: string | null;
    started_at: string | null;
    completed_at: string | null;
    created_by: number;
    created_by_name: string;
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface CampaignTargetConfig {
    list_ids?: number[];
    tag_ids?: number[];
    contact_ids?: number[];
    segment_id?: number | string | null;
    advanced?: Record<string, unknown> | null;
    include_all?: boolean;
    filters?: Record<string, unknown>;
    exclude_unsubscribed?: boolean;
    exclude_blocked?: boolean;
    exclude_invalid?: boolean;
}

export interface CampaignRecipient {
    id: number;
    campaign_id: number;
    contact_id: number;
    contact_name: string;
    contact_phone: string;
    status: "pending" | "queued" | "sent" | "failed" | "skipped";
    error_message: string | null;
    sent_at: string | null;
    created_at: string;
    contact?: {
        id: number;
        full_name: string;
        phone_normalised?: string;
        phone?: string;
        email?: string | null;
        status?: string;
    };
}

export interface CampaignFormData {
    name: string;
    sender_id: string;
    message_body: string;
    target_type: string;
    target_config: CampaignTargetConfig;
    template_id?: number | null;
    notes?: string;
    scheduled_at?: string | null;
}

export interface CampaignStats {
    total: number;
    sent: number;
    failed: number;
    skipped: number;
    pending: number;
    success_rate: number;
    timeline: { time: string; sent: number; failed: number }[];
}
