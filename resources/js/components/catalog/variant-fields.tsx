import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatRupiah } from '@/lib/format';
import type { VariantFormValue } from '@/types/catalog';

interface VariantFieldsProps {
    value: VariantFormValue;
    onChange: (next: VariantFormValue) => void;
    errors?: { name?: string; price?: string };
    idPrefix: string;
    defaultRadioName: string;
}

/** FieldGroup variasi yang dipakai ulang saat tambah produk dan edit variasi. */
export function VariantFields({ value, onChange, errors, idPrefix, defaultRadioName }: VariantFieldsProps) {
    const priceAsNumber = typeof value.price === 'number' ? value.price : Number(value.price);

    return (
        <div className="space-y-3">
            <div className="grid gap-3 sm:grid-cols-2">
                <div className="space-y-1.5">
                    <Label htmlFor={`${idPrefix}-name`}>Nama variasi</Label>
                    <Input
                        id={`${idPrefix}-name`}
                        value={value.name}
                        onChange={(event) => onChange({ ...value, name: event.target.value })}
                        placeholder="Contoh: 1 potong"
                        autoComplete="off"
                    />
                    <InputError message={errors?.name} />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor={`${idPrefix}-price`}>Harga (Rp)</Label>
                    <Input
                        id={`${idPrefix}-price`}
                        type="number"
                        inputMode="numeric"
                        min={0}
                        step={1000}
                        value={value.price}
                        onChange={(event) =>
                            onChange({
                                ...value,
                                price: event.target.value === '' ? '' : Number(event.target.value),
                            })
                        }
                    />
                    <p className="text-muted-foreground text-xs">{formatRupiah(priceAsNumber)}</p>
                    <InputError message={errors?.price} />
                </div>
            </div>

            <div className="flex flex-wrap gap-x-5 gap-y-2">
                <label htmlFor={`${idPrefix}-default`} className="flex min-h-9 cursor-pointer items-center gap-2 text-sm">
                    <input
                        id={`${idPrefix}-default`}
                        type="radio"
                        name={defaultRadioName}
                        checked={value.is_default}
                        onChange={() => onChange({ ...value, is_default: true })}
                        className="size-4"
                    />
                    Default
                </label>

                <label htmlFor={`${idPrefix}-active`} className="flex min-h-9 cursor-pointer items-center gap-2 text-sm">
                    <input
                        id={`${idPrefix}-active`}
                        type="checkbox"
                        checked={value.is_active}
                        onChange={(event) => onChange({ ...value, is_active: event.target.checked })}
                        className="border-input size-4 rounded"
                    />
                    Aktif
                </label>

                <label htmlFor={`${idPrefix}-available`} className="flex min-h-9 cursor-pointer items-center gap-2 text-sm">
                    <input
                        id={`${idPrefix}-available`}
                        type="checkbox"
                        checked={value.is_available}
                        onChange={(event) => onChange({ ...value, is_available: event.target.checked })}
                        className="border-input size-4 rounded"
                    />
                    Tersedia
                </label>
            </div>
        </div>
    );
}
