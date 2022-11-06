<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
                sprintf('%s: %s', __('Content'), $err_msg),
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
        if (DropdownTranslation::isDropdownTranslationActive()) {
            $content = DropdownTranslation::getTranslatedValue(
                $this->getID(),
                $this->getType(),
                'content',
                $_SESSION['glpilanguage'],
                $content
            );
        }

        $html = TemplateManager::renderContentForCommonITIL(
            $itil_item,
            $content
        );

        if (!$html) {
            $html = $content;
        }

        return $html;
    }
}
