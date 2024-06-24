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

/**
 * Filename was previously migration_cleaner.php
 * @since 0.85
 */

/**
 * @var array $CFG_GLPI
 * @var \DBmysql $DB
 */
global $CFG_GLPI, $DB;

include('../inc/includes.php');

Session::checkSeveralRightsOr([
    "networking" => UPDATE,
    "internet"   => UPDATE
]);

if (!$DB->tableExists('glpi_networkportmigrations')) {
    Session::addMessageAfterRedirect(__s('You don\'t need the "migration cleaner" tool anymore...'));
    Html::redirect($CFG_GLPI["root_doc"] . "/front/central.php");
}

Html::header(__('Migration cleaner'), $_SERVER['PHP_SELF'], "tools", "migration");

$twig_params = [
    'title' => __('"Migration cleaner" tool'),
    'reinit_label' => __('Reinit the network topology'),
    'clean_label' => __('Clean the network port migration errors'),
];
// language=Twig
echo \Glpi\Application\View\TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
    <div class="mb-3 text-center">
        <h1>{{ title }}</h1>
        <div class="d-flex justify-content-center">
            {% if can_view_all_entities() and has_profile_right('internet', constant('UPDATE')) %}
                <form method="post" action="{{ 'IPNetwork'|itemtype_form_path }}" data-submit-once>
                    <button type="submit" name="reinit_network" class="btn btn-primary mx-1">{{ reinit_label }}</button>
                    <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}"/>
                </form>
            {% endif %}
            {% if has_profile_right('networking', constant('UPDATE')) %}
                <a href="{{ path('front/networkportmigration.php') }}" role="button" class="btn btn-primary mx-1">{{ clean_label }}</a>
            {% endif %}
        </div>
    </div>
TWIG, $twig_params);

Html::footer();
