import { ConfirmDialog } from '@/components/catalog/confirm-dialog';
import { ProductImageField } from '@/components/catalog/product-image-field';
import { VariantStatusLabel } from '@/components/catalog/status-label';
import { VariantFormDialog } from '@/components/catalog/variant-form-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatRupiah } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';
import type { Category, Product, ProductVariant } from '@/types/catalog';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Produk', href: '/products' },
    { title: 'Edit produk', href: '/products' },
];

interface EditProductProps {
    product: Product;
    categories: Category[];
}

export default function EditProduct({ product, categories }: EditProductProps) {
    const form = useForm({
        category_id: product.category_id,
        name: product.name,
        description: product.description ?? '',
        unit: product.unit,
        is_active: product.is_active,
        is_available: product.is_available,
        image: null as File | null,
        remove_image: false as boolean,
    });

    const variants = product.variants ?? [];
    const [variantDialogOpen, setVariantDialogOpen] = useState(false);
    const [editingVariant, setEditingVariant] = useState<ProductVariant | null>(null);
    const [deletingVariant, setDeletingVariant] = useState<ProductVariant | null>(null);
    const [processing, setProcessing] = useState(false);

    const submitProduct = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        // PHP hanya mengisi $_FILES untuk POST. Method spoof tetap memakai route PUT
        // di backend sambil mengirim berkas melalui multipart/form-data.
        form.transform((data) => ({ ...data, _method: 'PUT' }));
        form.post(`/products/${product.id}`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => form.reset('image', 'remove_image'),
        });
    };

    const confirmDeleteVariant = () => {
        if (!deletingVariant) {
            return;
        }

        setProcessing(true);
        router.delete(`/products/${product.id}/variants/${deletingVariant.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setDeletingVariant(null);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${product.name}`} />

            <div className="flex h-full flex-1 flex-col gap-5 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Edit produk</h1>
                        <p className="text-muted-foreground mt-1 text-sm">{product.name}</p>
                    </div>
                    <Button asChild variant="ghost" size="sm">
                        <Link href="/products">
                            <ArrowLeft aria-hidden="true" />
                            Kembali ke produk
                        </Link>
                    </Button>
                </div>

                <form onSubmit={submitProduct} className="space-y-4 rounded-xl border p-4 md:p-5">
                    <h2 className="font-semibold">Informasi produk</h2>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="edit-product-category">Kategori</Label>
                            <select
                                id="edit-product-category"
                                className="border-input bg-background focus-visible:ring-ring h-10 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                value={form.data.category_id}
                                onChange={(event) => form.setData('category_id', Number(event.target.value))}
                            >
                                {categories.map((category) => (
                                    <option key={category.id} value={category.id}>
                                        {category.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.category_id} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="edit-product-name">Nama produk</Label>
                            <Input id="edit-product-name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="edit-product-unit">Satuan</Label>
                            <Input id="edit-product-unit" value={form.data.unit} onChange={(event) => form.setData('unit', event.target.value)} />
                            <InputError message={form.errors.unit} />
                        </div>

                        <div className="space-y-1.5 md:col-span-2">
                            <Label htmlFor="edit-product-description">Deskripsi (opsional)</Label>
                            <textarea
                                id="edit-product-description"
                                rows={3}
                                value={form.data.description}
                                onChange={(event) => form.setData('description', event.target.value)}
                                className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                            />
                            <InputError message={form.errors.description} />
                        </div>
                    </div>

                    <ProductImageField
                        id="edit-product-image"
                        currentUrl={product.image_url}
                        file={form.data.image}
                        removeImage={form.data.remove_image}
                        error={form.errors.image}
                        onFileChange={(file) => {
                            form.setData('image', file);
                            form.setData('remove_image', false);
                        }}
                        onRemove={() => {
                            form.setData('image', null);
                            form.setData('remove_image', true);
                        }}
                    />

                    <div className="flex flex-wrap gap-x-6 gap-y-2">
                        <label htmlFor="edit-product-active" className="flex min-h-9 cursor-pointer items-center gap-2 text-sm">
                            <input
                                id="edit-product-active"
                                type="checkbox"
                                checked={form.data.is_active}
                                onChange={(event) => form.setData('is_active', event.target.checked)}
                                className="border-input size-4 rounded"
                            />
                            Produk aktif
                        </label>
                        <label htmlFor="edit-product-available" className="flex min-h-9 cursor-pointer items-center gap-2 text-sm">
                            <input
                                id="edit-product-available"
                                type="checkbox"
                                checked={form.data.is_available}
                                onChange={(event) => form.setData('is_available', event.target.checked)}
                                className="border-input size-4 rounded"
                            />
                            Tersedia
                        </label>
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : 'Simpan perubahan produk'}
                        </Button>
                    </div>
                </form>

                <section className="space-y-4 rounded-xl border p-4 md:p-5">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h2 className="font-semibold">Variasi harga</h2>
                            <p className="text-muted-foreground mt-1 text-xs">
                                Perubahan harga hanya berlaku untuk item baru; pesanan lama tetap memakai harga saat item ditambahkan.
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => {
                                setEditingVariant(null);
                                setVariantDialogOpen(true);
                            }}
                        >
                            <Plus aria-hidden="true" />
                            Tambah variasi
                        </Button>
                    </div>

                    {variants.length === 0 ? (
                        <div className="rounded-lg border border-dashed p-8 text-center text-sm">Produk ini belum memiliki variasi.</div>
                    ) : (
                        <div className="divide-y rounded-lg border">
                            {variants.map((variant) => (
                                <div key={variant.id} className="flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="min-w-0">
                                        <p className="font-medium">
                                            {variant.name}
                                            {variant.is_default && <span className="text-muted-foreground ml-2 text-xs font-normal">Default</span>}
                                        </p>
                                        <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                                            <span className="text-sm font-semibold">{formatRupiah(variant.price)}</span>
                                            <VariantStatusLabel isActive={variant.is_active} isAvailable={variant.is_available} />
                                        </div>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => {
                                                setEditingVariant(variant);
                                                setVariantDialogOpen(true);
                                            }}
                                        >
                                            <Pencil aria-hidden="true" />
                                            Edit
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive"
                                            onClick={() => setDeletingVariant(variant)}
                                        >
                                            <Trash2 aria-hidden="true" />
                                            Hapus
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>
            </div>

            <VariantFormDialog
                key={editingVariant?.id ?? 'new'}
                open={variantDialogOpen}
                onOpenChange={setVariantDialogOpen}
                productId={product.id}
                variant={editingVariant}
            />

            <ConfirmDialog
                open={deletingVariant !== null}
                onOpenChange={(open) => !open && setDeletingVariant(null)}
                title="Hapus variasi?"
                description={`Variasi "${deletingVariant?.name ?? ''}" akan dihapus. Produk harus memiliki minimal satu variasi aktif.`}
                confirmLabel="Hapus variasi"
                destructive
                processing={processing}
                onConfirm={confirmDeleteVariant}
            />
        </AppLayout>
    );
}
