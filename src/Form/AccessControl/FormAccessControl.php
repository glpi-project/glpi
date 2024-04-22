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

        $twig = TemplateRenderer::getInstance();
        echo $twig->render('pages/admin/form/access_control.html.twig', [
            'form'            => $item,
            'access_controls' => $manager->getAccessControlsForForm(
                $item,
                false
            ),
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
        return Form::canView();
    }

    #[Override]
    public static function canCreate()
    {
        // Never created from the UX
        return false;
    }

    #[Override]
    public function canCreateItem()
    {
        // Never created from the UX
        return false;
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
        return new $config_class($config);
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
        $strategy_class = $input['strategy'] ?? $this->fields['strategy'] ?? null;
        if ($strategy_class === null || !$this->isValidStrategy($strategy_class)) {
            trigger_error(
                "Invalid access control strategy: $strategy_class",
                E_USER_WARNING
            );
            return false;
        }
        $strategy = new $strategy_class();

        if (is_a($input['_config'] ?? null, $strategy->getConfigClass())) {
            // Config object can be directly passed in the input if needed
            $input['config'] = json_encode($input['_config']);
            unset($input['_config']);
            return $input;
        } else {
            // Otherwise it is computed from user supplied values
            $input['config'] = json_encode($strategy->createConfigFromUserInput($input));
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
