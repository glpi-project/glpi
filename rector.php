<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector as CodeQuality;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector as DeadCode;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/ajax',
        __DIR__ . '/dependency_injection',
        __DIR__ . '/front',
        __DIR__ . '/inc',
        __DIR__ . '/install',
        __DIR__ . '/public',
        __DIR__ . '/routes',
        __DIR__ . '/src',
        __DIR__ . '/tools',
    ])
    ->withSkip([
        StringClassNameToClassConstantRector::class => [
            __DIR__ . '/install/migrations',
        ],
    ])
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withCache(
        cacheClass: FileCacheStorage::class,
        cacheDirectory: 'files/_cache/rector',
    )
    ->withParallel(timeoutSeconds: 300)
    // handled by PHP-CS-Fixer with `fully_qualified_strict_types` rule ->withImportNames()
    ->withRules([
        CodeQuality\Assign\CombinedAssignRector::class,
        CodeQuality\BooleanAnd\RemoveUselessIsObjectCheckRector::class,
        CodeQuality\BooleanAnd\SimplifyEmptyArrayCheckRector::class,
        CodeQuality\BooleanNot\ReplaceMultipleBooleanNotRector::class,
        CodeQuality\Catch_\ThrowWithPreviousExceptionRector::class,
        CodeQuality\Empty_\SimplifyEmptyCheckOnEmptyArrayRector::class,
        CodeQuality\Expression\InlineIfToExplicitIfRector::class,
        CodeQuality\Expression\TernaryFalseExpressionToIfRector::class,
        CodeQuality\For_\ForRepeatedCountToOwnVariableRector::class,
        CodeQuality\Foreach_\ForeachItemsAssignToEmptyArrayToAssignRector::class,
        CodeQuality\Foreach_\ForeachToInArrayRector::class,
        CodeQuality\Foreach_\SimplifyForeachToCoalescingRector::class,
        CodeQuality\Foreach_\UnusedForeachValueToArrayKeysRector::class,
        CodeQuality\FuncCall\ChangeArrayPushToArrayAssignRector::class,
        CodeQuality\FuncCall\CompactToVariablesRector::class,
        CodeQuality\FuncCall\InlineIsAInstanceOfRector::class,
        CodeQuality\FuncCall\IsAWithStringWithThirdArgumentRector::class,
        CodeQuality\FuncCall\RemoveSoleValueSprintfRector::class,
        CodeQuality\FuncCall\SetTypeToCastRector::class,
        CodeQuality\FuncCall\SimplifyFuncGetArgsCountRector::class,
        CodeQuality\FuncCall\SimplifyInArrayValuesRector::class,
        CodeQuality\FuncCall\SimplifyStrposLowerRector::class,
        CodeQuality\FuncCall\UnwrapSprintfOneArgumentRector::class,
        CodeQuality\Identical\BooleanNotIdenticalToNotIdenticalRector::class,
        CodeQuality\Identical\SimplifyArraySearchRector::class,
        CodeQuality\Identical\SimplifyConditionsRector::class,
        CodeQuality\Identical\StrlenZeroToIdenticalEmptyStringRector::class,
        // FIXME apply it in another PR, it generates a huge diff CodeQuality\If_\CombineIfRector::class,
        CodeQuality\If_\CompleteMissingIfElseBracketRector::class,
        // FIXME apply it in another PR, it generates a huge diff CodeQuality\If_\ConsecutiveNullCompareReturnsToNullCoalesceQueueRector::class,
        // FIXME apply it in another PR, it generates a huge diff CodeQuality\If_\ExplicitBoolCompareRector::class,
        // FIXME apply it in another PR, it generates a huge diff CodeQuality\If_\ShortenElseIfRector::class,
        // FIXME apply it in another PR, it generates a huge diff CodeQuality\If_\SimplifyIfElseToTernaryRector::class,
        // FIXME apply it in another PR, it generates a huge diff CodeQuality\If_\SimplifyIfNotNullReturnRector::class,
        // FIXME apply it in another PR, it generates a huge diff CodeQuality\If_\SimplifyIfNullableReturnRector::class,
        // FIXME apply it in another PR, it generates a huge diff CodeQuality\If_\SimplifyIfReturnBoolRector::class,
        CodeQuality\Include_\AbsolutizeRequireAndIncludePathRector::class,
        CodeQuality\LogicalAnd\AndAssignsToSeparateLinesRector::class,
        CodeQuality\LogicalAnd\LogicalToBooleanRector::class,
        CodeQuality\NotEqual\CommonNotEqualRector::class,
        CodeQuality\Ternary\UnnecessaryTernaryExpressionRector::class,
        DeadCode\Assign\RemoveUnusedVariableAssignRector::class,
    ])
    ->withPhpSets(php74: true) // apply PHP sets up to PHP 7.4
;
