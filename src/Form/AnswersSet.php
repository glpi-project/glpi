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
use Glpi\Form\AnswersHandler\AnswersHandler;
use Log;
use Search;
use User;

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

    public function defineTabs($options = [])
    {
        $tabs = parent::defineTabs();
        $this->addStandardTab(Log::class, $tabs, []);

        // TODO: add tab for created objects
        return $tabs;
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

        Search::showList(self::class, [
            'showmassiveactions' => false,
            'hide_controls'      => true,
            'sort'               => 4, // Creation date
            'order'              => 'DESC',
        ]);
        return true;
    }

    public function post_getFromDB()
    {
        $this->fields['answers'] = json_decode($this->fields['answers'], true);
    }

    public function rawSearchOptions()
    {
        $search_options = parent::rawSearchOptions();

        $search_options[] = [
            'id'       => '3',
            'table'    => User::getTable(),
            'field'    => 'name',
            'name'     => User::getTypeName(1),
            'datatype' => 'dropdown'
        ];

        $search_options[] = [
            'id'            => '4',
            'table'         => $this->getTable(),
            'field'         => 'date_creation',
            'name'          => __('Creation date'),
            'datatype'      => 'datetime',
            'massiveaction' => false
        ];

        return $search_options;
    }

    public static function canUpdate()
    {
        // Answers set can't be updated from the UI
        return false;
    }

    public static function canCreate()
    {
        // Answers set can't be created from the UI
        return false;
    }

    public static function canDelete()
    {
        // Any form administrator may delete answers
        return Form::canUpdate();
    }

    public function showForm($id, array $options = [])
    {
        $this->getFromDB($id);
        $this->initForm($id, $options);

        $answer_handler = new AnswersHandler();

        // Render twig template
        $twig = TemplateRenderer::getInstance();
        $twig->display('pages/admin/form/display_answers.html.twig', [
            'item'    => $this,
            'answers' => $answer_handler->prepareAnswersForDisplay($this->fields['answers']),
            'params'  => $options,
        ]);
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
