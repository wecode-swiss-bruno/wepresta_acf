<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    WePresta <mail@wepresta.shop>
 * @copyright Since 2024 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

/**
 * Rector Configuration.
 *
 * Modernise automatiquement le code PHP
 *
 * @see https://getrector.com/documentation
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedPropertyRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/controllers',
        __DIR__ . '/wepresta_acf.php',
    ])
    ->withSkip([
        __DIR__ . '/vendor',
        __DIR__ . '/var',
        __DIR__ . '/node_modules',
    ])
    ->withPhpSets(php81: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        strictBooleans: true
    )
    ->withRules([
        // Type declarations
        AddVoidReturnTypeWhereNoReturnRector::class,
        ReturnTypeFromReturnNewRector::class,
        ReturnTypeFromStrictTypedPropertyRector::class,
        TypedPropertyFromAssignsRector::class,
        TypedPropertyFromStrictConstructorRector::class,

        // PHP 8.1 features
        ReadOnlyPropertyRector::class,
        FirstClassCallableRector::class,
    ])
    ->withSkip([
        // Éviter les conflits avec PrestaShop
        NullToStrictStringFuncCallArgRector::class,
    ])
    ->withImportNames(removeUnusedImports: true);
