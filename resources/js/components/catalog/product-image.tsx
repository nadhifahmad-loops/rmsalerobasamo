import { cn } from '@/lib/utils';
import { useEffect, useState } from 'react';

const PLACEHOLDER = '/images/products/placeholder.svg';

interface ProductImageProps {
    src: string | null;
    alt: string;
    className?: string;
    imgClassName?: string;
    loading?: 'lazy' | 'eager';
    fit?: 'cover' | 'contain';
}

/**
 * Gambar produk dengan placeholder lokal dan fallback saat URL gagal dimuat.
 * Komponen ini tidak pernah meminta gambar dari sumber eksternal.
 */
export function ProductImage({ src, alt, className, imgClassName, loading = 'lazy', fit = 'cover' }: ProductImageProps) {
    const [failed, setFailed] = useState(false);
    const resolved = !failed && src ? src : PLACEHOLDER;

    useEffect(() => {
        setFailed(false);
    }, [src]);

    return (
        <div className={cn('bg-muted overflow-hidden', className)}>
            <img
                key={resolved}
                src={resolved}
                alt={alt}
                width={640}
                height={480}
                loading={loading}
                decoding="async"
                onError={() => setFailed(true)}
                className={cn('size-full', fit === 'cover' ? 'object-cover' : 'object-contain', imgClassName)}
            />
        </div>
    );
}
