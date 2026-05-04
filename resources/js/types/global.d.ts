import { PageProps as InertiaPageProps } from "@inertiajs/core";
import { AxiosInstance } from "axios";
import { route as ziggyRoute } from "ziggy-js";

declare global {
    interface Window {
        axios: AxiosInstance;
    }

    /* eslint-disable no-var */
    var route: typeof ziggyRoute;
}

declare module "vue" {
    interface ComponentCustomProperties {
        route: typeof ziggyRoute;
    }
}

declare module "@inertiajs/core" {
    interface PageProps extends InertiaPageProps {
        auth: {
            user: {
                id: number;
                name: string;
                email: string;
                role: "owner" | "admin" | "manager" | "staff" | "viewer";
                status: "active" | "inactive" | "suspended";
                last_login_at: string | null;
                email_verified_at: string | null;
                created_at: string;
                updated_at: string;
            };
        };
        flash: {
            success?: string;
            error?: string;
            info?: string;
        };
    }
}
