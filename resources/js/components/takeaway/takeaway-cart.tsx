import { ProductImage } from '@/components/catalog/product-image';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatRupiah } from '@/lib/format';
import type { TakeawayCartItem } from '@/types/takeaway';
import { Minus, Plus, ShoppingBag, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';

interface TakeawayCartProps {
    items: TakeawayCartItem[];
    notes: string;
    submitting: boolean;
    errors: Partial<Record<string, string>>;
    onQuantityChange: (index: number, delta: number) => void;
    onRemove: (index: number) => void;
    onItemNotesChange: (index: number, value: string) => void;
    onNotesChange: (value: string) => void;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
}

/** Keranjang takeaway. Harga hanya ditampilkan sebagai preview; backend menghitung ulang. */
export function TakeawayCart({
    items,
    notes,
    submitting,
    errors,
    onQuantityChange,
    onRemove,
    onItemNotesChange,
    onNotesChange,
    onSubmit,
}: TakeawayCartProps) {
    const total = items.reduce((sum, item) => sum + item.price * item.quantity, 0);

    return (
        <form onSubmit={onSubmit} className="flex flex-col gap-4" aria-label="Keranjang takeaway">
            <section className="rounded-xl border p-4">
                <div className="flex items-center justify-between gap-2 border-b pb-3">
                    <div>
                        <h2 className="font-semibold">Keranjang</h2>
                        <p className="text-muted-foreground mt-1 text-xs">{items.length} variasi dipilih</p>
                    </div>
                    <ShoppingBag className="text-muted-foreground size-5" aria-hidden="true" />
                </div>

                {items.length === 0 ? (
                    <div className="py-8 text-center">
                        <p className="font-medium">Keranjang masih kosong</p>
                        <p className="text-muted-foreground mt-1 text-xs">Pilih variasi menu di sebelah kiri.</p>
                    </div>
                ) : (
                    <ul className="divide-y">
                        {items.map((item, index) => (
                            <li key={`${item.product_id}-${item.product_variant_id}`} className="py-3">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="flex min-w-0 flex-1 items-start gap-3">
                                        <ProductImage src={item.image_url} alt={item.product_name} className="size-12 shrink-0 rounded-md border" />
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">{item.product_name}</p>
                                            <p className="text-muted-foreground text-xs">
                                                {item.variant_name} · {formatRupiah(item.price)} / {item.unit}
                                            </p>
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        onClick={() => onRemove(index)}
                                        aria-label={`Hapus ${item.product_name} ${item.variant_name}`}
                                    >
                                        <Trash2 aria-hidden="true" />
                                    </Button>
                                </div>

                                <div className="mt-2 flex items-center justify-between gap-3">
                                    <div className="flex items-center gap-1">
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="outline"
                                            className="size-9"
                                            onClick={() => onQuantityChange(index, -1)}
                                            aria-label={`Kurangi ${item.product_name}`}
                                        >
                                            <Minus aria-hidden="true" />
                                        </Button>
                                        <Input
                                            type="number"
                                            min={1}
                                            max={100}
                                            value={item.quantity}
                                            onChange={(event) => {
                                                const value = Number(event.target.value);
                                                if (Number.isFinite(value) && value >= 1) {
                                                    onQuantityChange(index, value - item.quantity);
                                                }
                                            }}
                                            className="h-9 w-14 text-center"
                                            aria-label={`Jumlah ${item.product_name}`}
                                        />
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="outline"
                                            className="size-9"
                                            onClick={() => onQuantityChange(index, 1)}
                                            aria-label={`Tambah ${item.product_name}`}
                                        >
                                            <Plus aria-hidden="true" />
                                        </Button>
                                    </div>
                                    <span className="font-medium whitespace-nowrap">{formatRupiah(item.price * item.quantity)}</span>
                                </div>

                                <div className="mt-2 space-y-1">
                                    <Label htmlFor={`takeaway-item-notes-${index}`} className="text-xs">
                                        Catatan item
                                    </Label>
                                    <Input
                                        id={`takeaway-item-notes-${index}`}
                                        value={item.notes}
                                        maxLength={500}
                                        onChange={(event) => onItemNotesChange(index, event.target.value)}
                                        placeholder="Contoh: tidak pedas"
                                    />
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                {errors.items && <p className="text-destructive mt-2 text-xs">{errors.items}</p>}
            </section>

            <section className="rounded-xl border p-4">
                <div className="space-y-1.5">
                    <Label htmlFor="takeaway-notes">Catatan pesanan</Label>
                    <Textarea
                        id="takeaway-notes"
                        value={notes}
                        maxLength={500}
                        onChange={(event) => onNotesChange(event.target.value)}
                        placeholder="Contoh: dimasak dulu saat pelanggan tiba"
                        rows={2}
                    />
                    {errors.notes && <p className="text-destructive text-xs">{errors.notes}</p>}
                </div>
            </section>

            <section className="rounded-xl border p-4">
                <div className="flex items-center justify-between gap-4">
                    <span className="text-sm">Total sementara</span>
                    <span className="text-lg font-semibold">{formatRupiah(total)}</span>
                </div>
                <p className="text-muted-foreground mt-1 text-xs">Total final dihitung ulang server saat pesanan dibuat.</p>
                <Button type="submit" className="mt-4 w-full" disabled={submitting || items.length === 0}>
                    {submitting ? 'Menyimpan…' : 'Checkout takeaway'}
                </Button>
            </section>
        </form>
    );
}
