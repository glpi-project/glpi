<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Controller\Dropdown;

use Glpi\Controller\AbstractController;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Group;
use Profile;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VisibilitySubTargetController extends AbstractController
{
    private const SUPPORTED_TYPES = [Group::class, Profile::class];

    #[Route(
        '/Dropdown/VisibilitySubTarget',
        name: 'visibility_sub_target',
        methods: ['POST']
    )]
    #[SecurityStrategy(Firewall::STRATEGY_CENTRAL_ACCESS)]
    public function __invoke(Request $request): Response
    {
        $input = $request->request;

        $type     = (string) $input->get('type', '');
        $items_id = $input->getInt('items_id', 0);

        // The legacy ajax/subvisibility.php returned an empty body in this case;
        // the jQuery `.load()` handler wired by Ajax::updateItemJsCode triggers
        // on every change of the parent dropdown, including resetting it to its
        // empty option (items_id = 0). Returning a 4xx here would inject an
        // error page into the sub-target div.
        if ($items_id <= 0 || !in_array($type, self::SUPPORTED_TYPES, true)) {
            return $this->withNoCache(new Response(''));
        }

        $raw_prefix = (string) $input->get('prefix', '');
        $prefix     = $raw_prefix !== '' ? $raw_prefix . '[' : '';
        $suffix     = $raw_prefix !== '' ? ']' : '';

        $entity_dropdown_params = [
            'value' => Session::getActiveEntity(),
            'name'  => $prefix . 'entities_id' . $suffix,
        ];

        if (Session::canViewAllEntities()) {
            $entity_dropdown_params['toadd'] = [-1 => __('No restriction')];
        }

        return $this->withNoCache($this->render(
            'components/dropdown/visibility_sub_target.html.twig',
            [
                'prefix'                 => $prefix,
                'suffix'                 => $suffix,
                'entity_dropdown_params' => $entity_dropdown_params,
            ]
        ));
    }

    // prevent shared cache leaks
    private function withNoCache(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
