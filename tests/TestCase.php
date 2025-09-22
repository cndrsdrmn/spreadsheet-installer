<?php

declare(strict_types=1);

namespace Tests;

use Cndrsdrmn\SpreadsheetInstaller\Source\SourceInterface;
use Cndrsdrmn\SpreadsheetInstaller\SpreadsheetBinary;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Filesystem\Filesystem;

abstract class TestCase extends BaseTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        $fs = new Filesystem;
        $fs->remove('path');

        parent::tearDown();
    }

    /**
     * Create a new SpreadsheetBinary instance.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function binaryInstance(
        string $binPath = 'path/to/bin',
        string $vendorBinPath = 'path/to/vendor/bin',
        ?Filesystem $fs = null,
        string $binary = 'test-bin'
    ): SpreadsheetBinary {
        return new SpreadsheetBinary(
            binPath: $binPath,
            vendorBinPath: $vendorBinPath,
            fs: $fs ?? $this->mockFilesystem(),
            binary: $binary
        );
    }

    /**
     * Create a new mock filesystem instance.
     */
    protected function mockFilesystem(): MockObject
    {
        return $this->getMockBuilder(Filesystem::class)->getMock();
    }

    /**
     * Create a new mock source instance.
     */
    protected function mockSource(): MockObject
    {
        return $this->getMockBuilder(SourceInterface::class)->getMock();
    }
}
