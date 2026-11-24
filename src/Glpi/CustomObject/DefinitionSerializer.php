<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\CustomObject;

use Closure;
use CommonDBTM;
use Glpi\Error\ErrorHandler;
use Glpi\ImportExport\Context\DatabaseMapper;
use Glpi\ImportExport\ImportExportHandlerInterface;
use Glpi\ImportExport\Result\ExportResult;
use Glpi\ImportExport\Result\ImportError;
use Glpi\ImportExport\Result\ImportResult;
use Glpi\ImportExport\Result\ImportResultIssues;
use Glpi\ImportExport\Result\ImportResultPreview;
use Glpi\ImportExport\Specification\DataRequirementSpecification;
use InvalidArgumentException;
use Profile;
use RuntimeException;
use Session;
use Throwable;
use Toolbox;

use function Safe\json_decode;
use function Safe\json_encode;

/**
 * Exports and imports custom object definitions.
 *
 * File format:
 * `{"type": itemtype, "version": 1, "items": [{"id", "name", "fields": {...}}]}`
 *
 * Other items (profiles...) are referenced by name: the data requirements are
 * computed again from these names on import, and mapped to the items of the
 * target instance.
 *
 * @phpstan-type Converter Closure(class-string<CommonDBTM>, mixed): mixed
 */
class DefinitionSerializer implements ImportExportHandlerInterface
{
    private const VERSION = 1;

    /** Columns copied as they are. */
    private const COLUMNS = ['system_name', 'label', 'icon', 'comment', 'is_active'];

    /** @param class-string<AbstractDefinition<*>> $itemtype */
    public function __construct(protected readonly string $itemtype) {}

