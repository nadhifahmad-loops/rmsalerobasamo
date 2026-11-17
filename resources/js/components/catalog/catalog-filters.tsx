import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Category, CatalogFilters as Filters } from '@/types/catalog';
import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';

interface CatalogFiltersProps {
    url: string;
    categories: Category[];
    filters: Filters;
    statuses?: { value: string; label: string }[];
}

const selectClassName =
    'border-input bg-background focus-visible:ring-ring h-10 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:outline-none';

function buildParams(search: string, categoryId: number | null, status?: string) {
    return {
        search: search || undefined,
        category_id: categoryId ?? undefined,
        status: status || undefined,
    };
}

/** Filter katalog server-side; dipakai bersama katalog kasir dan daftar admin. */
export function CatalogFilters({ url, categories, filters, statuses }: CatalogFiltersProps) {
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        setSearch(filters.search);
    }, [filters.search]);

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timeout = window.setTimeout(() => {
            router.get(url, buildParams(search, filters.category_id, filters.status), {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 300);

        return () => window.clearTimeout(timeout);
    }, [search, filters.search, filters.category_id, filters.status, url]);

    const submitWith = (overrides: Partial<Filters>) => {
        const next = {
            search,
            category_id: filters.category_id,
            status: filters.status,
            ...overrides,
        };

        router.get(url, buildParams(next.search, next.category_id, next.status), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const onSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        submitWith({});
    };

    return (
        <form role="search" onSubmit={onSubmit} className="rounded-xl border p-3 md:p-4">
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div className="space-y-1.5">
                    <Label htmlFor="catalog-search">Cari menu</Label>
                    <div className="relative">
                        <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" aria-hidden="true" />
                        <Input
                            id="catalog-search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Nama atau deskripsi menu"
                            className="pl-9"
                            autoComplete="off"
                        />
                    </div>
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="catalog-category">Kategori</Label>
                    <select
                        id="catalog-category"
                        className={selectClassName}
                        value={filters.category_id ?? ''}
                        onChange={(event) => submitWith({ category_id: event.target.value ? Number(event.target.value) : null })}
                    >
                        <option value="">Semua kategori</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.name}
                            </option>
                        ))}
                    </select>
                </div>

                {statuses && (
                    <div className="space-y-1.5">
                        <Label htmlFor="catalog-status">Status</Label>
                        <select
                            id="catalog-status"
                            className={selectClassName}
                            value={filters.status ?? ''}
                            onChange={(event) => submitWith({ status: event.target.value })}
                        >
                            <option value="">Semua status</option>
                            {statuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </select>
                    </div>
                )}
            </div>
        </form>
    );
}
