import { ProductImageField } from '@/components/catalog/product-image-field';
import { VariantFields } from '@/components/catalog/variant-fields';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { Category, ProductFormValue, VariantFormValue } from '@/types/catalog';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Produk', href: '/products' },
    { title: 'Tambah produk', href: '/products/create' },
];

interface CreateProductProps {
    categories: Category[];
}

const emptyVariant = (): VariantFormValue => ({
    name: '',
    price: '',
    is_default: false,
    is_active: true,
    is_available: true,
});

export default function CreateProduct({ categories }: CreateProductProps) {
    const form = useForm<ProductFormValue>({
        category_id: categories[0]?.id ?? 0,
        name: '',
        description: '',
        unit: 'porsi',
        is_active: true,
        is_available: true,
        image: null,
        remove_image: false,
        variants: [{ ...emptyVariant(), is_default: true }],
    });

    const updateVariant = (index: number, next: VariantFormValue) => {
        const updated = form.data.variants.map((variant, position) => (position === index ? next : variant));
        const normalized = next.is_default ? updated.map((variant, position) => ({ ...variant, is_default: position === index })) : updated;

        form.setData('variants', normalized);
    };

    const addVariant = () => {
        form.setData('variants', [...form.data.variants, emptyVariant()]);
    };

    const removeVariant = (index: number) => {
        let variants = form.data.variants.filter((_, position) => position !== index);

        if (variants.length > 0 && !variants.some((variant) => variant.is_default)) {
            variants = variants.map((variant, position) => ({ ...variant, is_default: position === 0 }));
        }

        form.setData('variants', variants);
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            variants: data.variants.map((variant) => ({ ...variant, price: Number(variant.price) })),
        }));
        form.post('/products', { preserveScroll: true, forceFormData: true });
    };

    const noCategories = categories.length === 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah produk" />

            <div className="flex h-full flex-1 flex-col gap-5 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Tambah produk</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Produk harus memiliki minimal satu variasi harga.</p>
                    </div>
                    <Button asChild variant="ghost" size="sm">
                        <Link href="/products">
                            <ArrowLeft aria-hidden="true" />
                            Kembali ke produk
                        </Link>
                    </Button>
                </div>

                {noCategories && (
                    <div className="border-warning/60 bg-soft-yellow rounded-md border p-4 text-sm">
                        Belum ada kategori aktif.{' '}
                        <Link href="/categories" className="underline underline-offset-4">
                            Buat kategori terlebih dahulu
                        </Link>
                        .
                    </div>
                )}

                <form onSubmit={submit} className="flex flex-col gap-5">
                    <section className="space-y-4 rounded-xl border p-4 md:p-5">
                        <h2 className="font-semibold">Informasi produk</h2>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="product-category">Kategori</Label>
                                <select
                                    id="product-category"
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
                                <Label htmlFor="product-name">Nama produk</Label>
                                <Input
                                    id="product-name"
                                    value={form.data.name}
                                    onChange={(event) => form.setData('name', event.target.value)}
                                    placeholder="Contoh: Rendang"
                                    autoComplete="off"
                                />
                                <InputError message={form.errors.name} />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="product-unit">Satuan</Label>
                                <Input
                                    id="product-unit"
                                    value={form.data.unit}
                                    onChange={(event) => form.setData('unit', event.target.value)}
                                    placeholder="Contoh: potong, porsi, gelas"
                                    autoComplete="off"
                                />
                                <InputError message={form.errors.unit} />
                            </div>

                            <div className="space-y-1.5 md:col-span-2">
                                <Label htmlFor="product-description">Deskripsi (opsional)</Label>
                                <textarea
                                    id="product-description"
                                    rows={3}
                                    value={form.data.description}
                                    onChange={(event) => form.setData('description', event.target.value)}
                                    className="border-input bg-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                />
                                <InputError message={form.errors.description} />
                            </div>
                        </div>

                        <ProductImageField
                            id="new-product-image"
                            currentUrl={null}
                            file={form.data.image}
                            removeImage={form.data.remove_image}
                            error={form.errors.image}
                            onFileChange={(file) => form.setData('image', file)}
                            onRemove={() => {
                                form.setData('image', null);
                                form.setData('remove_image', false);
                            }}
                        />

                        <div className="flex flex-wrap gap-x-6 gap-y-2">
                            <label htmlFor="product-active" className="flex min-h-9 cursor-pointer items-center gap-2 text-sm">
                                <input
                                    id="product-active"
                                    type="checkbox"
                                    checked={form.data.is_active}
                                    onChange={(event) => form.setData('is_active', event.target.checked)}
                                    className="border-input size-4 rounded"
                                />
                                Produk aktif
                            </label>
                            <label htmlFor="product-available" className="flex min-h-9 cursor-pointer items-center gap-2 text-sm">
                                <input
                                    id="product-available"
                                    type="checkbox"
                                    checked={form.data.is_available}
                                    onChange={(event) => form.setData('is_available', event.target.checked)}
                                    className="border-input size-4 rounded"
                                />
                                Tersedia
                            </label>
                        </div>
                    </section>

                    <section className="space-y-4 rounded-xl border p-4 md:p-5">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h2 className="font-semibold">Variasi harga</h2>
                                <p className="text-muted-foreground mt-1 text-xs">Tepat satu variasi akan ditandai sebagai default.</p>
                            </div>
                            <Button type="button" variant="outline" size="sm" onClick={addVariant}>
                                <Plus aria-hidden="true" />
                                Tambah variasi
                            </Button>
                        </div>

                        <InputError message={form.errors.variants} />

                        <div className="space-y-3">
                            {form.data.variants.map((variant, index) => (
                                <div key={index} className="rounded-lg border p-3 md:p-4">
                                    <div className="mb-3 flex items-center justify-between gap-2">
                                        <span className="text-muted-foreground text-sm font-medium">Variasi {index + 1}</span>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive"
                                            onClick={() => removeVariant(index)}
                                            disabled={form.data.variants.length === 1}
                                        >
                                            <Trash2 aria-hidden="true" />
                                            Hapus
                                        </Button>
                                    </div>
                                    <VariantFields
                                        value={variant}
                                        onChange={(next) => updateVariant(index, next)}
                                        errors={{
                                            name: form.errors[`variants.${index}.name`],
                                            price: form.errors[`variants.${index}.price`],
                                        }}
                                        idPrefix={`new-variant-${index}`}
                                        defaultRadioName="new-product-variant-default"
                                    />
                                </div>
                            ))}
                        </div>
                    </section>

                    <div className="flex flex-wrap justify-end gap-2">
                        <Button asChild variant="outline">
                            <Link href="/products">Batal</Link>
                        </Button>
                        <Button type="submit" disabled={form.processing || noCategories}>
                            {form.processing ? 'Menyimpan…' : 'Simpan produk'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
