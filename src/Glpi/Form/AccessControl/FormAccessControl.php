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

namespace Glpi\Form\AccessControl;

use CommonDBChild;
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AccessControl\ControlType\ControlTypeInterface;
use Glpi\Form\Clone\FormCloneHelper;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\DynamicExportData;
use Glpi\Form\Form;
use InvalidArgumentException;
use Override;
use ReflectionClass;
use Session;

use function Safe\json_decode;
use function Safe\json_encode;

final class FormAccessControl extends CommonDBChild
{
    public static $itemtype = Form::class;
    public static $items_id = 'forms_forms_id';

    #[Override]
    public static function getTypeName($nb = 0)
    {
        return _n('Access control', 'Access controls', $nb);
    }

    #[Override]
    public static function getIcon()
    {
        return "ti ti-key";
    }

    #[Override]
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        // This tab is only available for forms
        if (!($item instanceof Form)) {
            return "";
        }

        $form_access_mananger = FormAccessControlManager::getInstance();

        $count = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            $count = count($form_access_mananger->getActiveAccessControlsForForm($item));
        }

        return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $count);
    }

    #[Override]
    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        // This tab is only available for forms
        if (!($item instanceof Form)) {
            return false;
        }

        $manager = FormAccessControlManager::getInstance();
        $manager->createMissingAccessControlsForForm($item);
        $controls = $item->getAccessControls();
        $sorted_access_controls = $manager->sortAccessControls($controls);

        $twig = TemplateRenderer::getInstance();
        echo $twig->render('pages/admin/form/access_control.html.twig', [
            'form'            => $item,
            'warnings'        => $manager->getWarnings($item),
            'access_controls' => $sorted_access_controls,
        ]);

        return true;
    }

    #[Override]
    public static function canView(): bool
    {
        // Must be able to view forms
        return Form::canView();
    }

    #[Override]
    public function canViewItem(): bool
    {
        $form = Form::getByID($this->fields['forms_forms_id']);
        if (!$form) {
            return false;
        }

        // Must be able to view the parent form
        return $form->canViewItem();
    }

    #[Override]
    public static function canCreate(): bool
    {
        // Must be able to update parent form
        return Form::canUpdate();
    }

    #[Override]
    public function canCreateItem(): bool
    {
        $form = Form::getByID($this->input['forms_forms_id']);
        if (!$form) {
            return false;
        }

        // Must be able to update parent form
        return $form->canCreateItem();
    }

    #[Override]
    public static function canUpdate(): bool
    {
        // Must be able to update forms
        return Form::canUpdate();
    }

    #[Override]
    public function canUpdateItem(): bool
    {
        $form = Form::getByID($this->fields['forms_forms_id']);
        if (!$form) {
            return false;
        }

        // Must be able to update the parent form
        return $form->canUpdateItem();
    }

    #[Override]
    public static function canDelete(): bool
    {
        // Never deleted from the UX
        return false;
    }

    #[Override]
    public function canDeleteItem(): bool
    {
        // Never deleted from the UX
        return false;
    }

    #[Override]
    public static function canPurge(): bool
    {
        // Never purged from the UX
        return false;
    }

    #[Override]
    public function canPurgeItem(): bool
    {
        // Never purged from the UX
        return false;
    }

    #[Override]
    public function prepareInputForAdd($input)
    {
        // Config is mandatory on creation; inject default config if missing.
        if (!isset($input['_config']) && !isset($input['config'])) {
            $strategy_class = $this->input['strategy'];
            try {
                $strategy = $this->createStrategy($strategy_class);
                $input['_config'] = $strategy->getConfig();
            } catch (InvalidArgumentException $e) {
                global $PHPLOGGER;
                $PHPLOGGER->error(
                    "Invalid access control strategy: $strategy_class",
                    ['exception' => $e]
                );

                return false;
            }
        }

        $input = $this->prepareConfigInput($input);

        return $input;
    }

    #[Override]
    public function prepareInputForUpdate($input): array
    {
        $input = $this->prepareConfigInput($input);

        // Individual access controls items don't have their own page, thus we
        // don't want to send any invalid links.
        $input['_no_message_link'] = true;

        return $input;
    }

    #[Override]
    public function prepareInputForClone($input)
    {
        $input = parent::prepareInputForClone($input);
        return FormCloneHelper::getInstance()->prepareAccessControlInputForClone($input);
    }

    /**
     * Get access control strategy for this item.
     *
     * @return ControlTypeInterface
     */
    public function getStrategy(): ControlTypeInterface
    {
        $control_type = $this->fields['strategy'];
        return $this->createStrategy($control_type);
    }

    /**
     * Factory method to create a strategy instance safely.
     *
     * @param string|null $strategy_class Strategy class name
     * @return ControlTypeInterface
     * @throws InvalidArgumentException if strategy is invalid
     */
    public function createStrategy(?string $strategy_class): ControlTypeInterface
    {
        if (
            $strategy_class === null
            || !is_a($strategy_class, ControlTypeInterface::class, true)
            || (new ReflectionClass($strategy_class))->isAbstract()
        ) {
            throw new InvalidArgumentException("Invalid access control strategy: " . ($strategy_class ?? 'null'));
        }

        return new $strategy_class();
    }

    /**
     * Get config for this item's strategy.
     *
     * @return JsonFieldInterface
     */
    public function getConfig(): JsonFieldInterface
    {
        $config = json_decode($this->fields['config'], true);
        $strategy = $this->getStrategy();
        $strategy_config = $strategy->getConfig();

        return $strategy_config::jsonDeserialize($config);
    }

    public function createConfigFromUserInput(array $input): JsonFieldInterface
    {
        $strategy_class = $input['strategy'] ?? $this->fields['strategy'] ?? null;
        $strategy = $this->createStrategy($strategy_class);
        return $strategy->createConfigFromUserInput($input);
    }

    /**
     * Encode the input name to make sure it is unique and multiple items
     * can be updated using a single form.
     *
     * @param string $name
     * @return string
     */
    public function getNormalizedInputName(string $name): string
    {
        return "_access_control[{$this->getID()}][$name]";
    }

    public function exportDynamicData(): DynamicExportData
    {
        $strategy = $this->getStrategy();
        $config = $this->getConfig();

        $data = new DynamicExportData();
        $data->addField('config', $strategy->exportDynamicConfig($config));

        return $data;
    }

    public static function prepareDynamicImportData(
        ControlTypeInterface $strategy,
        array $input,
        DatabaseMapper $mapper,
    ): array {
        $input['_config'] = $strategy->prepareDynamicConfigDataForImport(
            $input['_config'],
            $mapper,
        );

        return $input;
    }

    #[Override]
    protected function computeFriendlyName()
    {
        $strategy = $this->getStrategy();
        return $strategy->getLabel();
    }

    /**
     * Build the json encoded config form the user supplied input.
     *
     * @param array $input
     *
     * @return array
     */
    protected function prepareConfigInput(array $input)
    {
        $config = $input['_config'] ?? null;
        if ($config !== null) {
            $input['config'] = json_encode($config);
            unset($input['_config']);
        }

        return $input;
    }
}
