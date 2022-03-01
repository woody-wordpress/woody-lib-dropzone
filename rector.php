<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Core\ValueObject\PhpVersion;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

// Skip Rules
use Rector\CodeQuality\Rector\Array_\ArrayThisCallToThisMethodCallRector;
use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\CodingStyle\Rector\ClassConst\RemoveFinalFromConstRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [__DIR__]);
    $parameters->set(Option::SKIP, [
        __DIR__ . '/node_modules',
        __DIR__ . '/dist',
        CallableThisArrayToAnonymousFunctionRector::class,
        ArrayThisCallToThisMethodCallRector::class, // Transform add_action + add_filter
        RemoveUnusedPromotedPropertyRector::class, // Rule PHP8.0
        RemoveFinalFromConstRector::class, // Rule PHP8.1
    ]);

    $containerConfigurator->import(LevelSetList::UP_TO_PHP_74);
    $containerConfigurator->import(SetList::DEAD_CODE);
    //$containerConfigurator->import(SetList::NAMING);
    $containerConfigurator->import(SetList::ORDER);
    $containerConfigurator->import(SetList::CODE_QUALITY);
    //$containerConfigurator->import(SetList::CODING_STYLE);

    //$containerConfigurator->import(LevelSetList::UP_TO_PHP_81);
    //$containerConfigurator->import(SetList::TYPE_DECLARATION);
};
