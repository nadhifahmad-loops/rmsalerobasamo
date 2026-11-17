import type { Paginated } from './catalog';

export type TableStatus = 'AVAILABLE' | 'OCCUPIED' | 'AWAITING_PAYMENT';

export interface DiningTable {
    id: number;
    table_number: string;
    label: string | null;
    display_label: string;
    is_active: boolean;
    status: TableStatus;
    active_order_id: number | null;
    active_order_number: string | null;
    running_total: number;
}

export type OrderStatus = 'OPEN' | 'AWAITING_PAYMENT' | 'PAID' | 'VOIDED';
export type OrderType = 'DINE_IN' | 'TAKEAWAY';

export interface OrderItem {
    id: number;
    order_id: number;
    product_id: number | null;
    product_variant_id: number | null;
    product_name_snapshot: string;
    variant_name_snapshot: string;
    unit_snapshot: string;
    price_snapshot: number;
    quantity: number;
    subtotal: number;
    notes: string | null;
    created_at?: string;
}

export interface Payment {
    id: number;
    order_id: number;
    payment_number: string;
    payment_method: PaymentMethod;
    amount: number;
    received_amount: number | null;
    change_amount: number;
    status: 'PAID';
    created_at: string;
    processor?: { id: number; name: string } | null;
}

export interface Order {
    id: number;
    order_number: string;
    order_type: OrderType;
    dining_table_id: number | null;
    table_label_snapshot: string | null;
    cashier_id: number;
    cashier?: { id: number; name: string } | null;
    dining_table?: DiningTable | null;
    status: OrderStatus;
    subtotal: number;
    discount: number;
    tax: number;
    service_charge: number;
    total: number;
    notes: string | null;
    items: OrderItem[];
    payment?: Payment | null;
    shift?: { id: number; status: 'OPEN' | 'CLOSED' } | null;
    created_at: string;
}

export interface OrderRow {
    id: number;
    order_number: string;
    order_type: OrderType;
    table_label_snapshot: string | null;
    status: OrderStatus;
    total: number;
    notes: string | null;
    created_at: string;
    paid_at?: string | null;
    payment_number?: string | null;
    payment_method?: PaymentMethod | null;
    items_count: number;
    cashier: { id: number; name: string } | null;
    dining_table: { id: number; table_number: string; label: string | null } | null;
}

export type PaymentMethod = 'CASH' | 'QRIS' | 'TRANSFER' | 'DEBIT';

export interface PaymentMethodOption {
    value: PaymentMethod;
    label: string;
}

export type OrderFormValue = {
    dining_table_id: number;
    notes: string;
    [key: string]: number | string;
};

export type DiningTableFormValue = {
    table_number: string;
    label: string;
    is_active: boolean;
    [key: string]: string | boolean;
};

export type OrderItemFormValue = {
    quantity: number;
    notes: string;
    [key: string]: number | string;
};

export type OrderNotesFormValue = {
    notes: string;
    [key: string]: string;
};

export type OrderFiltersValue = {
    view: 'active' | 'history';
    search: string;
    from: string;
    to: string;
    cashier_id: number | null;
    payment_method: PaymentMethod | '';
    order_type: OrderType | '';
    status: OrderStatus | '';
};

export type OrderFilterOptions = {
    cashiers: { id: number; name: string }[];
    statuses: { value: string; label: string }[];
    types: { value: string; label: string }[];
    methods: { value: string; label: string }[];
};

export type OrdersPageProps = {
    orders: Paginated<OrderRow>;
    view: 'active' | 'history';
    filters: OrderFiltersValue;
    options: OrderFilterOptions;
    activeShift: { id: number; opening_cash: number } | null;
};

export type OrderShowProps = {
    order: Order;
    products: Paginated<import('./catalog').Product>;
    categories: import('./catalog').Category[];
    filters: import('./catalog').CatalogFilters;
    productImages: Record<number, string | null>;
    canEdit: boolean;
};

export type CheckoutPageProps = {
    order: Order;
    paymentMethods: PaymentMethodOption[];
    maxReceivedAmount: number;
};

export type TablesPageProps = {
    tables: Paginated<DiningTable>;
    filters: { search: string };
};
