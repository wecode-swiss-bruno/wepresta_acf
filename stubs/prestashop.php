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
 * PrestaShop Stubs pour PHPStan.
 *
 * Ces stubs permettent à PHPStan d'analyser le code sans avoir PrestaShop installé
 */

declare(strict_types=1);

// Constantes globales
define('_PS_VERSION_', '8.0.0');
define('_DB_PREFIX_', 'ps_');
define('_MYSQL_ENGINE_', 'InnoDB');
define('_PS_MODE_DEV_', false);
define('_PS_CACHE_DIR_', '/var/cache/');

/**
 * @template T of ObjectModel
 */
abstract class ObjectModel
{
    public ?int $id = null;

    public function __construct(?int $id = null)
    {
    }

    public function add(bool $autoDate = true, bool $nullValues = false): bool
    {
        return true;
    }

    public function update(bool $nullValues = false): bool
    {
        return true;
    }

    public function delete(): bool
    {
        return true;
    }

    public function save(bool $nullValues = false, bool $autoDate = true): bool
    {
        return true;
    }

    public static function existsInDatabase(int $idEntity, string $table): bool
    {
        return true;
    }
}

abstract class Module extends ObjectModel
{
    public string $name = '';

    public string $tab = '';

    public string $version = '';

    public string $author = '';

    public bool $need_instance = false;

    public array $ps_versions_compliancy = [];

    public bool $bootstrap = true;

    public string $displayName = '';

    public string $description = '';

    public string $confirmUninstall = '';

    public ?Context $context = null;

    public string $_path = '';

    public array $_errors = [];

    public function __construct()
    {
    }

    public function install(): bool
    {
        return true;
    }

    public function uninstall(): bool
    {
        return true;
    }

    public function enable(bool $forceAll = false): bool
    {
        return true;
    }

    public function disable(bool $forceAll = false): bool
    {
        return true;
    }

    public function registerHook(string|array $hookName): bool
    {
        return true;
    }

    public function unregisterHook(string|array $hookName): bool
    {
        return true;
    }

    public function isRegisteredInHook(string $hookName): bool
    {
        return true;
    }

    public function getContent(): string
    {
        return '';
    }

    public function display(string $file, string $template): string
    {
        return '';
    }

    public function fetch(string $template, ?string $cacheId = null): string
    {
        return '';
    }

    public function isCached(string $template, ?string $cacheId = null): bool
    {
        return false;
    }

    public function _clearCache(string $template, ?string $cacheId = null): void
    {
    }

    public function displayConfirmation(string $message): string
    {
        return '';
    }

    public function displayWarning(string|array $warning): string
    {
        return '';
    }

    public function displayError(string|array $error): string
    {
        return '';
    }

    public function trans(string $id, array $parameters = [], ?string $domain = null): string
    {
        return $id;
    }

    public function l(string $string): string
    {
        return $string;
    }

    public function getLocalPath(): string
    {
        return '';
    }

    public function getContainer(): ?object
    {
        return null;
    }

    public static function isEnabled(string $moduleName): bool
    {
        return true;
    }

    public static function isInstalled(string $moduleName): bool
    {
        return true;
    }
}

class Configuration
{
    public static function get(string $key, ?int $idLang = null, ?int $idShopGroup = null, ?int $idShop = null): mixed
    {
        return null;
    }

    public static function updateValue(string $key, mixed $value, bool $html = false): bool
    {
        return true;
    }

    public static function deleteByName(string $key): bool
    {
        return true;
    }

    public static function hasKey(string $key): bool
    {
        return true;
    }
}

class Db
{
    public static function getInstance(bool $master = true): self
    {
        return new self();
    }

    public function execute(string $sql): bool
    {
        return true;
    }

    public function insert(string $table, array $data): bool
    {
        return true;
    }

    public function update(string $table, array $data, string $where = ''): bool
    {
        return true;
    }

    public function delete(string $table, string $where = ''): bool
    {
        return true;
    }

    public function getRow(DbQuery|string $sql): ?array
    {
        return null;
    }

    public function getValue(DbQuery|string $sql): mixed
    {
        return null;
    }

    public function executeS(DbQuery|string $sql): array
    {
        return [];
    }

    public function Insert_ID(): int
    {
        return 0;
    }
}

