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

use Glpi\Application\View\TemplateRenderer;

/**
 * Dialup instantiation of NetworkPort. A dialup connection (also known a point-to-point protocol) allows connection between to sites through specific connections.
 * @since 0.84
 */
class NetworkPortDialup extends NetworkPortInstantiation
{
    public static function getTypeName($nb = 0)
    {
        return __('Connection by dial line - Dialup Port');
    }

    public function getInstantiationHTMLTableHeaders(
        HTMLTableGroup $group,
        HTMLTableSuperHeader $super,
        HTMLTableSuperHeader $internet_super = null,
        HTMLTableHeader $father = null,
        array $options = []
    ) {

        $header = $group->addHeader('Connected', __s('Connected to'), $super);

        parent::getInstantiationHTMLTableHeaders($group, $super, $internet_super, $header, $options);
        return null;
    }

    public function getInstantiationHTMLTable(
        NetworkPort $netport,
        HTMLTableRow $row,
        HTMLTableCell $father = null,
        array $options = []
    ) {

        return $this->getInstantiationHTMLTableWithPeer($netport, $row, $father, $options);
    }

    public function showInstantiationForm(NetworkPort $netport, $options, $recursiveItems)
    {
        $twig_params = [
            'item' => $this,
            'netport' => $netport,
            'params' => $options,
            'connection_label' => __('Connected to')
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% do call([item, 'showMacField'], [netport, params]) %}
            {% set connection_field %}
                {% do call([item, 'showConnection'], [netport, true]) %}
            {% endset %}
            {{ fields.htmlField('', connection_field, connection_label) }}
TWIG, $twig_params);
    }
}
