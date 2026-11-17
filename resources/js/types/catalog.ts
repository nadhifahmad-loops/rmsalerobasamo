export interface Category {
    id: number;
    name: string;
    is_active: boolean;
    sort_order: number;
    products_count?: number;
}

export interface ProductVariant {
    id: number;
    product_id: number;
    name: string;
    price: number;
    is_default: boolean;
    is_active: boolean;
    is_available: boolean;
}

export interface Product {
    id: number;
    category_id: number;
    name: string;
    description: string | null;
    unit: string;
    is_active: boolean;
    is_available: boolean;
    image_url: string | null;
    category?: Category | null;
    variants?: ProductVariant[];
    variants_count?: number;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    current_page: number;
    data: T[];
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    next_page_url: string | null;
    prev_page_url: string | null;
    per_page: number;
    to: number | null;
    total: number;
}

export interface CatalogFilters {
    search: string;
    category_id: number | null;
    status?: string;
}

/**
 * Nilai form variasi. Price ditahan sebagai string saat input kosong.
 * Index signature diperlukan agar kompatibel dengan Inertia FormDataType.
 */
export type VariantFormValue = {
    name: string;
    price: string | number;
    is_default: boolean;
    is_active: boolean;
    is_available: boolean;
    [key: string]: string | number | boolean;
};

/** Form pembuatan produk termasuk daftar variasi. */
export type ProductFormValue = {
    category_id: number;
    name: string;
    description: string;
    unit: string;
    is_active: boolean;
    is_available: boolean;
    image: File | null;
    remove_image: boolean;
    variants: VariantFormValue[];
    [key: string]: string | number | boolean | File | null | VariantFormValue[];
};
