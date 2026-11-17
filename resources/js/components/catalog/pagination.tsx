import { decodePaginationLabel } from '@/lib/format';
import type { PaginationLink } from '@/types/catalog';
import { Link } from '@inertiajs/react';

export function Pagination({ links }: { links: PaginationLink[] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav aria-label="Paginasi" className="flex flex-wrap items-center justify-center gap-1 py-2">
            {links.map((link, index) => {
                const label = decodePaginationLabel(link.label);

                if (!link.url) {
                    return (
                        <span key={index} aria-disabled="true" className="text-muted-foreground px-3 py-2 text-sm">
                            {label}
                        </span>
                    );
                }

                return (
                    <Link
                        key={index}
                        href={link.url}
                        preserveScroll
                        aria-current={link.active ? 'page' : undefined}
                        className={
                            link.active
                                ? 'bg-primary text-primary-foreground min-h-10 rounded-md px-3 py-2 text-sm'
                                : 'border-input hover:bg-accent min-h-10 rounded-md border px-3 py-2 text-sm'
                        }
                    >
                        {label}
                    </Link>
                );
            })}
        </nav>
    );
}
