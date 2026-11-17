import type { CatalogFilters, Category, Paginated, Product } from './catalog';

export interface TakeawayCartItem {
    product_id: number;
    product_variant_id: number;
    product_name: string;
    variant_name: string;
    unit: string;
    price: number;
    quantity: number;
    notes: string;
    image_url: string | null;
    [key: string]: string | number | null;
}

export type TakeawayPageProps = {
    products: Paginated<Product>;
    categories: Category[];
    filters: CatalogFilters;
    activeShift: { id: number; opening_cash: number } | null;
};

export type TakeawayFormValue = {
    notes: string;
    items: TakeawayCartItem[];
    [key: string]: string | TakeawayCartItem[];
};
