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

namespace Glpi\Form;

use CommonDBChild;
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;

/**
 * Answers set for a given helpdesk form
 */
class AnswersSet extends CommonDBChild
{
    public static $itemtype = Form::class;
    public static $items_id = 'forms_forms_id';

    public static function getTypeName($nb = 0)
    {
        return __('Answers');
    }

    public static function getIcon()
    {
        return "ti ti-circle-check";
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!($item instanceof Form)) {
            return false;
        }

        return self::createTabEntry(
            self::getTypeName(),
            $this->countAnswers($item),
        );
    }

    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        if (!($item instanceof Form)) {
            return false;
        }

        $twig = TemplateRenderer::getInstance();
        $twig->display('pages/admin/form_answers_list.html.twig', []);
        return true;
    }

    /**
     * Count answers for a given form
     *
     * @param Form $form
     *
     * @return int
     */
    public function countAnswers(Form $form): int
    {
        return countElementsInTable(self::getTable(), [
            Form::getForeignKeyField() => $form->getID()
        ]);
    }
}
