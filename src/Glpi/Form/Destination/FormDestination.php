<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Form\Destination;

use CommonDBChild;
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Features\CloneWithoutNameSuffix;
use Glpi\Form\Clone\FormCloneHelper;
use Glpi\Form\Condition\ConditionableCreationInterface;
use Glpi\Form\Condition\ConditionableCreationTrait;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\DynamicExportData;
use Glpi\Form\Form;
use InvalidArgumentException;
use LogicException;
use Override;
use ReflectionClass;
use RuntimeException;
use Session;

use function Safe\json_decode;
use function Safe\json_encode;

#[CloneWithoutNameSuffix()]
final class FormDestination extends CommonDBChild implements ConditionableCreationInterface
{
    use ConditionableCreationTrait;

    /**
     * Parent item is a Form
     */
    public static $itemtype = Form::class;
    public static $items_id = 'forms_forms_id';

    #[Override]
    public static function getTypeName($nb = 0)
    {
        return _n('Destination', 'Destinations', $nb);
    }

    #[Override]
    public static function getIcon()
    {
        return "ti ti-arrow-forward";
    }

    #[Override]
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        // Only for forms
        if (!($item instanceof Form)) {
            return "";
        }

        $count = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            $count = $this->countForForm($item);
        }

        return self::createTabEntry(
            self::getTypeName(Session::getPluralNumber()),
            $count,
            null,
            self::getIcon() // Must be passed manually for some reason
        );
    }

    #[Override]
    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        // Only for forms
        if (!($item instanceof Form)) {
            return false;
        }

        // Reopen the active accordion item
        if (isset($_SESSION['active_destination'])) {
            $active = $_SESSION['active_destination'];
            unset($_SESSION['active_destination']);
        } else {
            $active = null;
        }

        $manager = FormDestinationManager::getInstance();

        $renderer = TemplateRenderer::getInstance();
        $renderer->display('pages/admin/form/form_destination.html.twig', [
            'icon'                         => self::getIcon(),
            'form'                         => $item,
            'default_destination_object'   => $manager->getDefaultType(),
            'destinations'                 => $item->getDestinations(),
            'available_destinations_types' => $manager->getDestinationTypesDropdownValues(),
            'active_destination'           => $active,
            'can_update'                   => self::canUpdate(),
            'warnings'                     => $manager->getWarnings($item),
        ]);

        return true;
    }

    #[Override]
    public static function canCreate(): bool
    {
        // Must be able to update forms
        return Form::canUpdate();
    }

    #[Override]
    public function canCreateItem(): bool
    {
        $form = Form::getByID($this->fields['forms_forms_id']);
        if (!$form) {
            return false;
        }

        // Must be able to update the parent form
        return $form->canUpdateItem();
    }

    #[Override]
    public static function canUpdate(): bool
    {
        // Must be able to update forms
        return Form::canUpdate();
    }

    #[Override]
    public static function canPurge(): bool
    {
        // Must be able to update forms
        return Form::canUpdate();
    }

    #[Override]
    public function canPurgeItem(): bool
    {
        $form = Form::getByID($this->fields['forms_forms_id']);
        if (!$form) {
            return false;
        }

        // Must be able to update the parent form
        return $form->canUpdateItem();
    }

    #[Override]
    public function prepareInputForAdd($input): array
    {
        $input = $this->prepareInput($input);

        // Set default config
        if (!isset($input['config'])) {
            // Is is safe to access the 'itemtype' key here as it has been
            // validated by the "prepareInput" method
            $input['config'] = json_encode([]);
        }

        // JSON fields must have a value when created to prevent SQL errors
        if (!isset($input['conditions'])) {
            $input['conditions'] = json_encode([]);
        }

        return $input;
    }

    #[Override]
    public function prepareInputForUpdate($input): array
    {
        return $this->prepareInput($input);
    }

    #[Override]
    public function prepareInputForClone($input)
    {
        $input = parent::prepareInputForClone($input);
        return FormCloneHelper::getInstance()->prepareDestinationInputForClone(
            $input,
        );
    }

    /**
     * Common "prepareInput" checks that need to be applied both on creation and
     * on update.
     *
     * @param array $input
     *
     * @return array
     */
    public function prepareInput($input): array
    {
        // Validate forms fk
        if (
            isset($input[Form::getForeignKeyField()])
            || $this->isNewItem() // Mandatory on creation
        ) {
            $form_id = $input[Form::getForeignKeyField()] ?? null;
            if ($form_id === null) {
                throw new InvalidArgumentException("Missing form id");
            }

            // Validate parent Form object
            $form = Form::getByID($form_id);
            if (!$form) {
                throw new InvalidArgumentException("Form not found");
            }
        }

        // Validate itemtype
        if (
            isset($input['itemtype'])
            || $this->isNewItem() // Mandatory on creation
        ) {
            $type = $input['itemtype'] ?? null;
            if (
                $type === null
                || !is_a($type, FormDestinationInterface::class, true)
                || (new ReflectionClass($type))->isAbstract()
            ) {
                throw new InvalidArgumentException("Invalid itemtype");
            }
        }

        // Validate extra config
        if (isset($input['config']) && is_array($input['config'])) {
            $destination_item = $this->getConcreteDestinationItem();
            if ($destination_item instanceof AbstractCommonITILFormDestination) {
                foreach ($destination_item->getConfigurableFields() as $field) {
                    if ($input['_from_import'] ?? false) {
                        continue;
                    }

                    $input['config'] = $field->prepareInput($input['config']);
                }
            }

            $input['config'] = json_encode($input['config']);
        }

        // Encode conditions
        if (isset($input['_conditions'])) {
            $input['conditions'] = json_encode($input['_conditions']);
            unset($input['_conditions']);
        }

        return $input;
    }

    /**
     * Get the concrete destination item using the specified class.
     *
     * @return FormDestinationInterface|null
     */
    public function getConcreteDestinationItem(): ?FormDestinationInterface
    {
        $class = $this->fields['itemtype'] ?? $this->input['itemtype'] ?? null;
        return self::getConcreteDestinationItemForItemtype($class);
    }

    public static function getConcreteDestinationItemForItemtype(
        string $itemtype
    ): ?FormDestinationInterface {
        if (!is_a($itemtype, FormDestinationInterface::class, true)) {
            return null;
        }

        if ((new ReflectionClass($itemtype))->isAbstract()) {
            return null;
        }

        return new $itemtype();
    }

    public function exportDynamicData(): DynamicExportData
    {
        $type = $this->getConcreteDestinationItem();
        $config = $this->getConfig();

        $data = new DynamicExportData();
        $data->addField('config', $type->exportDynamicConfig($config));

        return $data;
    }

    public static function prepareDynamicImportData(
        FormDestinationInterface $type,
        array $input,
        DatabaseMapper $mapper,
    ): array {
        $input['config'] = $type->prepareDynamicConfigDataForImport(
            $input['config'],
            $mapper,
        );

        return $input;
    }

    /**
     * Count the number of form_desinations items for a form
     *
     * @param Form $form
     *
     * @return int
     */
    protected function countForForm(Form $form): int
    {
        return countElementsInTable(
            self::getTable(),
            ['forms_forms_id' => $form->getID()],
        );
    }

    /**
     * Get and decode JSON config.
     *
     * @return array
     */
    public function getConfig(): array
    {
        $config = json_decode($this->fields['config'], true);
        if (!is_array($config)) {
            $config = [];
            trigger_error(
                "Invalid config: {$this->fields['config']}",
                E_USER_WARNING
            );
        }

        return $config;
    }

    public function isMandatory(): bool
    {
        if (!isset($this->fields['is_mandatory'])) {
            throw new LogicException("Fields are not loaded");
        }

        return (bool) $this->fields['is_mandatory'];
    }

    public function getForm(): Form
    {
        $form = $this->getItem();
        if (!($form instanceof Form)) {
            throw new RuntimeException("Can't load parent form");
        }

        return $form;
    }
}
