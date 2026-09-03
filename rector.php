<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Attribute\ExplicitAttributeNamedArgsRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictScalarReturnsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictSetUpRector;

return RectorConfig::configure()
                ->withPaths([
                    __DIR__ . '/src',
                    __DIR__ . '/tests',
                ])
                ->withSkip([
                    NewlineAfterStatementRector::class,
                    // PHPUnit attributes (#[Group], #[DataProvider], …) are
                    // @no-named-arguments; forcing named args on them makes
                    // PHPStan (argument.named) fail. Keep attribute args positional.
                    ExplicitAttributeNamedArgsRector::class,
                    RenamePropertyToMatchTypeRector::class,
                    RenameVariableToMatchMethodCallReturnTypeRector::class,
                    RenameVariableToMatchNewTypeRector::class,
                    TypedPropertyFromAssignsRector::class,
                    TypedPropertyFromStrictSetUpRector::class,
                    StringReturnTypeFromStrictScalarReturnsRector::class,
                ])
                ->withCache(__DIR__ . '/.rector.cache')
                ->withImportNames(
                    importShortClasses: false,
                    removeUnusedImports: true,
                )
                ->withPreparedSets(
                    deadCode: true,
                    codeQuality: true,
                    codingStyle: true,
                    typeDeclarations: true,
                    typeDeclarationDocblocks: true,
                    privatization: true,
                    naming: true,
                    namedArgs: true,
                    instanceOf: true,
                    earlyReturn: true,
                    phpunitCodeQuality: true,
                    phpunitNarrowAsserts: true,
                    phpunitMockToStub: true,
                )
                ->withAttributesSets(phpunit: true)
                ->withComposerBased(phpunit: true)
                ->withSets(
                    [
                        LevelSetList::UP_TO_PHP_83,
                    ]
                )
;
