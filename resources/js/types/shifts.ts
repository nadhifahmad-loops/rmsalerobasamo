import type { Paginated } from './catalog';

export type ShiftStatus = 'OPEN' | 'CLOSED';

export interface ShiftRow {
    id: number;
    cashier: string;
    status: ShiftStatus;
    opened_at: string;
    closed_at: string | null;
    opening_cash: number;
    expected_cash: number | null;
    actual_cash: number | null;
    difference: number | null;
    orders_count: number;
}

export interface ActiveShift {
    id: number;
    cashier_id: number;
    opening_cash: number;
    opened_at: string;
    expected_cash: number;
}

export interface ManagedShift {
    id: number;
    cashier: string;
    opened_at: string;
    opening_cash: number;
    expected_cash: number;
}

export type ShiftsPageProps = {
    shifts: Paginated<ShiftRow>;
    activeShift: ActiveShift | null;
    managedShifts: ManagedShift[];
    canManage: boolean;
};

export type OpenShiftFormValue = {
    opening_cash: string;
    [key: string]: string;
};

export type CloseShiftFormValue = {
    actual_cash: string;
    [key: string]: string;
};
