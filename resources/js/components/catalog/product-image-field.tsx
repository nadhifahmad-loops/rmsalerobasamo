import { ProductImage } from '@/components/catalog/product-image';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useEffect, useRef, useState, type ChangeEvent } from 'react';

const ACCEPTED_MIME = ['image/jpeg', 'image/png', 'image/webp'];
const MAX_BYTES = 5 * 1024 * 1024;

interface ProductImageFieldProps {
    currentUrl: string | null;
    file: File | null;
    removeImage: boolean;
    onFileChange: (file: File | null) => void;
    onRemove: () => void;
    error?: string;
    id?: string;
}

/** Input foto produk dengan preview lokal sebelum submit. */
export function ProductImageField({ currentUrl, file, removeImage, onFileChange, onRemove, error, id = 'product-image' }: ProductImageFieldProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [localError, setLocalError] = useState<string | null>(null);

    useEffect(() => {
        if (!file) {
            setPreviewUrl(null);

            return;
        }

        const url = URL.createObjectURL(file);
        setPreviewUrl(url);

        return () => URL.revokeObjectURL(url);
    }, [file]);

    const hasImage = !removeImage && (previewUrl !== null || currentUrl !== null);

    const handleChange = (event: ChangeEvent<HTMLInputElement>) => {
        const selected = event.target.files?.[0] ?? null;

        if (!selected) {
            onFileChange(null);

            return;
        }

        if (!ACCEPTED_MIME.includes(selected.type)) {
            setLocalError('Format foto harus JPG, PNG, atau WebP.');
            onFileChange(null);
            event.target.value = '';

            return;
        }

        if (selected.size > MAX_BYTES) {
            setLocalError('Ukuran foto maksimal 5 MB.');
            onFileChange(null);
            event.target.value = '';

            return;
        }

        setLocalError(null);
        onFileChange(selected);
    };

    const handleRemove = () => {
        setLocalError(null);
        onRemove();

        if (inputRef.current) {
            inputRef.current.value = '';
        }
    };

    return (
        <div className="space-y-2">
            <Label htmlFor={id}>Foto produk (opsional)</Label>

            <div className="flex flex-col gap-3 sm:flex-row sm:items-start">
                <ProductImage
                    src={previewUrl ?? currentUrl}
                    alt="Pratinjau foto produk"
                    fit="contain"
                    className="aspect-[4/3] w-full rounded-lg border sm:w-40"
                />

                <div className="flex flex-col gap-2">
                    <Input
                        ref={inputRef}
                        id={id}
                        type="file"
                        accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                        onChange={handleChange}
                        className="h-auto py-2"
                    />
                    <p className="text-muted-foreground text-xs">JPG, PNG, atau WebP. Maksimal 5 MB dan 2400 × 1800 piksel.</p>
                    <div>
                        <Button type="button" variant="outline" size="sm" onClick={handleRemove} disabled={!hasImage}>
                            Hapus foto
                        </Button>
                    </div>
                </div>
            </div>

            <InputError message={localError ?? error} />
        </div>
    );
}
