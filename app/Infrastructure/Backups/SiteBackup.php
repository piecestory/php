<?php

declare(strict_types=1);

namespace App\Infrastructure\Backups;

use Carbon\CarbonImmutable;
use FilesystemIterator;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Daily copy of what cannot be rebuilt from the code: the database (gzipped SQL) and uploaded files
 * (catalogue images and private request photos). Kept on the server under storage/app/backups, never
 * served by URL; sets older than the retention period are removed. Hostinger's own daily backups are
 * the second copy.
 */
final class SiteBackup
{
    public function __construct(
        private readonly string $directory,
        private readonly int $keepDays,
        private readonly string $mysqldump,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) config('backup.directory'),
            (int) config('backup.keep_days'),
            (string) config('backup.mysqldump'),
        );
    }

    /** @return array{database: string, files: string, removed: int} paths of the new set and number of old sets removed */
    public function run(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        File::ensureDirectoryExists($this->directory);
        $stamp = $now->format('Y-m-d_His');

        $database = "{$this->directory}/{$stamp}-database.sql.gz";
        $files = "{$this->directory}/{$stamp}-files.zip";

        $this->dumpDatabase($database);
        $this->archiveUploads($files);

        return ['database' => $database, 'files' => $files, 'removed' => $this->prune($now)];
    }

    private function dumpDatabase(string $target): void
    {
        /** @var array{host: string, port: string|int, database: string, username: string, password: ?string} $db */
        $db = config('database.connections.'.config('database.default'));
        $raw = $target.'.part';

        $process = new Process(
            [$this->mysqldump, '--single-transaction', '--quick', '--no-tablespaces', '--routines', '--default-character-set=utf8mb4',
                '--host='.$db['host'], '--port='.$db['port'], '--user='.$db['username'], '--result-file='.$raw, $db['database']],
            // Password via the environment, so it never appears in the process list or the logs.
            env: ['MYSQL_PWD' => (string) ($db['password'] ?? '')],
            timeout: 600,
        );
        $process->run();

        if (! $process->isSuccessful() || ! is_file($raw)) {
            File::delete($raw);
            throw new RuntimeException('Database backup failed: '.trim($process->getErrorOutput()));
        }

        $in = fopen($raw, 'rb');
        $out = gzopen($target, 'wb6');
        if ($in === false || $out === false) {
            throw new RuntimeException('Database backup could not be compressed.');
        }
        while (! feof($in)) {
            gzwrite($out, (string) fread($in, 1 << 20));
        }
        fclose($in);
        gzclose($out);
        File::delete($raw);
    }

    private function archiveUploads(string $target): void
    {
        $zip = new ZipArchive;
        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Files backup could not be created.');
        }

        // Public catalogue/content images and private request photos; not the backups themselves or temp uploads.
        foreach (['public' => storage_path('app/public'), 'private' => storage_path('app/private')] as $prefix => $root) {
            if (! is_dir($root)) {
                continue;
            }
            $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
            /** @var SplFileInfo $file */
            foreach ($items as $file) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                if ($file->isFile() && ! str_starts_with($relative, 'livewire-tmp/') && $file->getFilename() !== '.gitignore') {
                    $zip->addFile($file->getPathname(), "{$prefix}/{$relative}");
                }
            }
        }

        if ($zip->numFiles === 0) {
            $zip->addFromString('README.txt', 'No uploaded files at backup time.');
        }
        $zip->close();
    }

    private function prune(CarbonImmutable $now): int
    {
        $removed = 0;
        $cutoff = $now->subDays($this->keepDays)->getTimestamp();
        foreach (File::files($this->directory) as $file) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}_\d{6}-(database\.sql\.gz|files\.zip)$/', $file->getFilename()) === 1 && $file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $removed++;
            }
        }

        return $removed;
    }
}
