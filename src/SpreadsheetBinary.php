<?php

declare(strict_types=1);

namespace Cndrsdrmn\SpreadsheetInstaller;

use Composer\IO\IOInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use ZipArchive;

final readonly class SpreadsheetBinary
{
    /**
     * The binary directory path.
     */
    private string $binPath;

    /**
     * The vendor binary directory path.
     */
    private string $vendorBinPath;

    /**
     * Create a new class instance.
     */
    public function __construct(
        string $binPath,
        string $vendorBinPath,
        private Filesystem $fs = new Filesystem,
        private string $binary = 'spreadsheet'
    ) {
        $this->binPath = rtrim($binPath, DIRECTORY_SEPARATOR);
        $this->vendorBinPath = rtrim($vendorBinPath, DIRECTORY_SEPARATOR);

        if (! $this->fs->exists($this->binPath)) {
            $this->fs->mkdir($this->binPath);
        }
    }

    /**
     * Change the binary permissions.
     */
    public function chmod(int $mode, bool $force = false): void
    {
        if (! $this->exists() && ! $force) {
            return;
        }

        if (! $this->exists() && $force) {
            $this->fs->touch($this->path());
        }

        $this->fs->chmod($this->path(), $mode);
    }

    /**
     * Check if the given path exists and is executable.
     */
    public function exists(): bool
    {
        $path = $this->path();

        if (! $this->fs->exists($path)) {
            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $ext = mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));

            return in_array($ext, ['exe', 'bat', 'cmd'], true);
        }

        return is_executable($path);
    }

    /**
     * Install the binary from the stream.
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function installFromStream(ResponseStreamInterface $stream, IOInterface $io, string $filename): void
    {
        $archive = $this->fs->tempnam(sys_get_temp_dir(), 'spreadsheet_'.$filename);
        $resource = null;

        try {
            $resource = $this->writeStreamToFile($stream, $archive);

            $io->write('<info>Extracting archive...</info>');

            $this->extractFromArchive($archive, match (true) {
                str_ends_with($filename, '.zip') => 'zip',
                str_ends_with($filename, '.tar.gz') => 'tar.gz',
                default => throw new RuntimeException("Unsupported archive extension: {$filename}."),
            });

            if (! str_contains($filename, 'Windows') || PHP_OS_FAMILY !== 'Windows') {
                $this->chmod(mode: 0755, force: true);
            }

            $this->fs->copy($this->path(), $this->vendorPath(), true);

            $io->write("<info>Spreadsheet binary installed successfully at {$this->path()}.</info>");
        } finally {
            if (is_resource($resource)) {
                fclose($resource);
            }

            if ($this->fs->exists($archive)) {
                $this->fs->remove($archive);
            }
        }
    }

    /**
     * Get the binary path.
     */
    public function path(): string
    {
        return $this->binPath.DIRECTORY_SEPARATOR.$this->binary;
    }

    /**
     * Remove the binary.
     */
    public function remove(): void
    {
        if ($this->fs->exists($this->binPath)) {
            $this->fs->remove($this->binPath);
        }

        if ($this->fs->exists($this->vendorPath())) {
            $this->fs->remove($this->vendorPath());
        }
    }

    /**
     * Get the vendor binary path.
     */
    public function vendorPath(): string
    {
        return $this->vendorBinPath.DIRECTORY_SEPARATOR.$this->binary;
    }

    /**
     * Get the binary version.
     */
    public function version(): ?string
    {
        if (! $this->exists()) {
            return null;
        }

        $process = new Process([$this->path(), '--version']);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        return trim((string) preg_replace('/^spreadsheet version ([\d.]+)$/', '$1', $process->getOutput()));
    }

    /**
     * Extract the binary from the archive.
     */
    private function extractFromArchive(string $archive, string $extension): void
    {
        if ($extension === 'zip') {
            $zip = new ZipArchive;

            if ($zip->open($archive) !== true) {
                throw new RuntimeException("Failed to open ZIP archive: {$archive}.");
            }

            if (! $zip->extractTo($this->binPath)) {
                throw new RuntimeException("Failed to extract ZIP archive: {$archive}.");
            }

            $zip->close();

            return;
        }

        if ($extension === 'tar.gz') {
            $process = new Process(['tar', '-xzf', $archive, '-C', $this->binPath], timeout: null);
            $process->mustRun();

            return;
        }

        throw new RuntimeException("Unsupported archive extension: {$extension}.");
    }

    /**
     * Write the stream to a file.
     *
     * @return resource
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function writeStreamToFile(ResponseStreamInterface $stream, string $destination)
    {
        $resource = @fopen($destination, 'w+');

        if ($resource === false) {
            throw new RuntimeException("Failed to open file: {$destination}.");
        }

        foreach ($stream as $chunk) {
            if (fwrite($resource, $chunk->getContent()) === false) {
                throw new RuntimeException("Failed to write to file: {$destination}.");
            }
        }

        if (fflush($resource) === false) {
            throw new RuntimeException("Failed to flush file: {$destination}.");
        }

        return $resource;
    }
}
