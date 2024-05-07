<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
use InvalidArgumentException;
use JsonConfigInterface;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\AccessControl\ControlType\ControlTypeInterface;
use Glpi\Form\Form;
use Override;
use ReflectionClass;

final class FormAccessControl extends CommonDBChild
{
    public static $itemtype = Form::class;
    public static $items_id = 'forms_forms_id';

    #[Override]
    public static function getTypeName($nb = 0)
    {
        return __('Access control');
    }

    #[Override]
    public static function getIcon()
    {
        return "ti ti-key";
    }

    #[Override]
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        // This tab is only available for forms
        if (!($item instanceof Form)) {
            return false;
        }

        return self::createTabEntry(self::getTypeName());
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
            'access_controls' => $sorted_access_controls,
        ]);

        return true;
    }

    #[Override]
    public static function canView()
    {
        // Must be able to view forms
        return Form::canView();
    }

    #[Override]
    public function canViewItem()
    {
        $form = Form::getByID($this->fields['forms_forms_id']);
        if (!$form) {
            return false;
        }

        // Must be able to view the parent form
        return $form->canViewItem();
    }

    #[Override]
    public static function canCreate()
    {
        // Must be able to update parent form
        return Form::canUpdate();
    }

    #[Override]
    public function canCreateItem()
    {
        $form = Form::getByID($this->input['forms_forms_id']);
        if (!$form) {
            return false;
        }

        // Must be able to update parent form
        return $form->canCreateItem();
    }

    #[Override]
    public static function canUpdate()
    {
        // Must be able to update forms
        return Form::canUpdate();
    }

    #[Override]
    public function canUpdateItem()
    {
        $form = Form::getByID($this->fields['forms_forms_id']);
        if (!$form) {
            return false;
        }

        // Must be able to update the parent form
        return $form->canUpdateItem();
    }

    #[Override]
    public static function canDelete()
    {
        // Never deleted from the UX
        return false;
    }

    #[Override]
    public function canDeleteItem()
    {
        // Never deleted from the UX
        return false;
    }

    #[Override]
    public static function canPurge()
    {
        // Never purged from the UX
        return false;
    }

    #[Override]
    public function canPurgeItem()
    {
        // Never purged from the UX
        return false;
    }

    #[Override]
    public function prepareInputForAdd($input)
    {
        // Config is mandatory on creation; inject default config if missing.
        if (!isset($input['_config'])) {
            $strategy_class = $this->input['strategy'];
            if (!$this->isValidStrategy($strategy_class)) {
                trigger_error(
                    "Invalid access control strategy: $strategy_class",
                    E_USER_WARNING
                );
                return false;
            }

            $strategy = new $strategy_class();
            $config_class = $strategy->getConfigClass();
            $input['_config'] = new $config_class();
        }

        $input = $this->prepareConfigInput($input);

        return $input;
    }

    #[Override]
    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareConfigInput($input);

        // Individual access controls items don't have their own page, thus we
        // don't want to send any invalid links.
        $input['_no_message_link'] = true;

        return $input;
    }

    /**
     * Get access control strategy for this item.
     *
     * @return ControlTypeInterface
     */
    public function getStrategy(): ControlTypeInterface
    {
        $control_type = $this->fields['strategy'];
        if (!$this->isValidStrategy($control_type)) {
            throw new \RuntimeException();
        }

        return new $control_type();
    }

    /**
     * Get config for this item's strategy.
     *
     * @return JsonConfigInterface
     */
    public function getConfig(): JsonConfigInterface
    {
        $config = json_decode($this->fields['config'], true);
        $strategy = $this->getStrategy();
        $config_class = $strategy->getConfigClass();

        if (!is_a($config_class, JsonConfigInterface::class, true)) {
            throw new \RuntimeException("Invalid config class");
        }

        return $config_class::createFromRawArray($config);
    }

    public function createConfigFromUserInput(array $input): JsonConfigInterface
    {
        $strategy_class = $input['strategy'] ?? $this->fields['strategy'] ?? null;
        if ($strategy_class === null || !$this->isValidStrategy($strategy_class)) {
            throw new InvalidArgumentException(
                "Invalid access control strategy: $strategy_class"
            );
        }
        $strategy = new $strategy_class();
        return $strategy->createConfigFromUserInput($input);
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
     * @return array|false
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

    /**
     * Verify that the given access control strategy is valid.
     *
     * @param string $strategy Strategy name (class name)
     *
     * @return bool
     */
    protected function isValidStrategy(string $strategy): bool
    {
        return
            is_a($strategy, ControlTypeInterface::class, true)
            && !(new ReflectionClass($strategy))->isAbstract()
        ;
    }
}
