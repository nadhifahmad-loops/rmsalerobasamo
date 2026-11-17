/** Format Rupiah tunggal untuk seluruh modul katalog. */
export function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number.isFinite(value) ? value : 0);
}

/** Label pagination dari Laravel mengandung entitas HTML; decode tanpa innerHTML. */
export function decodePaginationLabel(label: string): string {
    return label.replaceAll('&laquo;', '«').replaceAll('&raquo;', '»').replaceAll('&hellip;', '…').replaceAll('&nbsp;', ' ');
}
