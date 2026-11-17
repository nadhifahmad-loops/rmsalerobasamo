import { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface User {
    id: number;
    name: string;
    username: string;
    email: string | null;
    avatar?: string | null;
    is_active: boolean;
    role: 'kasir' | 'admin' | 'owner' | null;
    permissions: Record<string, boolean>;
    created_at?: string;
    updated_at?: string;
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface SharedData {
    name: string;
    auth: Auth;
    flash?: {
        success?: string | null;
        error?: string | null;
    };
    [key: string]: unknown;
}

export interface Props {
    auth?: { user: User };
    [key: string]: unknown;
}

export type Breadcrumb = BreadcrumbItem & {
    icon?: ReactNode;
};
