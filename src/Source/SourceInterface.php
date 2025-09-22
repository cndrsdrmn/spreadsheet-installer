<?php

declare(strict_types=1);

namespace Cndrsdrmn\SpreadsheetInstaller\Source;

use Cndrsdrmn\SpreadsheetInstaller\SpreadsheetBinary;

interface SourceInterface
{
    /**
     * Download the source.
     */
    public function download(SpreadsheetBinary $binary): void;

    /**
     * Check if the source is up to date.
     */
    public function isUpToDate(SpreadsheetBinary $binary): bool;

    /**
     * Get the version of the source.
     */
    public function version(): ?string;
}
