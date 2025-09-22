<?php

declare(strict_types=1);

namespace Cndrsdrmn\SpreadsheetInstaller;

use Cndrsdrmn\SpreadsheetInstaller\Source\SourceInterface;

/**
 * @internal
 */
final readonly class Installer
{
    /**
     * Create a new class instance.
     */
    public function __construct(private SpreadsheetBinary $binary, private SourceInterface $source)
    {
        //
    }

    /**
     * Check if the binary is up to date.
     */
    public function isUpToDate(): bool
    {
        return $this->source->isUpToDate($this->binary);
    }

    /**
     * Remove the binary.
     */
    public function remove(): void
    {
        $this->binary->remove();
    }

    /**
     * Run the installer.
     */
    public function run(): void
    {
        $this->source->download($this->binary);
    }
}
