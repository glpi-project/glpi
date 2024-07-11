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

namespace Glpi\Controller\Asset;

use Html;
use Glpi\Asset\Asset;
use Glpi\Asset\AssetDefinition;
use Glpi\Controller\Controller;
use Glpi\Search\SearchEngine;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class IndexController implements Controller
{
    #[Route(
        "/Asset/{class}",
        name: "glpi_asset_index",
        requirements: [
            'request_parameters' => '[A-Za-z]+',
        ]
    )]
    public function __invoke(Request $request): Response
    {
        $definition = new AssetDefinition();
        $classname  = null;
        if (
            !$definition->getFromDBBySystemName($request->get('class'))
            || ($classname = $definition->getAssetClassName()) === null
            || !is_a($classname, Asset::class, true)
        ) {
            throw new BadRequestHttpException();
        }

        if (!Session::haveRightsOr($classname::$rightname, [READ, READ_ASSIGNED, READ_OWNED])) {
            throw new AccessDeniedHttpException();
        }

        return new StreamedResponse(
            function () use ($classname) {
                Html::header(
                    title: $classname::getTypeName(Session::getPluralNumber()),
                    sector: 'assets',
                    item: $classname
                );

                SearchEngine::show($classname);

                Html::footer();
            }
        );
    }
}
