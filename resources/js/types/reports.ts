export type ReportPeriod = {
    from: string;
    to: string;
};

export type ReportSummary = {
    revenue: number;
    transactions: number;
    items_sold: number;
    average_transaction: number;
};

export type ReportMethod = {
    method: string;
    label: string;
    transactions: number;
    revenue: number;
};

export type ReportTopItem = {
    product_name: string;
    variant_name: string;
    unit: string;
    quantity: number;
    revenue: number;
};

export type ReportShift = {
    id: number;
    opened_at: string;
    cashier: string;
    status: 'OPEN' | 'CLOSED';
    opening_cash: number;
    expected_cash: number | null;
    actual_cash: number | null;
    difference: number | null;
};

export type SalesReport = {
    period: ReportPeriod;
    summary: ReportSummary;
    methods: ReportMethod[];
    top_items: ReportTopItem[];
    shifts: ReportShift[];
    shift_count: number;
};

export type ReportsPageProps = {
    report: SalesReport;
};
