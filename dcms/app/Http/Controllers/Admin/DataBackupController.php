<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataBackupController extends Controller
{
    private const ALLOCATED_BYTES = 50 * 1024 * 1024 * 1024; // 50 GB

    public function index(Request $request)
    {
        if (!session('admin_logged_in')) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                ], 403);
            }

            return redirect('/admin/login');
        }

        AuditLogger::log(
            'view',
            'data_backup',
            'Admin viewed the data backup page'
        );

        $query = Backup::query()->latest();

        if ($request->filled('scope') && $request->scope === 'month') {
            $query->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month);
        }

        if ($request->filled('type') && in_array($request->type, ['full', 'incremental'], true)) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status') && in_array($request->status, ['completed', 'failed', 'in_progress'], true)) {
            $query->where('status', $request->status);
        }

        $backups = $query->paginate(10)->withQueryString();

        $storageUsedBytes = (int) Backup::sum('size_bytes');
        $fullBackupsBytes = (int) Backup::where('type', 'full')->sum('size_bytes');
        $incrementalBackupsBytes = (int) Backup::where('type', 'incremental')->sum('size_bytes');
        $totalBackups = Backup::count();

        $thisMonthBackups = Backup::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $lastBackup = Backup::where('status', 'completed')->latest()->first();

        /*
         * Hostinger automated backups are managed outside the Laravel system.
         * This section only reflects/admin-maintains the coverage status inside PUPTDMS.
         */
        $hostingerBackup = $this->getHostingerBackupCoverage();

        /*
         * Kept for backward compatibility with the old Blade/JS variables.
         * You can remove this later once the Blade no longer uses Auto-Backup Schedule.
         */
        $autoBackupEnabled = $hostingerBackup['enabled'];

        $backupSchedule = [
            'daily_enabled' => $hostingerBackup['enabled'],
            'daily_time' => SystemSetting::getSetting('backup_schedule_daily_time', '02:00'),
            'weekly_enabled' => $hostingerBackup['enabled'],
            'weekly_time' => SystemSetting::getSetting('backup_schedule_weekly_time', '00:00'),
            'monthly_enabled' => false,
            'monthly_time' => SystemSetting::getSetting('backup_schedule_monthly_time', '23:59'),
        ];

        $totalAllocatedBytes = self::ALLOCATED_BYTES;

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'rows' => $backups->getCollection()->map(function ($backup) {
                    return [
                        'id' => $backup->id,
                        'backup_id' => $backup->backup_id,
                        'type' => $backup->type ?? 'full',
                        'status' => $backup->status ?? 'completed',
                        'size_formatted' => $backup->size_formatted ?? '0 B',
                        'created_at_formatted' => $backup->created_at
                            ? $backup->created_at->format('M d, Y h:i A')
                            : '—',
                        'download_url' => route('admin.data_backup.download', $backup->id),
                    ];
                })->values(),
                'meta' => [
                    'current_page' => $backups->currentPage(),
                    'from' => $backups->firstItem(),
                    'to' => $backups->lastItem(),
                    'total' => $backups->total(),
                    'per_page' => $backups->perPage(),
                    'last_page' => $backups->lastPage(),
                ],
                'stats' => [
                    'total_backups' => $totalBackups,
                    'this_month_backups' => $thisMonthBackups,
                    'last_backup' => $lastBackup?->created_at?->format('M d') ?? '—',
                    'hostinger_backup_status' => $hostingerBackup['status'],
                    'hostinger_backup_status_label' => $hostingerBackup['status_label'],
                    'hostinger_backup_enabled' => $hostingerBackup['enabled'],
                    'hostinger_backup_last_verified_at' => $hostingerBackup['last_verified_at'],
                ],
            ]);
        }

        return view('admin.data-backup', compact(
            'backups',
            'storageUsedBytes',
            'fullBackupsBytes',
            'incrementalBackupsBytes',
            'totalBackups',
            'thisMonthBackups',
            'lastBackup',
            'autoBackupEnabled',
            'totalAllocatedBytes',
            'backupSchedule',
            'hostingerBackup'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        if (!session('admin_logged_in')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $request->validate([
            'type' => 'nullable|in:full,incremental',
        ]);

        $type = $request->input('type', 'full');

        $backupId = 'BKP-' . now()->format('Ymd-His') . '-' . strtoupper(Str::random(4));
        $filename = $backupId . '.sql.gz';
        $filePath = 'backups/' . $filename;

        $backup = Backup::create([
            'backup_id' => $backupId,
            'type' => $type,
            'size_bytes' => 0,
            'file_path' => null,
            'status' => 'in_progress',
        ]);

        $tmpSqlPath = storage_path('app/tmp_' . $backupId . '.sql');
        $tmpGzPath = $tmpSqlPath . '.gz';

        try {
            Storage::disk('local')->makeDirectory('backups');

            /*
             * "incremental" is currently stored as a regular DB dump as well.
             * The type is preserved for UI/history, while true incremental logic can be added later.
             */
            $this->dumpDatabase($tmpSqlPath);
            $this->gzipFile($tmpSqlPath, $tmpGzPath);

            Storage::disk('local')->put($filePath, file_get_contents($tmpGzPath));

            $sizeBytes = Storage::disk('local')->size($filePath);

            $backup->update([
                'size_bytes' => $sizeBytes,
                'file_path' => $filePath,
                'status' => 'completed',
            ]);

            AuditLogger::log(
                'backup',
                'data_backup',
                'Admin created a ' . $type . ' backup: ' . $backupId
            );

            return response()->json([
                'success' => true,
                'message' => "Backup {$backupId} created successfully.",
                'backup' => $backup->fresh(),
            ]);
        } catch (\Throwable $e) {
            $backup->update([
                'status' => 'failed',
            ]);

            AuditLogger::log(
                'error',
                'data_backup',
                'Backup failed for ' . $backupId . ': ' . $e->getMessage()
            );

            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage(),
            ], 500);
        } finally {
            if (file_exists($tmpSqlPath)) {
                @unlink($tmpSqlPath);
            }

            if (file_exists($tmpGzPath)) {
                @unlink($tmpGzPath);
            }
        }
    }

    public function download(int $id): StreamedResponse|JsonResponse
    {
        if (!session('admin_logged_in')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $backup = Backup::findOrFail($id);

        if (!$backup->file_path || !Storage::disk('local')->exists($backup->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Backup file not found on disk.',
            ], 404);
        }

        AuditLogger::log(
            'download',
            'data_backup',
            'Admin downloaded backup: ' . $backup->backup_id
        );

        return Storage::disk('local')->download(
            $backup->file_path,
            basename($backup->file_path),
            ['Content-Type' => 'application/gzip']
        );
    }

    public function restore(int $id): JsonResponse
    {
        if (!session('admin_logged_in')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $backup = Backup::findOrFail($id);

        if (!$backup->file_path || !Storage::disk('local')->exists($backup->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Backup file not found. Cannot restore.',
            ], 404);
        }

        $gzPath = storage_path('app/restore_' . $backup->backup_id . '.sql.gz');
        $sqlPath = storage_path('app/restore_' . $backup->backup_id . '.sql');

        try {
            file_put_contents($gzPath, Storage::disk('local')->get($backup->file_path));

            $gz = gzopen($gzPath, 'rb');
            if ($gz === false) {
                throw new \RuntimeException('Unable to open compressed backup file.');
            }

            $out = fopen($sqlPath, 'wb');
            if ($out === false) {
                gzclose($gz);
                throw new \RuntimeException('Unable to create temporary SQL restore file.');
            }

            while (!gzeof($gz)) {
                fwrite($out, gzread($gz, 4096));
            }

            gzclose($gz);
            fclose($out);

            $this->importDatabase($sqlPath);

            AuditLogger::log(
                'restore',
                'data_backup',
                'Admin restored backup: ' . $backup->backup_id
            );

            return response()->json([
                'success' => true,
                'message' => "Backup {$backup->backup_id} has been restored successfully.",
            ]);
        } catch (\Throwable $e) {
            AuditLogger::log(
                'error',
                'data_backup',
                'Restore failed for ' . $backup->backup_id . ': ' . $e->getMessage()
            );

            return response()->json([
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage(),
            ], 500);
        } finally {
            if (file_exists($gzPath)) {
                @unlink($gzPath);
            }

            if (file_exists($sqlPath)) {
                @unlink($sqlPath);
            }
        }
    }

    public function destroy(int $id): JsonResponse
    {
        if (!session('admin_logged_in')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $backup = Backup::findOrFail($id);
        $backupId = $backup->backup_id;

        if ($backup->file_path && Storage::disk('local')->exists($backup->file_path)) {
            Storage::disk('local')->delete($backup->file_path);
        }

        $backup->delete();

        AuditLogger::log(
            'delete',
            'data_backup',
            'Admin deleted backup: ' . $backupId
        );

        return response()->json([
            'success' => true,
            'message' => "Backup {$backupId} has been deleted.",
        ]);
    }

    /*
     * Redirects admin to Hostinger hPanel.
     * Add a route for this method, then use it for your "Open Hostinger hPanel" button.
     */
    public function openHpanel(): RedirectResponse|JsonResponse
    {
        if (!session('admin_logged_in')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        AuditLogger::log(
            'open',
            'data_backup',
            'Admin opened Hostinger hPanel from the data backup page'
        );

        return redirect()->away($this->getHpanelUrl());
    }

    public function verifyHostingerBackup(Request $request): JsonResponse
    {
        if (!session('admin_logged_in')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        SystemSetting::setSetting('hostinger_backup_enabled', '1', 'backup');
        SystemSetting::setSetting('hostinger_backup_status', 'active', 'backup');
        SystemSetting::setSetting('hostinger_backup_last_verified_at', now()->toDateTimeString(), 'backup');
        SystemSetting::setSetting(
            'hostinger_backup_notes',
            'Daily automatic hosting-level backups are active based on the current Hostinger plan and are managed externally through Hostinger hPanel.',
            'backup'
        );

        /*
         * Legacy setting kept so existing UI/JS that still checks auto_backup_enabled will not break.
         */
        SystemSetting::setSetting('auto_backup_enabled', '1', 'backup');

        AuditLogger::log(
            'verify',
            'data_backup',
            'Admin verified Hostinger daily automatic backup status'
        );

        return response()->json([
            'success' => true,
            'message' => 'Hostinger backup status has been marked as verified.',
            'status' => 'active',
            'status_label' => 'Active',
            'verified_label' => 'Verified',
            'last_verified_at' => now()->format('M d, Y h:i A'),
        ]);
    }

    /*
     * This now marks the Hostinger backup coverage status inside the system.
     * It does not enable/disable Hostinger backups directly.
     */
    public function toggleAuto(Request $request): JsonResponse
    {
        if (!session('admin_logged_in')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $enabled = $request->boolean('enabled');

        SystemSetting::setSetting('hostinger_backup_enabled', $enabled ? '1' : '0', 'backup');
        SystemSetting::setSetting('hostinger_backup_status', $enabled ? 'active' : 'inactive', 'backup');
        SystemSetting::setSetting('hostinger_backup_last_verified_at', now()->toDateTimeString(), 'backup');

        /*
         * Legacy setting kept so existing UI/JS that still checks auto_backup_enabled will not break.
         */
        SystemSetting::setSetting('auto_backup_enabled', $enabled ? '1' : '0', 'backup');

        AuditLogger::log(
            'update',
            'data_backup',
            'Admin marked Hostinger backup coverage as ' . ($enabled ? 'active' : 'inactive')
        );

        return response()->json([
            'success' => true,
            'enabled' => $enabled,
            'status' => $enabled ? 'active' : 'inactive',
            'message' => $enabled
                ? 'Hostinger backup coverage has been marked as active.'
                : 'Hostinger backup coverage has been marked as inactive.',
        ]);
    }

    /*
     * Updated purpose:
     * This saves backup coverage/status information for Hostinger-managed backups.
     * It still accepts the old schedule fields so your current JS will not immediately break.
     */
    public function updateSchedule(Request $request): JsonResponse
    {
        if (!session('admin_logged_in')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $data = $request->validate([
            'hostinger_backup_enabled' => 'nullable|boolean',
            'hostinger_backup_status' => 'nullable|in:active,inactive,not_verified',
            'hostinger_backup_frequency' => 'nullable|string|max:100',
            'hostinger_backup_notes' => 'nullable|string|max:255',

            /*
             * Legacy schedule fields from the old Auto-Backup Schedule UI.
             */
            'daily_enabled' => 'nullable|boolean',
            'daily_time' => 'nullable|date_format:H:i',
            'weekly_enabled' => 'nullable|boolean',
            'weekly_time' => 'nullable|date_format:H:i',
            'monthly_enabled' => 'nullable|boolean',
            'monthly_time' => 'nullable|date_format:H:i',
        ]);

        $enabled = array_key_exists('hostinger_backup_enabled', $data)
            ? filter_var($data['hostinger_backup_enabled'], FILTER_VALIDATE_BOOLEAN)
            : (
                filter_var($data['daily_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ||
                filter_var($data['weekly_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ||
                filter_var($data['monthly_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)
            );

        $status = $data['hostinger_backup_status'] ?? ($enabled ? 'active' : 'inactive');
        $frequency = $data['hostinger_backup_frequency'] ?? SystemSetting::getSetting(
            'hostinger_backup_frequency',
            'Daily or weekly depending on the active Hostinger plan'
        );

        SystemSetting::setSetting('hostinger_backup_enabled', $enabled ? '1' : '0', 'backup');
        SystemSetting::setSetting('hostinger_backup_status', $status, 'backup');
        SystemSetting::setSetting('hostinger_backup_frequency', $frequency, 'backup');
        SystemSetting::setSetting('hostinger_backup_last_verified_at', now()->toDateTimeString(), 'backup');

        if (isset($data['hostinger_backup_notes'])) {
            SystemSetting::setSetting('hostinger_backup_notes', $data['hostinger_backup_notes'], 'backup');
        }

        /*
         * Legacy settings kept for current Blade/JS compatibility.
         */
        if (array_key_exists('daily_enabled', $data)) {
            SystemSetting::setSetting('backup_schedule_daily_enabled', $data['daily_enabled'] ? '1' : '0', 'backup');
        }

        if (isset($data['daily_time'])) {
            SystemSetting::setSetting('backup_schedule_daily_time', $data['daily_time'], 'backup');
        }

        if (array_key_exists('weekly_enabled', $data)) {
            SystemSetting::setSetting('backup_schedule_weekly_enabled', $data['weekly_enabled'] ? '1' : '0', 'backup');
        }

        if (isset($data['weekly_time'])) {
            SystemSetting::setSetting('backup_schedule_weekly_time', $data['weekly_time'], 'backup');
        }

        if (array_key_exists('monthly_enabled', $data)) {
            SystemSetting::setSetting('backup_schedule_monthly_enabled', $data['monthly_enabled'] ? '1' : '0', 'backup');
        }

        if (isset($data['monthly_time'])) {
            SystemSetting::setSetting('backup_schedule_monthly_time', $data['monthly_time'], 'backup');
        }

        SystemSetting::setSetting('auto_backup_enabled', $enabled ? '1' : '0', 'backup');

        AuditLogger::log(
            'update',
            'data_backup',
            'Admin updated Hostinger backup coverage settings'
        );

        return response()->json([
            'success' => true,
            'message' => 'Hostinger backup coverage updated successfully.',
            'hostinger_backup' => $this->getHostingerBackupCoverage(),
            'auto_backup_enabled' => $enabled,
        ]);
    }

    private function getHostingerBackupCoverage(): array
    {
        $enabled = filter_var(
            SystemSetting::getSetting('hostinger_backup_enabled', SystemSetting::getSetting('auto_backup_enabled', '1')),
            FILTER_VALIDATE_BOOLEAN
        );

        $status = SystemSetting::getSetting(
            'hostinger_backup_status',
            $enabled ? 'active' : 'not_verified'
        );

        if (!in_array($status, ['active', 'inactive', 'not_verified'], true)) {
            $status = $enabled ? 'active' : 'not_verified';
        }

        return [
            'enabled' => $enabled,
            'status' => $status,
            'status_label' => match ($status) {
                'active' => 'Active',
                'inactive' => 'Inactive',
                default => 'Not Verified',
            },
            'frequency' => SystemSetting::getSetting(
                'hostinger_backup_frequency',
                'Daily or weekly depending on the active Hostinger plan'
            ),
            'managed_by' => 'Hostinger hPanel',
            'last_verified_at' => SystemSetting::getSetting('hostinger_backup_last_verified_at', null),
            'notes' => SystemSetting::getSetting(
                'hostinger_backup_notes',
                'Daily automatic hosting-level backups are active based on the current Hostinger plan and are managed externally through Hostinger hPanel.'
            ),
            'hpanel_url' => $this->getHpanelUrl(),
        ];
    }

    private function getHpanelUrl(): string
    {
        return rtrim(
            config('services.hostinger.hpanel_url') ?: env('HOSTINGER_HPANEL_URL', 'https://hpanel.hostinger.com'),
            '/'
        );
    }

    private function dumpDatabase(string $outputPath): void
    {
        $db = config('database.default');
        $cfg = config("database.connections.{$db}");

        if (!$cfg) {
            throw new \RuntimeException('Database configuration not found.');
        }

        $driver = $cfg['driver'] ?? null;
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new \RuntimeException('Only MySQL/MariaDB backup is currently supported.');
        }

        $host = $cfg['host'] ?? '127.0.0.1';
        $port = $cfg['port'] ?? 3306;
        $name = $cfg['database'] ?? '';
        $user = $cfg['username'] ?? '';
        $pass = $cfg['password'] ?? '';

        if ($name === '' || $user === '') {
            throw new \RuntimeException('Database credentials are incomplete.');
        }

        $cnfPath = tempnam(sys_get_temp_dir(), 'mysql_');
        if ($cnfPath === false) {
            throw new \RuntimeException('Unable to create temporary MySQL config file.');
        }

        file_put_contents($cnfPath, "[client]\npassword=\"{$pass}\"\n");
        chmod($cnfPath, 0600);

        $mysqldumpPath = env('MYSQLDUMP_PATH', 'mysqldump');

        if ($mysqldumpPath !== 'mysqldump' && !file_exists($mysqldumpPath)) {
            @unlink($cnfPath);
            throw new \RuntimeException('mysqldump executable not found at: ' . $mysqldumpPath);
        }

        $cmd = sprintf(
            '%s --defaults-extra-file=%s --host=%s --port=%s --user=%s --result-file=%s %s 2>&1',
            escapeshellarg($mysqldumpPath),
            escapeshellarg($cnfPath),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($user),
            escapeshellarg($outputPath),
            escapeshellarg($name)
        );

        exec($cmd, $output, $code);

        @unlink($cnfPath);

        if ($code !== 0) {
            throw new \RuntimeException('mysqldump failed: ' . implode("\n", $output));
        }

        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new \RuntimeException('Backup dump file was not created correctly.');
        }
    }

    private function gzipFile(string $sourcePath, string $destPath): void
    {
        $in = fopen($sourcePath, 'rb');
        if ($in === false) {
            throw new \RuntimeException('Unable to open SQL dump for compression.');
        }

        $out = gzopen($destPath, 'wb9');
        if ($out === false) {
            fclose($in);
            throw new \RuntimeException('Unable to create compressed backup file.');
        }

        while (!feof($in)) {
            $chunk = fread($in, 65536);
            if ($chunk === false) {
                fclose($in);
                gzclose($out);
                throw new \RuntimeException('Error while reading SQL dump for compression.');
            }

            gzwrite($out, $chunk);
        }

        fclose($in);
        gzclose($out);
    }

    private function importDatabase(string $sqlPath): void
    {
        $db = config('database.default');
        $cfg = config("database.connections.{$db}");

        if (!$cfg) {
            throw new \RuntimeException('Database configuration not found.');
        }

        $driver = $cfg['driver'] ?? null;
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new \RuntimeException('Only MySQL/MariaDB restore is currently supported.');
        }

        $host = $cfg['host'] ?? '127.0.0.1';
        $port = $cfg['port'] ?? 3306;
        $name = $cfg['database'] ?? '';
        $user = $cfg['username'] ?? '';
        $pass = $cfg['password'] ?? '';

        if ($name === '' || $user === '') {
            throw new \RuntimeException('Database credentials are incomplete.');
        }

        $cnfPath = tempnam(sys_get_temp_dir(), 'mysql_');
        if ($cnfPath === false) {
            throw new \RuntimeException('Unable to create temporary MySQL config file.');
        }

        file_put_contents($cnfPath, "[client]\npassword=\"{$pass}\"\n");
        chmod($cnfPath, 0600);

        $mysqlPath = env('MYSQL_PATH', 'mysql');

        if ($mysqlPath !== 'mysql' && !file_exists($mysqlPath)) {
            @unlink($cnfPath);
            throw new \RuntimeException('mysql executable not found at: ' . $mysqlPath);
        }

        $cmd = sprintf(
            '%s --defaults-extra-file=%s --host=%s --port=%s --user=%s %s < %s 2>&1',
            escapeshellarg($mysqlPath),
            escapeshellarg($cnfPath),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($user),
            escapeshellarg($name),
            escapeshellarg($sqlPath)
        );

        exec($cmd, $output, $code);

        @unlink($cnfPath);

        if ($code !== 0) {
            throw new \RuntimeException('mysql import failed: ' . implode("\n", $output));
        }
    }
}