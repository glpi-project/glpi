<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Controller;

use Config;
use DBmysql;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Glpi\System\Requirement\DatabaseTablesEngine;
use Glpi\System\RequirementsManager;
use Glpi\Toolbox\VersionParser;
use Html;
use Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Toolbox;

class NeedsUpdateController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function __invoke(): Response
    {
        return new StreamedResponse($this->display(...));
    }

    public function display(): void
    {
        // Prevent debug bar to be displayed when an admin user was connected with debug mode when codebase was updated.
        $debug_mode = $_SESSION['glpi_use_mode'];
        Toolbox::setDebugMode(Session::NORMAL_MODE);

        $_SESSION['can_process_update'] = true;

        /** @var DBmysql $DB */
        global $DB;

        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

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

        TemplateRenderer::getInstance()->display('update/need_update.html.twig', $twig_params);
        $_SESSION['glpi_use_mode'] = $debug_mode;
    }
}
