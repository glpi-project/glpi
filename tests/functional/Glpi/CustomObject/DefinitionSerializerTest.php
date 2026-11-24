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

namespace tests\units\Glpi\CustomObject;

use Glpi\Asset\AssetDefinition;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDocumentsCapacity;
use Glpi\Asset\Capacity\IsInventoriableCapacity;
use Glpi\Asset\CapacityConfig;
use Glpi\Asset\CustomFieldDefinition;
use Glpi\Asset\CustomFieldType\DropdownType;
use Glpi\Asset\CustomFieldType\StringType;
use Glpi\Dropdown\DropdownDefinition;
use Glpi\ImportExport\Context\DatabaseMapper;
use Glpi\ImportExport\ImportExportManager;
use Glpi\Inventory\MainAsset\GenericNetworkAsset;
use Glpi\Tests\DbTestCase;
use Location;
use Profile;
use Psr\Log\LogLevel;
use Session;

use function Safe\copy;
use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\md5_file;

/**
 * Export then import of real definitions, on the same instance: the
 * `system_name` is changed in the file, as it must be unique.
 */
final class DefinitionSerializerTest extends DbTestCase
{
    private function import(string $json, string $itemtype): array
    {
        $serializer = ImportExportManager::getHandler($itemtype);
        $mapper = new DatabaseMapper(Session::getActiveEntities());
        $preview = $serializer->previewImport($json, $mapper);
        $this->assertSame([], $preview->getItemsWithFatalErrors());
        $this->assertSame([], $serializer->listIssues($mapper, $json)->getIssues()[array_key_first($preview->getValidItems() + $preview->getInvalidItems())]);
        $this->assertCount(1, $preview->getValidItems());
        $result = $serializer->importItems($json, $mapper);
        $this->assertSame([], $result->getFailedImports());
        $this->assertCount(1, $result->getImportedItems());
        return $result->getImportedItems();
    }

    private function renameSystemName(string $json, string $system_name): string
    {
        $data = json_decode($json, true);
        $data['items'][0]['fields']['system_name'] = $system_name;
        return json_encode($data);
    }

