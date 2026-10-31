<?php
/*
 * FrameVAS - Advanced Import
 * Author: FrameVAS - Artem Vasylenko
 */

use Espo\Core\Container;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;

class AfterInstall
{
    private const DEFAULTS = [
        'advancedImportNotFoundAction' => 'error',
        'advancedImportMultipleAction' => 'error',
    ];

    public function run(Container $container): void
    {
        $config = $container->getByClass(Config::class);

        $configWriter = $container->getByClass(InjectableFactory::class)->create(ConfigWriter::class);

        $isChanged = false;

        // Keep values chosen before an upgrade or re-installation.
        foreach (self::DEFAULTS as $param => $value) {
            if ($config->get($param) !== null) {
                continue;
            }

            $configWriter->set($param, $value);

            $isChanged = true;
        }

        if ($isChanged) {
            $configWriter->save();
        }
    }
}
