<?php

namespace App\Actions\Reports;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Satu aturan batas tanggal untuk riwayat dan laporan (BUSINESS-RULES §9).
 * Tanggal input ditafsirkan sebagai Asia/Jakarta, lalu dikonversi ke UTC
 * sesuai penyimpanan timestamp Laravel.
 */
final class ResolveDateRange
{
    public const TIMEZONE = 'Asia/Jakarta';

    /**
     * @return array{from: CarbonImmutable, to: CarbonImmutable, from_label: string, to_label: string}
     */
    public function handle(?string $from, ?string $to): array
    {
        $fromDate = $this->parse($from) ?? CarbonImmutable::today(self::TIMEZONE);
        $toDate = $this->parse($to) ?? $fromDate;

        if ($fromDate->greaterThan($toDate)) {
            throw ValidationException::withMessages([
                'to' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
            ]);
        }

        // Timestamp disimpan memakai APP_TIMEZONE (Asia/Jakarta pada aplikasi ini).
        // Batas hari dihitung di timezone usaha lalu dikonversi ke timezone
        // penyimpanan agar query tetap benar bila konfigurasi berubah.
        $storageTimezone = (string) config('app.timezone', 'UTC');

        return [
            'from' => $fromDate->startOfDay()->setTimezone($storageTimezone),
            'to' => $toDate->endOfDay()->setTimezone($storageTimezone),
            'from_label' => $fromDate->toDateString(),
            'to_label' => $toDate->toDateString(),
        ];
    }

    private function parse(?string $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, self::TIMEZONE);

        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }
}
