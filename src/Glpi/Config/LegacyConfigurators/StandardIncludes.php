<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Config\LegacyConfigurators;

use Config;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Config\ConfigProviderHasRequestTrait;
use Glpi\Config\ConfigProviderWithRequestInterface;
use Glpi\Config\LegacyConfigProviderInterface;
use Glpi\Http\RequestPoliciesTrait;
use Glpi\System\Requirement\DatabaseTablesEngine;
use Glpi\System\RequirementsManager;
use Glpi\Toolbox\VersionParser;
use Html;
use Session;
use Toolbox;
use Update;

final class StandardIncludes implements LegacyConfigProviderInterface, ConfigProviderWithRequestInterface
{
    use ConfigProviderHasRequestTrait;
    use RequestPoliciesTrait;

    public function execute(): void
    {
        /**
         * @var array $CFG_GLPI
         */
        global $CFG_GLPI;

        // Check version
        if ($this->shouldCheckDbStatus($this->getRequest()) && !defined('SKIP_UPDATES') && !Update::isDbUpToDate()) {
            // Prevent debug bar to be displayed when an admin user was connected with debug mode when codebase was updated.
            $debug_mode = $_SESSION['glpi_use_mode'];
            Toolbox::setDebugMode(Session::NORMAL_MODE);

            /** @var \DBmysql $DB */
            global $DB;

            $requirements = (new RequirementsManager())->getCoreRequirementList($DB);
            $requirements->add(new DatabaseTablesEngine($DB));

            $twig_params = [
                'core_requirements' => $requirements,
                'try_again'         => __('Try again'),
                'update_needed'     => __('The GLPI codebase has been updated. The update of the GLPI database is necessary.'),
                'upgrade'           => _sx('button', 'Upgrade'),
                'outdated_files'    => __('You are trying to use GLPI with outdated files compared to the version of the database. Please install the correct GLPI files corresponding to the version of your database.'),
                'stable_release'    => VersionParser::isStableRelease(GLPI_VERSION),
                'agree_unstable'    => Config::agreeUnstableMessage(VersionParser::isDevVersion(GLPI_VERSION)),
                'outdated'          => version_compare(
                    VersionParser::getNormalizedVersion($CFG_GLPI['version'] ?? '0.0.0-dev'),
                    VersionParser::getNormalizedVersion(GLPI_VERSION),
                    '>'
                )
            ];

            Html::nullHeader(__('Update needed'), $CFG_GLPI["root_doc"]);
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class="container-fluid mb-4">
                <div class="row justify-content-evenly">
                    <div class="col-12 col-xxl-6">
                        <div class="card text-center mb-4">
                            {% include 'install/blocks/requirements_table.html.twig' with {'requirements': core_requirements} %}
                            {% if core_requirements.hasMissingMandatoryRequirements() or core_requirements.hasMissingOptionalRequirements() %}
                                <form action="{{ path('index.php') }}" method="post">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-redo"></i>{{ try_again }}
                                    </button>
                                </form>
                            {% endif %}
                            {% if not core_requirements.hasMissingMandatoryRequirements() %}
                                {% if not outdated %}
                                    <form method="post" action="{{ path('install/update.php') }}">
                                        <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                                        {% if not stable_release %}
                                            {{ agree_unstable|raw }}
                                        {% endif %}
                                        <p class="mt-2 mb-n2 alert alert-important alert-warning">
                                            {{ update_needed }}
                                        </p>
                                        <button type="submit" name="from_update" class="btn btn-primary">
                                            <i class="fas fa-check"></i>{{ upgrade }}
                                        </button>
                                    </form>
                                {% else %}
                                    <p class="mt-2 mb-n2 alert alert-important alert-warning">
                                        {{ outdated_files }}
                                    </p>
                                {% endif %}
                            {% endif %}
                        </div>
                    </div>
                </div>
            </div>
TWIG, $twig_params);
            Html::nullFooter();
            $_SESSION['glpi_use_mode'] = $debug_mode;
            exit();
        }
    }
}
