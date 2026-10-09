<?php

declare(strict_types=1);

use App\Infrastructure\Alerts\OpsAlert;
use App\Infrastructure\Backups\SiteBackup;
use App\Mail\OpsAlertMail;
use Carbon\CarbonImmutable;
use Database\Seeders\SettingsSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\ExecutableFinder;

beforeEach(function (): void {
    $this->seed(SettingsSeeder::class);
    Mail::fake();
    $this->backups = storage_path('framework/testing/backups');
    File::deleteDirectory($this->backups);
});

afterEach(function (): void {
    File::deleteDirectory($this->backups);
});

/**
 * mysqldump matching the test database, from BACKUP_MYSQLDUMP (or the PATH outside CI). On CI runners the
 * preinstalled MySQL 8 client cannot dump MariaDB, so the real dump runs only where a matching one is set.
 */
function mysqldumpBinary(): ?string
{
    $configured = env('BACKUP_MYSQLDUMP');
    if (is_string($configured) && $configured !== '' && (is_file($configured) || (new ExecutableFinder)->find($configured))) {
        return $configured;
    }
    if (env('CI')) {
        return null;
    }

    return (new ExecutableFinder)->find('mysqldump') ?? (new ExecutableFinder)->find('mariadb-dump');
}

it('schedules the nightly backup and the background work', function (): void {
    $commands = collect(app(Schedule::class)->events())->map(fn ($event) => $event->command)->implode("\n");

    expect($commands)->toContain('backup:run')
        ->toContain('queue:work')
        ->toContain('orders:expire-holds')
        ->toContain('orders:remind-reservations');
});

it('backs up the database and uploaded files, and removes sets past the retention period', function (): void {
    $binary = mysqldumpBinary();
    if ($binary === null) {
        $this->markTestSkipped('mysqldump is not installed here.');
    }

    File::ensureDirectoryExists($this->backups);
    $old = "{$this->backups}/2026-01-01_043000-database.sql.gz";
    File::put($old, 'old');
    touch($old, now()->subDays(20)->getTimestamp());
    $recent = "{$this->backups}/2026-09-30_043000-files.zip";
    File::put($recent, 'recent');
    File::put("{$this->backups}/notes.txt", 'not ours');

    $set = (new SiteBackup($this->backups, 14, $binary))->run(CarbonImmutable::now());

    $sql = (string) gzdecode((string) file_get_contents($set['database']));
    $zip = new ZipArchive;
    expect($sql)->toContain('CREATE TABLE `orders`')->toContain('Dump completed')
        ->and($zip->open($set['files']))->toBeTrue()
        ->and($set['removed'])->toBe(1)
        ->and(file_exists($old))->toBeFalse()
        ->and(file_exists($recent))->toBeTrue()
        ->and(file_exists("{$this->backups}/notes.txt"))->toBeTrue();
});

it('fails loudly and leaves nothing half-written when the database cannot be dumped', function (): void {
    (new SiteBackup($this->backups, 14, 'definitely-not-mysqldump'))->run();
})->throws(RuntimeException::class, 'Database backup failed');

it('emails the team about a problem once, not once per occurrence', function (): void {
    OpsAlert::send('Nightly backup failed', 'disk full');
    OpsAlert::send('Nightly backup failed', 'disk full');
    OpsAlert::send('Server error: QueryException', 'gone away');

    Mail::assertSentCount(2);
    Mail::assertSent(OpsAlertMail::class, fn (OpsAlertMail $mail) => $mail->hasTo('info@php.piecenstory.com') && $mail->alertSubject === 'Nightly backup failed');
});

it('sends alerts to the configured address when there is one', function (): void {
    config(['app.alert_email' => 'tech@example.test']);

    OpsAlert::send('Server error: RuntimeException', 'x');

    Mail::assertSent(OpsAlertMail::class, fn (OpsAlertMail $mail) => $mail->hasTo('tech@example.test'));
});

it('reports server errors to the team only on the live site, without stack traces', function (): void {
    report(new RuntimeException('local problem'));
    Mail::assertNothingSent();

    app()->detectEnvironment(fn () => 'production');
    report(new RuntimeException('Connection refused'));

    Mail::assertSent(OpsAlertMail::class, function (OpsAlertMail $mail): bool {
        return str_contains($mail->alertSubject, 'RuntimeException')
            && str_contains($mail->details, 'Connection refused')
            && ! str_contains($mail->details, '#0 ')
            && ! str_contains($mail->details, base_path());
    });
});

it('never lets an alert hide the original error when the database is not ready', function (): void {
    // As on a first deployment: settings and cache tables do not exist yet.
    app()->bind(App\Domain\Settings\StoreSettings::class, fn () => throw new RuntimeException('Table settings does not exist'));
    config(['cache.default' => 'missing-store']);

    OpsAlert::forException(new RuntimeException('original problem'));

    Mail::assertNothingSent();
});

it('never alerts about visitors\' mistakes such as missing pages', function (): void {
    app()->detectEnvironment(fn () => 'production');

    $this->get('/store/no-such-category-anywhere')->assertNotFound();

    Mail::assertNothingSent();
});