class DbQuery
{
    public function select(string $fields): self
    {
        return $this;
    }

    public function from(string $table, ?string $alias = null): self
    {
        return $this;
    }

    public function where(string $condition): self
    {
        return $this;
    }

    public function orderBy(string $fields): self
    {
        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        return $this;
    }

    public function leftJoin(string $table, ?string $alias = null, ?string $on = null): self
    {
        return $this;
    }

    public function innerJoin(string $table, ?string $alias = null, ?string $on = null): self
    {
        return $this;
    }

    public function groupBy(string $fields): self
    {
        return $this;
    }

    public function having(string $condition): self
    {
        return $this;
    }
}

class Tools
{
    public static function getValue(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public static function isSubmit(string $key): bool
    {
        return false;
    }

    public static function redirect(string $url): void
    {
    }

    public static function redirectAdmin(string $url): void
    {
    }

    public static function getAdminTokenLite(string $tab): string
    {
        return '';
    }
}

class Context
{
    public ?Shop $shop = null;

    public ?Language $language = null;

    public ?Currency $currency = null;

    public ?Customer $customer = null;

    public ?Cart $cart = null;

    public ?Controller $controller = null;

    public ?Smarty $smarty = null;

    public ?Link $link = null;

    public ?Employee $employee = null;

    public static function getContext(): self
    {
        return new self();
    }
}

class Shop extends ObjectModel
{
    public const CONTEXT_SHOP = 1;

    public const CONTEXT_GROUP = 2;

    public const CONTEXT_ALL = 3;

    public static function setContext(int $type, ?int $id = null): void
    {
    }
}

class Language extends ObjectModel
{
    public static function getLanguages(bool $active = true): array
    {
        return [];
    }
}

class Currency extends ObjectModel
{
}
class Customer extends ObjectModel
{
}
class Cart extends ObjectModel
{
}
class Product extends ObjectModel
{
}
class Order extends ObjectModel
{
    public ?string $reference = null;

    public float $total_paid = 0;
}
class Employee extends ObjectModel
{
}

class Link
{
    public function getModuleLink(string $module, string $controller = 'default', array $params = []): string
    {
        return '';
    }

    public function getProductLink(Product $product): string
    {
        return '';
    }
}

class Tab extends ObjectModel
{
    public bool $active = true;

    public string $class_name = '';

    public ?string $route_name = null;

    public array $name = [];

    public int $id_parent = 0;

    public string $module = '';

    public string $icon = '';

    public bool $enabled = true;

    public bool $hide_host_mode = false;

    public static function getIdFromClassName(string $className): int
    {
        return 0;
    }
}

class HelperForm
{
    public ?Module $module = null;

    public string $identifier = '';

    public string $token = '';

    public string $currentIndex = '';

    public int $default_form_language = 0;

    public int $allow_employee_form_lang = 0;

    public string $submit_action = '';

    public string $title = '';

    public array $fields_value = [];

    public string $name_controller = '';

    public function generateForm(array $fieldsForm): string
    {
        return '';
    }
}

abstract class Controller
{
    public function registerStylesheet(string $id, string $path, array $options = []): void
    {
    }

    public function registerJavascript(string $id, string $path, array $options = []): void
    {
    }

    public function addCSS(string $path): void
    {
    }

    public function addJS(string $path): void
    {
    }
}

class AdminController extends Controller
{
    public static string $currentIndex = '';
}

class AdminModulesController extends AdminController
{
}

class ModuleFrontController extends Controller
{
    public ?Module $module = null;

    public string $php_self = '';

    public function init(): void
    {
    }

    public function setMedia(): void
    {
    }

    public function initContent(): void
    {
    }

    public function setTemplate(string $template): void
    {
    }

    public function getTemplateVarPage(): array
    {
        return [];
    }

    public function getBreadcrumbLinks(): array
    {
        return [];
    }
}

class PrestaShopLogger
{
    public static function addLog(string $message, int $severity = 1, ?int $errorCode = null, ?string $objectType = null, ?int $objectId = null): void
    {
    }
}

class Smarty
{
    public function assign(array|string $tplVar, mixed $value = null): void
    {
    }
}

/**
 * SQL escape.
 */
function pSQL(string $string): string
{
    return $string;
}
function bqSQL(string $string): string
{
    return $string;
}
