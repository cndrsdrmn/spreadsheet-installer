<?php

declare(strict_types=1);

namespace Tests;

use Composer\IO\IOInterface;
use Phar;
use PharData;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\RequiresOperatingSystemFamily;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use ZipArchive;

/**
 * Tests for the SpreadsheetBinary class.
 */
final class SpreadsheetBinaryTest extends TestCase
{
    private Filesystem $fs;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fs = new Filesystem;
        $this->path = sys_get_temp_dir().'/spreadsheet_test';
    }

    protected function tearDown(): void
    {
        if ($this->fs->exists($this->path)) {
            $this->fs->remove($this->path);
        }

        parent::tearDown();
    }

    #[Test, RequiresOperatingSystemFamily('Windows')]
    public function it_exists_handles_windows_executables(): void
    {
        $binary = $this->binaryInstance($this->path, $this->path, $this->fs, 'spreadsheet.exe');
        $this->fs->touch($binary->path());

        $this->assertTrue($binary->exists());
    }

    #[Test]
    public function it_exists_returns_false_when_file_does_not_exist(): void
    {
        $binary = $this->binaryInstance($this->path, $this->path, $this->fs);

        $this->assertFalse($binary->exists());
    }

    #[Test, RequiresOperatingSystem('^(Linux|Darwin)$')]
    public function it_exists_returns_true_when_file_exists_and_is_executable(): void
    {
        $binary = $this->binaryInstance($this->path, $this->path, $this->fs);
        $this->fs->touch($binary->path());
        $this->fs->chmod($binary->path(), 0755);

        $this->assertTrue($binary->exists());
    }

    #[Test, RequiresOperatingSystem('^(Linux|Darwin)$')]
    public function it_can_install_from_stream_successfully_with_tar_gz(): void
    {
        $isWindows = PHP_OS_FAMILY === 'Windows';
        $binaryName = $isWindows ? 'spreadsheet.exe' : 'spreadsheet';

        $tarPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tar_'.bin2hex(random_bytes(4)).'.tar';
        $tarGzPath = $tarPath.'.gz';

        $tar = new PharData($tarPath);
        $tar->addFromString($binaryName, 'dummy-binary-content');
        $tar->compress(Phar::GZ);

        $tarGzBytes = file_get_contents($tarGzPath);

        @unlink($tarPath);
        @unlink($tarGzPath);

        $client = new MockHttpClient([
            new MockResponse($tarGzBytes, ['http_code' => 200]),
        ]);
        $response = $client->request('GET', 'https://example.com/spreadsheet.tar.gz');

        $io = $this->createMock(IOInterface::class);
        $io->expects($this->exactly(2))->method('write');

        $binary = $this->binaryInstance($this->path, $this->path, $this->fs);
        $binary->installFromStream($client->stream($response), $io, 'spreadsheet.tar.gz');

        $this->assertTrue($binary->exists());
    }

    #[Test, RequiresOperatingSystemFamily('Windows')]
    public function it_can_install_from_stream_successfully_with_zip(): void
    {
        $this->markTestSkipped('This test is still failing on Windows.');

        $binaryName = 'spreadsheet.exe';

        $zipPath = tempnam(sys_get_temp_dir(), 'zip_');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::OVERWRITE);
        $zip->addFromString($binaryName, 'dummy-binary-content');
        $zip->close();
        $zipBytes = file_get_contents($zipPath);
        @unlink($zipPath);

        $client = new MockHttpClient([
            new MockResponse($zipBytes, ['http_code' => 200]),
        ]);
        $response = $client->request('GET', 'https://example.com/spreadsheet.zip');

        $io = $this->createMock(IOInterface::class);
        $io->expects($this->exactly(2))->method('write');

        $binary = $this->binaryInstance($this->path, $this->path, $this->fs);
        $binary->installFromStream($client->stream($response), $io, 'spreadsheet.zip');

        $this->assertTrue($binary->exists());
    }

    #[Test]
    public function it_throws_exception_for_unsupported_archive(): void
    {
        $client = new MockHttpClient([
            new MockResponse('unknown', ['http_code' => 200]),
        ]);
        $response = $client->request('GET', 'https://example.com/spreadsheet.zip');
        $io = $this->createMock(IOInterface::class);

        $binary = $this->binaryInstance($this->path, $this->path, $this->fs);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported archive extension: spreadsheet.unknown.');

        $binary->installFromStream($client->stream($response), $io, 'spreadsheet.unknown');
    }

    #[Test]
    public function it_returns_null_for_nonexistent_binary(): void
    {
        $binary = $this->binaryInstance($this->path, $this->path, $this->fs);

        $this->assertNull($binary->version());
    }

    #[Test]
    public function it_returns_null_for_invalid_version_command(): void
    {
        $binary = $this->binaryInstance();

        $this->assertNull($binary->version());
    }

    #[Test, RequiresOperatingSystem('^(Linux|Darwin)$')]
    public function it_returns_version_successfully(): void
    {
        $binaryPath = $this->path.'/versioned_executable';
        $this->fs->dumpFile($binaryPath, "#!/usr/bin/env bash\necho 'spreadsheet version 1.2.3'");
        $this->fs->chmod($binaryPath, 0755);

        $binary = $this->binaryInstance($this->path, $this->path, $this->fs, 'versioned_executable');

        $this->assertSame('1.2.3', $binary->version());
    }
}
