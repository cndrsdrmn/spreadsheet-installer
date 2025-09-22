<?php

declare(strict_types=1);

namespace Tests;

use Cndrsdrmn\SpreadsheetInstaller\Installer;
use PHPUnit\Framework\Attributes\Test;

final class InstallerTest extends TestCase
{
    #[Test]
    public function it_installer_checks_source_for_up_to_date_status(): void
    {
        $binary = $this->binaryInstance();

        $source = $this->mockSource();
        $source->expects($this->once())->method('isUpToDate')->with($binary)->willReturn(true);

        /** @var \Cndrsdrmn\SpreadsheetInstaller\Source\SourceInterface $source */
        $installer = new Installer($binary, $source);

        $this->assertTrue($installer->isUpToDate());
    }

    #[Test]
    public function it_installer_integration_with_source(): void
    {
        $binary = $this->binaryInstance();

        $source = $this->mockSource();
        $source->expects($this->once())->method('download')->with($binary);

        /** @var \Cndrsdrmn\SpreadsheetInstaller\Source\SourceInterface $source */
        $installer = new Installer($binary, $source);
        $installer->run();
    }

    #[Test]
    public function it_installer_successfully_removes_files(): void
    {
        $fs = $this->mockFilesystem();
        $fs->expects($this->atLeast(2))->method('exists')->willReturn(true);
        $fs->expects($this->atLeast(2))->method('remove');

        /** @var \Symfony\Component\Filesystem\Filesystem $fs */
        $binary = $this->binaryInstance(fs: $fs);

        /** @var \Cndrsdrmn\SpreadsheetInstaller\Source\SourceInterface $source */
        $source = $this->mockSource();

        $installer = new Installer($binary, $source);
        $installer->remove();
    }
}
