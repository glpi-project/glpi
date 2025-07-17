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

use Glpi\ContentTemplates\ParametersPreset;
use Glpi\ContentTemplates\TemplateManager;

/**
 * Base template class
 *
 * @since 10.0.0
 */
abstract class AbstractITILChildTemplate extends CommonDropdown
{
    use CommonDBVisible {
        CommonDBVisible::haveVisibilityAccess as traitHaveVisibilityAccess;
    }

    public function haveVisibilityAccess()
    {
        if (!self::canView()) {
            return false;
        }
        return $this->traitHaveVisibilityAccess();
    }


    /**
     * Class for Group type target
     * @return string
     */
    public function getGroupClass()
    {
        return 'Group_' . $this->getType();
    }

    /**
     * Class for User type target
     * @return string
     */
    public function getUserClass()
    {
        return $this->getType() . '_User';
    }

    /**
     * Class for Profile type target
     * @return string
     */
    public function getProfileClass()
    {
        return 'Profile_' . $this->getType();
    }

    /**
     * Class for Profile type target
     * @return string
     */
    public function getEntityClass()
    {
        return 'Entity_' . $this->getType();
    }

    public static function getTypes()
    {
        return ['User', 'Group', 'Profile', 'Entity'];
    }

    public function post_getFromDB()
    {
        // Groups
        $this->groups = $this->getGroupClass()::getGroups($this);
        // Users
        $this->users = $this->getUserClass()::getUsers($this);
        // Profiles
        $this->profiles = $this->getProfileClass()::getProfiles($this);
        // Entities
        $this->entities = $this->getEntityClass()::getEntities($this);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (self::canView()) {
            $nb = 0;
            switch ($item->getType()) {
                case self::getType():
                    if (Session::haveRight(self::$rightname, CREATE)) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                            $nb = $this->countVisibilities();
                        }
                        return [
                            1 => self::createTabEntry(
                                _n(
                                    'Target',
                                    'Targets',
                                    Session::getPluralNumber()
                                ),
                                $nb
                            ),
                        ];
                    }
                    break;
            }
        }
        return '';
    }

    public function defineTabs($options = [])
    {
        $ong = parent::defineTabs();
        $this->addStandardTab(self::getType(), $ong, $options);
        return $ong;
    }

    /**
     * @param $item         CommonGLPI object
     * @param $tabnum (default 1)
     * @param $withtemplate (default 0)
     **/
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case self::getType():
                $item->showVisibility();
                return true;
        }
        return false;
    }

    public function cleanDBonPurge()
    {
        parent::cleanDBonPurge();
        $this->deleteChildrenAndRelationsFromDb([
            $this->getUserClass(),
            $this->getGroupClass(),
            $this->getProfileClass(),
            $this->getEntityClass(),
        ]);
    }

    /**
     * Get value of "condition" option passed to ajax/getDropdownValue to apply target restrictions
     * @return array
     */
    public function getDropdownCondition()
    {
        $table = $this->getTable();
        $fkField = self::getForeignKeyField();
        $where = [
            'OR' => [
                $this->getUserClass()::getTable() . '.users_id' => Session::getLoginUserID(),
                [
                    $this->getGroupClass()::getTable() . '.groups_id' => count($_SESSION["glpigroups"])
                        ? $_SESSION["glpigroups"]
                        : [-1],
                    'OR' => [
                        [$this->getGroupClass()::getTable() . '.no_entity_restriction' => 1],
                        getEntitiesRestrictCriteria(
                            $this->getGroupClass()::getTable(),
                            '',
                            $_SESSION['glpiactiveentities'],
                            true
                        ),
                    ],
                ],
                [
                    $this->getProfileClass()::getTable() . '.profiles_id' => $_SESSION["glpiactiveprofile"]['id'],
                    'OR' => [
                        $this->getProfileClass()::getTable() . '.no_entity_restriction' => 1,
                        getEntitiesRestrictCriteria(
                            $this->getProfileClass()::getTable(),
                            '',
                            $_SESSION['glpiactiveentities'],
                            true
                        ),
                    ],
                ],
            ],
        ];
        $restrict = getEntitiesRestrictCriteria($this->getEntityClass()::getTable(), '', '', true, true);
        if (count($restrict)) {
            $where['OR'] = $where['OR'] + $restrict;
        }
        return [
            'WHERE' => $where,
            'LEFT JOIN' => [
                $this->getUserClass()::getTable() => [
                    'ON' => [
                        $this->getUserClass()::getTable() => $fkField,
                        $table => 'id',
                    ],
                ],
                $this->getGroupClass()::getTable() => [
                    'ON' => [
                        $this->getGroupClass()::getTable() => $fkField,
                        $table => 'id',
                    ],
                ],
                $this->getProfileClass()::getTable() => [
                    'ON' => [
                        $this->getProfileClass()::getTable() => $fkField,
                        $table => 'id',
                    ],
                ],
                $this->getEntityClass()::getTable() => [
                    'ON' => [
                        $this->getEntityClass()::getTable() => $fkField,
                        $table => 'id',
                    ],
                ],
            ],
            'ORDERBY' => [
                'itemtype',
                'name',
            ],
        ];
    }

    /**
     * No specific right needed to be a target
     * @return false
     */
    public function getVisibilityRight()
    {
        return false;
    }

    public function showForm($ID, array $options = [])
    {
        if (!parent::showForm($ID, $options)) {
            return false;
        }

        // Add autocompletion for ticket properties (twig templates)
        $parameters = ParametersPreset::getForAbstractTemplates();
        Html::activateUserTemplateAutocompletion(
            'textarea[name=content]',
            TemplateManager::computeParameters($parameters)
        );

        // Add related documentation
        Html::addTemplateDocumentationLinkJS(
            'textarea[name=content]',
            ParametersPreset::ITIL_CHILD_TEMPLATE
        );

        return true;
    }

    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForUpdate($input);

        if (!$this->validateContentInput($input)) {
            return false;
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = parent::prepareInputForUpdate($input);

        if (!$this->validateContentInput($input)) {
            return false;
        }

        return $input;
    }

    /**
     * Validate 'content' field from input.
     *
     * @param array $input
     *
     * @return bool
     */
    protected function validateContentInput(array $input): bool
    {
        if (!isset($input['content'])) {
            return true;
        }

        $err_msg = null;
        if (!TemplateManager::validate($input['content'], $err_msg)) {
            Session::addMessageAfterRedirect(
                htmlescape(sprintf('%s: %s', __('Content'), $err_msg)),
                false,
                ERROR
            );
            $this->saveInput();
            return false;
        }

        return true;
    }

    /**
     * Get content rendered by template engine, using given ITIL item to build parameters.
     *
     * @param CommonITILObject $itil_item
     *
     * @return string
     */
    public function getRenderedContent(CommonITILObject $itil_item): string
    {
        if (empty($this->fields['content'])) {
            return '';
        }

        $content = $this->fields['content'];
        $content = DropdownTranslation::getTranslatedValue(
            $this->getID(),
            $this->getType(),
            'content',
            $_SESSION['glpilanguage'],
            $content
        );

        $html = TemplateManager::renderContentForCommonITIL(
            $itil_item,
            $content
        );

        if ($html === null) {
            $html = $content;
        }

        return $html;
    }
}
