<?php

declare(strict_types=1);

namespace SonsOfPHP\Bard\Operation\Composer\Root;

use SonsOfPHP\Bard\JsonFileInterface;
use SonsOfPHP\Bard\Operation\OperationInterface;

/**
 * @author Joshua Estes <joshua@sonsofphp.com>
 */
final readonly class UpdateAutoloadSectionOperation implements OperationInterface
{
    public function __construct(private JsonFileInterface $pkgComposerJsonFile) {}

    public function apply(JsonFileInterface $rootComposerJsonFile): JsonFileInterface
    {
        $rootDir = $rootComposerJsonFile->getPath();
        $pkgDir  = $this->pkgComposerJsonFile->getPath();
        $path    = trim(str_replace($rootDir, '', $pkgDir), '/');

        $rootAutoloadSection = $rootComposerJsonFile->getSection('autoload');
        $pkgAutoloadSection  = $this->pkgComposerJsonFile->getSection('autoload');

        foreach ($pkgAutoloadSection as $section => $config) {
            if ('psr-4' === $section) {
                if (!isset($rootAutoloadSection['psr-4'])) {
                    $rootAutoloadSection['psr-4'] = [];
                }

                foreach ($config as $namespace => $pkgPath) {
                    $rootAutoloadSection['psr-4'][$namespace] = trim($path . '/' . trim((string) $pkgPath, '/'), '/');
                }
            }

            if ('exclude-from-classmap' === $section) {
                if (!isset($rootAutoloadSection['exclude-from-classmap'])) {
                    $rootAutoloadSection['exclude-from-classmap'] = [];
                }

                foreach ($config as $pkgPath) {
                    $rootAutoloadSection['exclude-from-classmap'][] = trim($path . '/' . trim((string) $pkgPath, '/'), '/');
                }
            }
        }

        if (isset($rootAutoloadSection['psr-4'])) {
            $rootAutoloadSection['psr-4'] = array_unique($rootAutoloadSection['psr-4']);
            ksort($rootAutoloadSection['psr-4']);
        }

        if (isset($rootAutoloadSection['exclude-from-classmap'])) {
            $rootAutoloadSection['exclude-from-classmap'] = array_unique($rootAutoloadSection['exclude-from-classmap']);
            ksort($rootAutoloadSection['exclude-from-classmap']);
        }

        ksort($rootAutoloadSection);

        return $rootComposerJsonFile->setSection('autoload', $rootAutoloadSection);
    }
}
