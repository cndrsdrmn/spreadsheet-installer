<?php

declare(strict_types=1);

namespace Cndrsdrmn\SpreadsheetInstaller;

use Cndrsdrmn\SpreadsheetInstaller\Source\GitHubSource;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * @internal
 */
final class Plugin implements EventSubscriberInterface, PluginInterface
{
    /**
     * The spreadsheet binary.
     */
    private SpreadsheetBinary $binary;

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => ['run', 0],
            ScriptEvents::POST_UPDATE_CMD => ['run', 0],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->binary = new SpreadsheetBinary(
            binPath: dirname(__DIR__).DIRECTORY_SEPARATOR.'bin',
            vendorBinPath: $composer->getConfig()->get('bin-dir'),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function deactivate(Composer $composer, IOInterface $io): void {}

    /**
     * Run the installer process.
     */
    public function run(Event $event): void
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        $installer = new Installer(
            binary: $this->binary,
            source: new GitHubSource($event->getIO(), 'cndrsdrmn/go-spreadsheet')
        );

        if ($installer->isUpToDate()) {
            return;
        }

        $installer->run();
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        $this->binary->remove();
    }
}
