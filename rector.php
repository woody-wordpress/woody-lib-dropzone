<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Core\ValueObject\PhpVersion;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [__DIR__]);

    $containerConfigurator->import(LevelSetList::UP_TO_PHP_74);
    // $containerConfigurator->import(LevelSetList::UP_TO_PHP_81);
    // $containerConfigurator->import(SetList::TYPE_DECLARATION);
    $containerConfigurator->import(SetList::DEAD_CODE);
    // $containerConfigurator->import(SetList::NAMING);
    // $containerConfigurator->import(SetList::ORDER);
    // $containerConfigurator->import(SetList::CODE_QUALITY);
    // $containerConfigurator->import(SetList::CODING_STYLE);
};
