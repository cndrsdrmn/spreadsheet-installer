<?php

declare(strict_types=1);

namespace Tests\Source;

use Cndrsdrmn\SpreadsheetInstaller\Source\GitHubSource;
use Composer\IO\IOInterface;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\TestCase;

final class GitHubSourceTest extends TestCase
{
    #[Test]
    public function it_download_handles_exception_gracefully(): void
    {
        $this->expectException(RuntimeException::class);

        $io = $this->getMockBuilder(IOInterface::class)->getMock();
        $io->expects($this->once())->method('writeError');

        $client = new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ]);

        $source = new GitHubSource($io, 'cndrsdrmn/spreadsheet-installer', $client);

        $source->download($this->binaryInstance());
    }

    #[Test]
    public function it_download_successfully_fetches_and_installs_binary(): void
    {
        $io = $this->getMockBuilder(IOInterface::class)->getMock();
        $io->expects($this->once())->method('write');

        $client = new MockHttpClient([
            new MockResponse(json_encode([
                'assets' => [
                    ['name' => 'Darwin_x86_64.tar.gz', 'browser_download_url' => 'https://example.com/spreadsheet.tar.gz'],
                    ['name' => 'Linux_x86_64.tar.gz', 'browser_download_url' => 'https://example.com/spreadsheet.tar.gz'],
                    ['name' => 'Windows_x86_64.zip', 'browser_download_url' => 'https://example.com/spreadsheet.zip'],
                    ['name' => 'Darwin_arm64.tar.gz', 'browser_download_url' => 'https://example.com/spreadsheet.tar.gz'],
                    ['name' => 'Linux_arm64.tar.gz', 'browser_download_url' => 'https://example.com/spreadsheet.tar.gz'],
                    ['name' => 'Windows_arm64.zip', 'browser_download_url' => 'https://example.com/spreadsheet.zip'],
                ],
            ]), ['http_code' => 200]),
        ]);

        $source = new GitHubSource($io, 'cndrsdrmn/spreadsheet-installer', $client);

        $source->download($this->binaryInstance());
    }

    #[Test, RequiresOperatingSystem('^(Linux|Darwin)$')]
    public function it_recognizes_when_binary_is_up_to_date(): void
    {
        $io = $this->getMockBuilder(IOInterface::class)->getMock();
        $io->expects($this->once())->method('write')
            ->with($this->stringContains('Spreadsheet is already up to date.'));

        $fs = new Filesystem;
        $binary = $this->binaryInstance(fs: $fs);
        $fs->dumpFile($binary->path(), "#!/usr/bin/env bash\necho 'spreadsheet version 1.0.0'");
        $fs->chmod($binary->path(), 0755);

        $client = new MockHttpClient([
            new MockResponse(json_encode(['tag_name' => 'v1.0.0']), ['http_code' => 200]),
        ]);

        $source = new GitHubSource($io, 'cndrsdrmn/spreadsheet-installer', $client);

        $this->assertTrue($source->isUpToDate($binary));
    }

    #[Test]
    public function it_recognizes_when_binary_is_not_up_to_date(): void
    {
        $io = $this->getMockBuilder(IOInterface::class)->getMock();
        $io->expects($this->never())->method('write');

        $fs = new Filesystem;
        $binary = $this->binaryInstance(fs: $fs);
        $fs->dumpFile($binary->path(), "#!/usr/bin/env bash\necho 'spreadsheet version 0.9.0'");

        $client = new MockHttpClient([
            new MockResponse(json_encode(['tag_name' => 'v1.0.0']), ['http_code' => 200]),
        ]);

        $source = new GitHubSource($io, 'cndrsdrmn/spreadsheet-installer', $client);

        $this->assertFalse($source->isUpToDate($binary));
    }

    #[Test]
    public function it_handles_binary_with_no_version_as_outdated(): void
    {
        $io = $this->getMockBuilder(IOInterface::class)->getMock();
        $io->expects($this->never())->method('write');

        $binary = $this->binaryInstance();

        $client = new MockHttpClient([
            new MockResponse(json_encode(['tag_name' => 'v1.0.0']), ['http_code' => 200]),
        ]);

        $source = new GitHubSource($io, 'cndrsdrmn/spreadsheet-installer', $client);

        $this->assertFalse($source->isUpToDate($binary));
    }

    #[Test]
    public function it_returns_correct_version_when_tag_name_is_present(): void
    {
        $io = $this->getMockBuilder(IOInterface::class)->getMock();

        $client = new MockHttpClient([
            new MockResponse(json_encode(['tag_name' => 'v1.2.3']), ['http_code' => 200]),
        ]);

        $source = new GitHubSource($io, 'cndrsdrmn/spreadsheet-installer', $client);

        $this->assertEquals('1.2.3', $source->version());
    }

    #[Test]
    public function it_returns_null_when_tag_name_is_missing(): void
    {
        $io = $this->getMockBuilder(IOInterface::class)->getMock();

        $client = new MockHttpClient([
            new MockResponse(json_encode([]), ['http_code' => 200]),
        ]);

        $source = new GitHubSource($io, 'cndrsdrmn/spreadsheet-installer', $client);

        $this->assertNull($source->version());
    }

    #[Test]
    public function it_returns_null_when_api_request_fails(): void
    {
        $io = $this->getMockBuilder(IOInterface::class)->getMock();
        $io->expects($this->once())->method('writeError')
            ->with($this->stringContains('Failed to fetch the latest release from GitHub'));

        $client = new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]);

        $source = new GitHubSource($io, 'cndrsdrmn/spreadsheet-installer', $client);

        $this->assertNull($source->version());
    }
}
