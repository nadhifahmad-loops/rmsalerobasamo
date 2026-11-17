<?php

/**
 * Verifikasi backup/restore portabel untuk Phase 6.
 *
 * Membaca data dari koneksi database aktif, menulis backup JSON ke file
 * sementara, membuat database SQLite isolat, menjalankan migration,
 * mengisi data, lalu membandingkan count dan checksum. Tidak mengubah .env
 * dan tidak menulis ke database sumber.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/** @var list<string> $tables */
$tables = [
    'roles',
    'users',
    'categories',
    'products',
    'product_variants',
    'dining_tables',
    'shifts',
    'orders',
    'order_items',
    'payments',
    'audit_logs',
];

$sourceConnection = (string) Config::get('database.default');
$backupPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pos-backup-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';
$restorePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pos-restore-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sqlite';

/**
 * @param  array<string, array{count: int, rows: list<array<string, mixed>>}>  $tables
 */
function payloadChecksum(array $tables): string
{
    $canonical = [];

    foreach ($tables as $name => $payload) {
        $canonical[$name] = [
            'count' => $payload['count'],
            'rows' => $payload['rows'],
        ];
    }

    return hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

try {
    $exported = [];
    $totalRows = 0;

    foreach ($tables as $table) {
        $rows = DB::connection($sourceConnection)
            ->table($table)
            ->orderBy('id')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();

        $exported[$table] = [
            'count' => count($rows),
            'rows' => $rows,
        ];
        $totalRows += count($rows);
    }

    $sourceChecksum = payloadChecksum($exported);
    $backup = [
        'version' => 1,
        'generated_at' => now()->toJSON(),
        'source_connection' => $sourceConnection,
        'checksum' => $sourceChecksum,
        'tables' => $exported,
    ];

    if (file_put_contents($backupPath, json_encode($backup, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) === false) {
        throw new RuntimeException('Gagal menulis file backup sementara.');
    }

    touch($restorePath);

    Config::set('database.default', 'sqlite');
    Config::set('database.connections.sqlite.database', $restorePath);
    DB::purge('sqlite');
    DB::reconnect('sqlite');

    Artisan::call('migrate:fresh', [
        '--force' => true,
        '--no-interaction' => true,
    ]);

    foreach ($exported as $table => $payload) {
        if ($payload['rows'] === []) {
            continue;
        }

        $columns = array_keys($payload['rows'][0]);
        $chunkSize = max(1, (int) floor(900 / max(1, count($columns))));

        foreach (array_chunk($payload['rows'], $chunkSize) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    $restored = [];
    foreach ($tables as $table) {
        $rows = DB::table($table)
            ->orderBy('id')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();

        $restored[$table] = [
            'count' => count($rows),
            'rows' => $rows,
        ];
    }

    $restoredChecksum = payloadChecksum($restored);
    $violations = DB::select('PRAGMA foreign_key_check');

    echo "source_connection={$sourceConnection}\n";
    echo 'backup_file='.basename($backupPath)."\n";
    echo 'restore_db='.basename($restorePath)."\n";
    echo "rows={$totalRows}\n";

    foreach ($tables as $table) {
        $sourceCount = $exported[$table]['count'];
        $restoredCount = $restored[$table]['count'];

        if ($sourceCount !== $restoredCount) {
            throw new RuntimeException("Count mismatch pada {$table}: source={$sourceCount}, restore={$restoredCount}");
        }

        echo "{$table}={$restoredCount}\n";
    }

    if ($sourceChecksum !== $restoredChecksum) {
        throw new RuntimeException("Checksum mismatch: source={$sourceChecksum}, restore={$restoredChecksum}");
    }

    if ($violations !== []) {
        throw new RuntimeException('FK violations ditemukan: '.count($violations));
    }

    echo "source_checksum={$sourceChecksum}\n";
    echo "restored_checksum={$restoredChecksum}\n";
    echo "fk_violations=0\n";
    echo "RESULT=OK\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'RESULT=FAILED: '.$exception->getMessage()."\n");
    exit(1);
} finally {
    if (is_file($backupPath)) {
        @unlink($backupPath);
    }

    if (is_file($restorePath)) {
        @unlink($restorePath);
    }
}