    public function exportItems(array $items): ExportResult
    {
        $exported = [];
        foreach ($items as $definition) {
            if ($definition instanceof AbstractDefinition) {
                $exported[] = ['id' => $definition->getID(), 'name' => $definition->getFriendlyName()]
                    + $this->convertReferences($this->exportDefinition($definition), self::exportReference(...));
            }
        }

        $json = json_encode(
            ['type' => $this->itemtype, 'version' => self::VERSION, 'items' => $exported],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
        return new ExportResult($this->getFileName($items), $json);
    }

    public function previewImport(string $json, DatabaseMapper $mapper, array $skipped_items = []): ImportResultPreview
    {
        $preview = new ImportResultPreview();
        foreach ($this->readItems($json) as $item) {
            if (in_array($item['id'], $skipped_items)) {
                $preview->addSkippedItem($item['id'], $item['name']);
                continue;
            }
            $errors = $this->getFatalErrors($item);
            foreach ($errors as $error) {
                $preview->addFatalError($item['id'], $item['name'], $error);
            }
            if ($errors !== []) {
                continue;
            }

            $requirements = $this->getRequirements($item);
            $mapper->mapExistingItemsForRequirements($requirements);
            if ($mapper->validateRequirements($requirements)) {
                $preview->addValidItem($item['id'], $item['name']);
            } else {
                $preview->addInvalidItem($item['id'], $item['name']);
            }
        }
        return $preview;
    }

    public function listIssues(DatabaseMapper $mapper, string $json): ImportResultIssues
    {
        $issues = new ImportResultIssues();
        foreach ($this->readItems($json) as $item) {
            $requirements = $this->getRequirements($item);
            $mapper->mapExistingItemsForRequirements($requirements);
            $issues->addIssues($item['id'], $mapper->getInvalidRequirements($requirements));
        }
        return $issues;
    }

    public function importItems(string $json, DatabaseMapper $mapper, array $skipped_items = []): ImportResult
    {
        global $DB;

        $result = new ImportResult();
        foreach ($this->readItems($json) as $item) {
            if (in_array($item['id'], $skipped_items)) {
                continue;
            }
            if ($this->getFatalErrors($item) !== []) {
                $result->addFailedImport($item['name'], ImportError::FATAL_ERROR);
                continue;
            }
            $requirements = $this->getRequirements($item);
            $mapper->mapExistingItemsForRequirements($requirements);
            if (!$mapper->validateRequirements($requirements)) {
                $result->addFailedImport($item['name'], ImportError::MISSING_DATA_REQUIREMENT);
                continue;
            }

            $DB->beginTransaction();
            try {
                $definition = $this->importDefinition($this->convertReferences(
                    $item,
                    static fn(string $itemtype, mixed $name): ?int => is_scalar($name) ? $mapper->getItemId($itemtype, (string) $name) : null,
                ));
                $DB->commit();
                $result->addImportedItem($definition);
            } catch (Throwable $e) {
                $DB->rollBack();
                ErrorHandler::logCaughtException($e);
                $result->addFailedImport($item['name'], ImportError::INVALID_DATA);
            }
        }
        return $result;
    }

    /**
     * The exported data of the definition, with ids of other items, converted
     * to names afterwards by {@see convertReferences()}.
     *
     * @param AbstractDefinition<*> $definition
     * @return array<string, mixed>
     */
    protected function exportDefinition(AbstractDefinition $definition): array
    {
        $fields = array_intersect_key($definition->fields, array_flip(self::COLUMNS));
        foreach (['profiles', 'translations'] as $column) {
            $fields[$column] = json_decode($definition->fields[$column] ?? '[]', true);
        }
        return ['fields' => $fields];
    }

    /**
     * The input of `add()`, from the item of the file (with ids of the target
     * instance). Only known values are taken from the file.
     *
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    protected function prepareInput(array $fields): array
    {
        $input = array_intersect_key($fields, array_flip(self::COLUMNS));
        foreach (['profiles', 'translations'] as $column) {
            $input[$column] = is_array($fields[$column] ?? null) ? $fields[$column] : [];
        }
        return $input;
    }

    /**
     * Create the definition, from the item of the file (with ids of the target
     * instance).
     *
     * @param array<string, mixed> $item
     * @return AbstractDefinition<*>
     */
    protected function importDefinition(array $item): AbstractDefinition
    {
        $definition = getItemForItemtype($this->itemtype);
        $fields = is_array($item['fields'] ?? null) ? $item['fields'] : [];
        if (!$definition instanceof AbstractDefinition || $definition->add($this->prepareInput($fields)) === false) {
            throw new RuntimeException(sprintf('Unable to import %s "%s".', $this->itemtype, $item['name'] ?? ''));
        }
        return $definition;
    }

    /**
     * Convert every reference to another item held by the item: ids to names
     * on export, names to ids on import. A reference converted to `null` is
     * dropped.
     *
     * @param array<string, mixed> $item
     * @param Converter $convert
     * @return array<string, mixed>
     */
    protected function convertReferences(array $item, Closure $convert): array
    {
        $item['fields']['profiles'] = self::convertKeys($item['fields']['profiles'] ?? [], Profile::class, $convert);
        return $item;
    }

    /**
     * Errors that cannot be fixed from the wizard.
     *
     * @param array<string, mixed> $item
     * @return list<string>
     */
    protected function getFatalErrors(array $item): array
    {
        $system_name = $item['fields']['system_name'] ?? null;
        if (is_string($system_name) && countElementsInTable($this->itemtype::getTable(), ['system_name' => $system_name]) > 0) {
            return [sprintf(__('%1$s "%2$s" already exists.'), $this->itemtype::getTypeName(1), $system_name)];
        }
        return [];
    }

    /**
     * Errors for the classes that do not exist on this instance.
     *
     * @param array<mixed> $classes
     * @return list<string>
     */
    protected static function getMissingClassesErrors(array $classes): array
    {
        $errors = [];
        foreach ($classes as $class) {
            if (is_string($class) && $class !== '' && !class_exists($class)) {
                $plugin = isPluginItemType($class);
                $errors[] = is_array($plugin)
                    ? sprintf(__('Missing plugin: %s'), $plugin['plugin'])
                    : sprintf(__('Unknown type: %s'), $class);
            }
        }
        return array_values(array_unique($errors));
    }

    /**
     * @param array<mixed> $map Map whose keys are references.
     * @param class-string<CommonDBTM> $itemtype
     * @param Converter $convert
     * @return array<mixed>
     */
    protected static function convertKeys(mixed $map, string $itemtype, Closure $convert): array
    {
        $converted = [];
        foreach (is_array($map) ? $map : [] as $key => $value) {
            $key = $convert($itemtype, $key);
            if (is_int($key) || is_string($key)) {
                $converted[$key] = $value;
            }
        }
        return $converted;
    }

    /**
     * Convert a reference, or a list of references. Empty values (no item)
     * and `$special_values` are kept as they are.
     *
     * @param class-string<CommonDBTM> $itemtype
     * @param Converter $convert
     * @param list<string> $special_values
     */
    protected static function convertValue(mixed $value, string $itemtype, Closure $convert, array $special_values = []): mixed
    {
        if (is_array($value)) {
            return array_values(array_filter(
                array_map(static fn(mixed $v): mixed => self::convertValue($v, $itemtype, $convert, $special_values), $value),
                static fn(mixed $v): bool => $v !== null,
            ));
        }
        if (empty($value) || (is_scalar($value) && in_array((string) $value, $special_values, true))) {
            return $value;
        }
        return $convert($itemtype, $value);
    }

    /**
     * @param array<string, mixed> $item
     * @return list<DataRequirementSpecification>
     */
    private function getRequirements(array $item): array
    {
        $requirements = [];
        $this->convertReferences($item, static function (string $itemtype, mixed $name) use (&$requirements): mixed {
            if (is_scalar($name)) {
                $requirements[$itemtype . '|' . $name] = new DataRequirementSpecification($itemtype, (string) $name);
            }
            return $name;
        });
        return array_values($requirements);
    }

    /**
     * The name of the item, or `null` if it no longer exists.
     *
     * @param class-string<CommonDBTM> $itemtype
     */
    private static function exportReference(string $itemtype, mixed $id): ?string
    {
        $item = getItemForItemtype($itemtype);
        if (!is_numeric($id) || $item === false || !$item->getFromDB((int) $id)) {
            return null;
        }
        return DataRequirementSpecification::fromItem($item)->name;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readItems(string $json): array
    {
        $data = json_decode($json, true);
        if (
            !is_array($data)
            || ($data['type'] ?? null) !== $this->itemtype
            || ($data['version'] ?? null) !== self::VERSION
            || !is_array($data['items'] ?? null)
        ) {
            throw new InvalidArgumentException('Unsupported file.');
        }

        $items = [];
        foreach ($data['items'] as $item) {
            if (is_array($item) && is_scalar($item['id'] ?? null) && is_array($item['fields'] ?? null)) {
                $items[] = [
                    'id'     => (int) $item['id'],
                    'name'   => (string) (is_scalar($item['name'] ?? null) ? $item['name'] : $item['id']),
                    'fields' => $item['fields'],
                ] + $item;
            }
        }
        return $items;
    }

    /** @param CommonDBTM[] $items */
    private function getFileName(array $items): string
    {
        $date = Session::getCurrentDate();
        if (count($items) === 1) {
            return Toolbox::slugify(current($items)->getFriendlyName()) . "-$date.json";
        }

        // Two exports of as many items must not share the same name.
        $checksum = crc32(json_encode(array_map(static fn(CommonDBTM $item) => $item->getID(), $items)));
        $type = Toolbox::slugify($this->itemtype::getTypeName(count($items)));
        return sprintf('export-of-%d-%s-%s-%s.json', count($items), $type, $date, $checksum);
    }
}
