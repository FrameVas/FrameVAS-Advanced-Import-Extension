<?php
/*
 * FrameVAS - Advanced Import
 * Author: FrameVAS - Artem Vasylenko
 */

use Espo\Core\Container;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Config\ConfigWriter;

class AfterUninstall
{
    private const PARAMS = [
        'advancedImportNotFoundAction',
        'advancedImportMultipleAction',
    ];

    public function run(Container $container): void
    {
        $configWriter = $container->getByClass(InjectableFactory::class)->create(ConfigWriter::class);

        foreach (self::PARAMS as $param) {
            $configWriter->remove($param);
        }

        $configWriter->save();
    }
}