    public function testAssetDefinitionRoundTrip(): void
    {
        $this->login();
        $technician = getItemByTypeName(Profile::class, 'Technician', true);
        $superadmin = getItemByTypeName(Profile::class, 'Super-Admin', true);
        $location = $this->createItem(Location::class, ['name' => 'Export place', 'entities_id' => $this->getTestRootEntity(true)])->getID();

        // A custom dropdown, used by a custom field
        $dropdown_definition = $this->initDropdownDefinition('ExportedValues');
        $dropdown_class = $dropdown_definition->getCustomObjectClassName();
        $dropdown_value = $this->createItem($dropdown_class, ['name' => 'Value 1', 'entities_id' => $this->getTestRootEntity(true)]);

        $definition = $this->initAssetDefinition(
            'ExportedAsset',
            [
                new Capacity(HasDocumentsCapacity::class),
                new Capacity(IsInventoriableCapacity::class, new CapacityConfig(['inventory_mainasset' => GenericNetworkAsset::class])),
            ],
            [$superadmin => ALLSTANDARDRIGHT, $technician => READ],
        );
        copy(GLPI_ROOT . '/tests/fixtures/uploads/foo.png', GLPI_TMP_DIR . '/export_test.png');
        $this->assertTrue($definition->update([
            'id'             => $definition->getID(),
            'label'          => 'Exported asset',
            'icon'           => 'ti ti-device-mobile',
            'comment'        => 'A comment',
            'translations'   => ['fr_FR' => ['one' => 'Actif exporté', 'other' => 'Actifs exportés']],
            '_picture'       => ['export_test.png'],
            'fields_display' => [0 => 'name', 1 => 'serial', 2 => 'custom_place', 3 => 'custom_value'],
            'field_options'  => ['serial' => ['readonly' => [(string) $technician, '-1'], 'required' => '1']],
        ]));
        $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $definition->getID(),
            'system_name'   => 'place',
            'label'         => 'Place',
            'type'          => DropdownType::class,
            'itemtype'      => Location::class,
            'default_value' => $location,
            'field_options' => ['readonly' => [(string) $technician], 'hidden' => ['-1']],
            'translations'  => ['fr_FR' => 'Lieu'],
        ], ['default_value', 'field_options', 'translations']);
        $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $definition->getID(),
            'system_name'   => 'value',
            'label'         => 'Value',
            'type'          => DropdownType::class,
            'itemtype'      => $dropdown_class,
            'default_value' => [$dropdown_value->getID()],
            'field_options' => ['multiple' => '1'],
        ], ['default_value', 'field_options']);
        $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $definition->getID(),
            'system_name'   => 'note',
            'type'          => StringType::class,
            'default_value' => 'Hello',
        ], ['default_value', 'field_options']);
        $definition->getFromDB($definition->getID());

        // Export: ids became names
        $export = ImportExportManager::getHandler(AssetDefinition::class)->exportItems([$definition]);
        $this->assertStringStartsWith('exported-asset-', $export->getFileName());
        $item = json_decode($export->getJsonContent(), true)['items'][0];
        $this->assertSame(['Super-Admin' => ALLSTANDARDRIGHT, 'Technician' => READ], $item['fields']['profiles']);
        $this->assertSame(['Technician', '-1'], $item['fields']['fields_display'][1]['field_options']['readonly']);
        $this->assertStringEndsWith('.png', $item['fields']['picture']['name']);
        $fields = array_column($item['custom_fields'], null, 'system_name');
        $this->assertSame('Export place', $fields['place']['default_value']);
        $this->assertSame(['Technician'], $fields['place']['field_options']['readonly']);
        $this->assertSame(['Value 1'], $fields['value']['default_value']);
        $this->assertSame('Hello', $fields['note']['default_value']);

        // Same system name: refused
        $preview = ImportExportManager::getHandler(AssetDefinition::class)
            ->previewImport($export->getJsonContent(), new DatabaseMapper(Session::getActiveEntities()));
        $this->assertSame(
            ['Asset definition "ExportedAsset" already exists.'],
            $preview->getItemsWithFatalErrors()[$definition->getID()]['errors'],
        );

        // Import as another definition
        $imported = $this->import($this->renameSystemName($export->getJsonContent(), 'ImportedAsset'), AssetDefinition::class)[0];
        $imported = AssetDefinition::getById($imported->getID());
        $this->assertSame('ImportedAsset', $imported->fields['system_name']);
        foreach (['label', 'icon', 'comment', 'is_active'] as $field) {
            $this->assertSame($definition->fields[$field], $imported->fields[$field], $field);
        }
        foreach (['capacities', 'translations', 'fields_display', 'profiles'] as $field) {
            $this->assertEquals(json_decode($definition->fields[$field], true), json_decode($imported->fields[$field], true), $field);
        }
        $this->assertNotSame($definition->fields['picture'], $imported->fields['picture']);
        $this->assertSame(md5_file(GLPI_PICTURE_DIR . '/' . $definition->fields['picture']), md5_file(GLPI_PICTURE_DIR . '/' . $imported->fields['picture']));
        $this->assertTrue($imported->hasCapacityEnabled(new IsInventoriableCapacity()));

        $custom_fields = [];
        foreach ($imported->getCustomFieldDefinitions() as $custom_field) {
            $custom_fields[$custom_field->fields['system_name']] = $custom_field->fields;
        }
        $this->assertEqualsCanonicalizing(['place', 'value', 'note'], array_keys($custom_fields));
        $this->assertSame(Location::class, $custom_fields['place']['itemtype']);
        $this->assertEquals($location, $custom_fields['place']['default_value']);
        $this->assertEquals([(string) $technician], array_map('strval', $custom_fields['place']['field_options']['readonly']));
        $this->assertSame(['fr_FR' => 'Lieu'], json_decode($custom_fields['place']['translations'], true));
        $this->assertSame($dropdown_class, $custom_fields['value']['itemtype']);
        $this->assertEquals([$dropdown_value->getID()], $custom_fields['value']['default_value']);
        $this->assertSame('Hello', $custom_fields['note']['default_value']);
    }

    public function testMissingProfileAndCustomTypeAreReported(): void
    {
        $this->login();
        $definition = $this->initAssetDefinition('AssetWithIssues', [new Capacity(HasDocumentsCapacity::class)]);
        $json = ImportExportManager::getHandler(AssetDefinition::class)->exportItems([$definition])->getJsonContent();
        $data = json_decode($this->renameSystemName($json, 'AssetWithIssuesCopy'), true);
        $data['items'][0]['fields']['profiles'] = ['Unknown profile' => READ];
        $data['items'][0]['fields']['capacities'][] = ['name' => 'GlpiPlugin\\Foo\\Capacity', 'config' => []];

        $serializer = ImportExportManager::getHandler(AssetDefinition::class);
        $mapper = new DatabaseMapper(Session::getActiveEntities());
        $preview = $serializer->previewImport(json_encode($data), $mapper);
        $this->assertSame(['Missing plugin: Foo'], $preview->getItemsWithFatalErrors()[$definition->getID()]['errors']);

        array_pop($data['items'][0]['fields']['capacities']);
        $preview = $serializer->previewImport(json_encode($data), $mapper);
        $this->assertSame([$definition->getID() => $definition->getFriendlyName()], $preview->getInvalidItems());

        $mapper->addMappedItem(Profile::class, 'Unknown profile', getItemByTypeName(Profile::class, 'Observer', true));
        $imported = $serializer->importItems(json_encode($data), $mapper)->getImportedItems()[0];
        $this->assertSame(
            [getItemByTypeName(Profile::class, 'Observer', true) => READ],
            json_decode(AssetDefinition::getById($imported->getID())->fields['profiles'], true),
        );
    }

    public function testReferencesAreAlwaysNames(): void
    {
        $this->login();
        // A profile whose name is the id of another one
        $other = $this->createItem(Profile::class, ['name' => 'Other profile']);
        $numeric = $this->createItem(Profile::class, ['name' => (string) $other->getID()]);
        $deleted = $this->createItem(Profile::class, ['name' => 'Deleted profile']);
        $definition = $this->initAssetDefinition('AssetWithNumericProfile', [], [$numeric->getID() => READ, $deleted->getID() => UPDATE]);
        $this->deleteItem(Profile::class, $deleted->getID());

        $json = ImportExportManager::getHandler(AssetDefinition::class)->exportItems([$definition])->getJsonContent();
        $this->assertSame([(string) $other->getID() => READ], json_decode($json, true)['items'][0]['fields']['profiles']);

        $imported = $this->import($this->renameSystemName($json, 'AssetWithNumericProfileCopy'), AssetDefinition::class)[0];
        $this->assertSame([$numeric->getID() => READ], json_decode(AssetDefinition::getById($imported->getID())->fields['profiles'], true));
    }

    public function testUnknownCustomFieldTypeIsFatal(): void
    {
        $this->login();
        $definition = $this->initAssetDefinition('AssetWithUnknownField');
        $json = ImportExportManager::getHandler(AssetDefinition::class)->exportItems([$definition])->getJsonContent();
        $data = json_decode($this->renameSystemName($json, 'AssetWithUnknownFieldCopy'), true);
        $data['items'][0]['custom_fields'] = [['system_name' => 'field', 'type' => 'Glpi\\Unknown\\FieldType']];

        $preview = ImportExportManager::getHandler(AssetDefinition::class)
            ->previewImport(json_encode($data), new DatabaseMapper(Session::getActiveEntities()));
        $this->assertSame(['Unknown type: Glpi\\Unknown\\FieldType'], $preview->getItemsWithFatalErrors()[$definition->getID()]['errors']);
    }

    public function testMissingReferencesInFieldOptionsAndDefaultValues(): void
    {
        $this->login();
        $profile = $this->createItem(Profile::class, ['name' => 'Exported profile']);
        $location = $this->createItem(Location::class, ['name' => 'Exported place', 'entities_id' => $this->getTestRootEntity(true)]);
        $definition = $this->initAssetDefinition('AssetWithReferences');
        $this->assertTrue($definition->update([
            'id'             => $definition->getID(),
            'fields_display' => [0 => 'name', 1 => 'serial'],
            'field_options'  => ['serial' => ['hidden' => [(string) $profile->getID()]]],
        ]));
        $this->createItem(CustomFieldDefinition::class, [
            'assets_assetdefinitions_id' => $definition->getID(),
            'system_name'   => 'place',
            'type'          => DropdownType::class,
            'itemtype'      => Location::class,
            'default_value' => $location->getID(),
            'field_options' => ['readonly' => [(string) $profile->getID()]],
        ], ['default_value', 'field_options']);
        $definition->getFromDB($definition->getID());
        $json = ImportExportManager::getHandler(AssetDefinition::class)->exportItems([$definition])->getJsonContent();
        $json = $this->renameSystemName($json, 'AssetWithReferencesCopy');

        // As on another instance, the profile and the location do not exist
        $this->deleteItem(CustomFieldDefinition::class, current($definition->getCustomFieldDefinitions())->getID(), true);
        $this->deleteItem(Profile::class, $profile->getID(), true);
        $this->deleteItem(Location::class, $location->getID(), true);

        $serializer = ImportExportManager::getHandler(AssetDefinition::class);
        $mapper = new DatabaseMapper(Session::getActiveEntities());
        $issues = $serializer->listIssues($mapper, $json)->getIssues()[$definition->getID()];
        $this->assertEqualsCanonicalizing(
            [Profile::class . ' Exported profile', Location::class . ' Exported place'],
            array_map(static fn($requirement) => $requirement->itemtype . ' ' . $requirement->name, $issues),
        );

        $observer = getItemByTypeName(Profile::class, 'Observer', true);
        $other_location = getItemByTypeName(Location::class, '_location01', true);
        $mapper->addMappedItem(Profile::class, 'Exported profile', $observer);
        $mapper->addMappedItem(Location::class, 'Exported place', $other_location);
        $imported = AssetDefinition::getById($serializer->importItems($json, $mapper)->getImportedItems()[0]->getID());
        $this->assertSame([(string) $observer], array_map('strval', json_decode($imported->fields['fields_display'], true)[1]['field_options']['hidden']));
        $custom_field = current($imported->getCustomFieldDefinitions());
        $this->assertEquals($other_location, $custom_field->fields['default_value']);
        $this->assertSame([(string) $observer], array_map('strval', $custom_field->fields['field_options']['readonly']));
    }

    public function testPictureMustBeAnImage(): void
    {
        $this->login();
        $definition = $this->initAssetDefinition('AssetWithBadPicture');
        $json = ImportExportManager::getHandler(AssetDefinition::class)->exportItems([$definition])->getJsonContent();
        $data = json_decode($this->renameSystemName($json, 'AssetWithBadPictureCopy'), true);
        $data['items'][0]['fields']['picture'] = ['name' => 'picture.php', 'data' => base64_encode('<?php echo "Hello";')];

        $imported = $this->import(json_encode($data), AssetDefinition::class)[0];
        $this->assertEmpty(AssetDefinition::getById($imported->getID())->fields['picture']);
        $this->hasSessionMessages(ERROR, ['Unable to save picture file.']);
    }

    public function testFailedImportIsRolledBackAndOthersAreImported(): void
    {
        $this->login();
        $failing = $this->initAssetDefinition('FailingAsset');
        copy(GLPI_ROOT . '/tests/fixtures/uploads/foo.png', GLPI_TMP_DIR . '/rollback_test.png');
        $this->assertTrue($failing->update(['id' => $failing->getID(), '_picture' => ['rollback_test.png']]));
        $failing->getFromDB($failing->getID());
        $other = $this->initAssetDefinition('OtherAsset');

        $json = ImportExportManager::getHandler(AssetDefinition::class)->exportItems([$failing, $other])->getJsonContent();
        $data = json_decode($json, true);
        $data['items'][0]['fields']['system_name'] = 'FailingAssetCopy';
        $data['items'][1]['fields']['system_name'] = 'OtherAssetCopy';
        // Two custom fields with the same system name: the second one is refused
        $field = ['system_name' => 'note', 'type' => StringType::class];
        $data['items'][0]['custom_fields'] = [$field, $field];

        $pictures = glob(GLPI_PICTURE_DIR . '/*/*');
        $result = ImportExportManager::getHandler(AssetDefinition::class)
            ->importItems(json_encode($data), new DatabaseMapper(Session::getActiveEntities()));
        $this->assertCount(1, $result->getFailedImports());
        $this->assertSame(['OtherAssetCopy'], array_map(static fn($item) => $item->fields['system_name'], $result->getImportedItems()));
        $this->assertSame(0, countElementsInTable(AssetDefinition::getTable(), ['system_name' => 'FailingAssetCopy']));
        $this->assertSame($pictures, glob(GLPI_PICTURE_DIR . '/*/*'), 'The picture of the failed import must be removed');
        $this->hasSessionMessages(ERROR, ['The system name must be unique among fields for this asset definition']);
        $this->hasPhpLogRecordThatContains('Unable to import the custom field "note".', LogLevel::ERROR);
    }

    public function testDropdownDefinitionRoundTrip(): void
    {
        $this->login();
        $definition = $this->initDropdownDefinition('ExportedDropdown');
        $this->assertTrue($definition->update([
            'id'           => $definition->getID(),
            'label'        => 'Exported dropdown',
            'translations' => ['fr_FR' => ['one' => 'Liste exportée']],
        ]));
        $definition->getFromDB($definition->getID());

        $json = ImportExportManager::getHandler(DropdownDefinition::class)->exportItems([$definition])->getJsonContent();
        $imported = $this->import($this->renameSystemName($json, 'ImportedDropdown'), DropdownDefinition::class)[0];
        $imported = DropdownDefinition::getById($imported->getID());
        foreach (['label', 'is_active', 'profiles', 'translations'] as $field) {
            $this->assertSame($definition->fields[$field], $imported->fields[$field], $field);
        }
    }
}
