<?php

namespace olvlvl\ComposerAttributeCollector;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Util\Platform;
use olvlvl\ComposerAttributeCollector\Datastore\FileDatastore;
use olvlvl\ComposerAttributeCollector\Datastore\RuntimeDatastore;
use olvlvl\ComposerAttributeCollector\Filter\ContentFilter;
use olvlvl\ComposerAttributeCollector\Filter\InterfaceFilter;

use function file_put_contents;
use function microtime;
use function sprintf;

use const DIRECTORY_SEPARATOR;

/**
 * @internal
 */
final class Plugin implements PluginInterface, EventSubscriberInterface
{
    public const CACHE_DIR = '.composer-attribute-collector';
    public const VERSION_MAJOR = 2;
    public const VERSION_MINOR = 0;

    /**
     * @uses onPostAutoloadDump
     *
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'post-autoload-dump' => 'onPostAutoloadDump',
        ];
    }

    /**
     * @codeCoverageIgnore
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * @codeCoverageIgnore
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * @codeCoverageIgnore
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public static function onPostAutoloadDump(Event $event): void
    {
        $composer = $event->getComposer();
        $config = Config::from($composer);
        $io = $event->getIO();

        require_once $config->vendorDir . "/autoload.php";

        $start = microtime(true);
        $io->write('<info>Generating attributes file</info>');
        self::dump($config, $io);
        $elapsed = self::renderElapsedTime($start);
        $io->write("<info>Generated attributes file in $elapsed</info>");
    }

    public static function dump(Config $config, IOInterface $io): void
    {
        //
        // Scan include paths
        //
        $start = microtime(true);
        $datastore = self::buildDefaultDatastore($config, $io);
        $classMapGenerator = new MemoizeClassMapGenerator($datastore, $io);
        foreach ($config->include as $include) {
            $classMapGenerator->scanPaths($include, $config->excludeRegExp);
        }
        $classMap = $classMapGenerator->getMap();
        $elapsed = self::renderElapsedTime($start);
        $io->debug("Generating attributes file: scanned paths in $elapsed");

        //
        // Filter the class map
        //
        $start = microtime(true);
        $classMapFilter = new MemoizeClassMapFilter($datastore, $io);
        $filter = self::buildFileFilter();
        $classMap = $classMapFilter->filter(
            $classMap,
            fn (string $class, string $filepath): bool => $filter->filter($filepath, $class, $io)
        );
        $elapsed = self::renderElapsedTime($start);
        $io->debug("Generating attributes file: filtered class map in $elapsed");

        //
        // Collect attributes
        //
        $start = microtime(true);
        $attributeCollector = new MemoizeAttributeCollector(new ClassAttributeCollector($io), $datastore, $io);
        $collection = $attributeCollector->collectAttributes($classMap);
        $elapsed = self::renderElapsedTime($start);
        $io->debug("Generating attributes file: collected attributes in $elapsed");

        //
        // Render attributes
        //
        $start = microtime(true);
        $code = self::render($collection);
        file_put_contents($config->attributesFile, $code);
        $elapsed = self::renderElapsedTime($start);
        $io->debug("Generating attributes file: rendered code in $elapsed");
    }

    private static function buildDefaultDatastore(Config $config, IOInterface $io): Datastore
    {
        if (!$config->useCache) {
            return new RuntimeDatastore();
        }

        $basePath = Platform::getCwd();

        assert($basePath !== '');

        return new FileDatastore($basePath . DIRECTORY_SEPARATOR . self::CACHE_DIR, $io);
    }

    private static function renderElapsedTime(float $start): string
    {
        return sprintf("%.03f ms", (microtime(true) - $start) * 1000);
    }

    private static function buildFileFilter(): Filter
    {
        return new Filter\Chain([
            new ContentFilter(),
            new InterfaceFilter()
        ]);
    }

    private static function render(Collector $collector): string
    {
        return CollectionRenderer::render($collector);
    }
}
