<?php

declare(strict_types=1);

namespace Cndrsdrmn\SpreadsheetInstaller\Source;

use Cndrsdrmn\SpreadsheetInstaller\SpreadsheetBinary;
use Composer\IO\IOInterface;
use RuntimeException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;

/**
 * @internal
 */
final class GitHubSource implements SourceInterface
{
    /**
     * The GitHub API URL.
     */
    private const GITHUB_URL = 'https://api.github.com/repos/%s/releases/latest';

    /**
     * The response from the GitHub API.
     *
     * @var array<string, mixed>|null
     */
    private ?array $response = null;

    /**
     * The HTTP client instance.
     */
    private readonly HttpClientInterface $client;

    /**
     * Create a new class instance.
     */
    public function __construct(private readonly IOInterface $io, private readonly string $repo, ?HttpClientInterface $client = null)
    {
        $this->client = $client ?? HttpClient::create([
            'headers' => [
                'Accept' => 'application/vnd.github+json',
                'User-Agent' => 'ComposerSpreadsheetInstaller',
            ],
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function download(SpreadsheetBinary $binary): void
    {
        [$filename, $link] = $this->retrieveDownloadInfo();

        try {
            $this->io->write("<info>Downloading archive from GitHub: {$link}</info>");

            $response = $this->request('GET', $link);

            $binary->installFromStream($this->client->stream($response), $this->io, $filename);
        } catch (Throwable $e) {
            $this->io->writeError("<error>Failed to download archive from GitHub: {$e->getMessage()}</error>");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isUpToDate(SpreadsheetBinary $binary): bool
    {
        $current = $binary->version();
        $version = $this->version();

        if ($current !== null && $version !== null && version_compare($current, $version, '>=')) {
            $this->io->write('<info>Spreadsheet is already up to date.</info>');

            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function version(): ?string
    {
        $response = $this->fetch();

        if (isset($response['tag_name']) && is_string($response['tag_name'])) {
            return ltrim($response['tag_name'], 'v');
        }

        return null;
    }

    /**
     * Fetch the latest release from GitHub.
     *
     * @return array<string, mixed>
     */
    private function fetch(): array
    {
        if ($this->response !== null) {
            return $this->response;
        }

        $url = sprintf(self::GITHUB_URL, $this->repo);

        try {
            $response = $this->request('GET', $url);

            return $this->response = $response->toArray();
        } catch (Throwable $e) {
            $this->io->writeError("<error>Failed to fetch the latest release from GitHub: {$e->getMessage()}</error>");
        }

        return [];
    }

    /**
     * Get the platform-specific asset name.
     */
    private function platform(): string
    {
        $arch = mb_strtolower(php_uname('m'));
        $arch = match ($arch) {
            'x86_64', 'amd64' => 'x86_64',
            'arm64', 'aarch64' => 'arm64',
            default => throw new RuntimeException('Unsupported architecture: '.$arch),
        };

        $os = mb_strtolower(PHP_OS_FAMILY);

        return match ($os) {
            'windows' => 'Windows_'.$arch.'.zip',
            'darwin' => 'Darwin_'.$arch.'.tar.gz',
            'linux' => 'Linux_'.$arch.'.tar.gz',
            default => throw new RuntimeException('Unsupported OS: '.$os),
        };
    }

    /**
     * Make a request to the GitHub API.
     *
     * @param  array<string, mixed>  $options
     */
    private function request(string $method, string $url, array $options = []): ResponseInterface
    {
        try {
            $response = $this->client->request($method, $url, $options);

            $status = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw new RuntimeException("Failed to request GitHub API: {$e->getMessage()}.", $e->getCode(), $e);
        }

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("GitHub API request failed ({$status}): {$url}");
        }

        return $response;
    }

    /**
     * Retrieve the download information.
     *
     * @return array{0: string, 1: string}
     */
    private function retrieveDownloadInfo(): array
    {
        $response = $this->fetch();

        if (! isset($response['assets']) || ! is_array($response['assets'])) {
            throw new RuntimeException('No assets found in the latest release.');
        }

        $platform = $this->platform();

        foreach ($response['assets'] as $asset) {
            $name = $asset['name'] ?? null;
            if (! is_string($name)) {
                continue;
            }

            $link = $asset['browser_download_url'] ?? null;
            if (! is_string($link)) {
                continue;
            }

            if (str_contains($name, $platform)) {
                return [$name, $link];
            }
        }

        throw new RuntimeException("No matching asset found for platform {$platform}.");
    }
}
