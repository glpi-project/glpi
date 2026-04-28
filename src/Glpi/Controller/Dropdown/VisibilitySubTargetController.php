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
use Glpi\Exception\Http\BadRequestHttpException;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VisibilitySubTargetController extends AbstractController
{
    private const SUPPORTED_TYPES = ['Group', 'Profile'];

    #[Route(
        '/Dropdown/VisibilitySubTarget',
        name: 'visibility_sub_target',
        methods: ['POST']
    )]
    public function __invoke(Request $request): Response
    {
        $input = $request->request;

        $type     = (string) $input->get('type', '');
        $items_id = $input->getInt('items_id', 0);

        if ($items_id <= 0 || !in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new BadRequestHttpException();
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

        return $this->render(
            'components/dropdown/visibility_sub_target.html.twig',
            [
                'prefix'                 => $prefix,
                'suffix'                 => $suffix,
                'entity_dropdown_params' => $entity_dropdown_params,
            ]
        );
    }
}
