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
use KnowbaseItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VisibilityTargetController extends AbstractController
{
    private const SUPPORTED_TYPES = ['User', 'Group', 'Entity', 'Profile'];

    #[Route(
        '/Dropdown/VisibilityTarget',
        name: 'visibility_target',
        methods: ['POST']
    )]
    public function __invoke(Request $request): Response
    {
        $input = $request->request;

        $type  = (string) $input->get('type', '');
        $right = (string) $input->get('right', '');

        if (
            $type === ''
            || !in_array($type, self::SUPPORTED_TYPES, true)
            || !$this->isAllowedRight($right)
        ) {
            throw new BadRequestHttpException();
        }

        $raw_prefix = (string) $input->get('prefix', '');
        $prefix     = $raw_prefix !== '' ? $raw_prefix . '[' : '';
        $suffix     = $raw_prefix !== '' ? ']' : '';

        $rand        = mt_rand();
        $allusers    = $input->has('allusers');
        $no_button   = (bool) $input->get('nobutton', false);
        $entity      = $input->getInt('entity', -1);
        $is_recursive = (bool) $input->get('is_recursive', false);

        $response = $this->render(
            'components/dropdown/visibility_target.html.twig',
            [
                'type'              => $type,
                'right'             => $right,
                'allusers'          => $allusers,
                'rand'              => $rand,
                'prefix'            => $prefix,
                'suffix'            => $suffix,
                'raw_prefix'        => $raw_prefix,
                'entity'            => $entity,
                'is_recursive'      => $is_recursive,
                'show_button'       => !$no_button,
                'profile_condition' => $this->getProfileCondition($right),
            ]
        );

        //prevent shared cache leaks.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    /**
     * Limit `right` to visibility rights to block profile-enumeration probing
     * (`right=config`, `right=user...). Production callers: CommonDBVisible
     * (`*_public`) and the knowbase sidepanel (`knowbase`/`faq`).
     */
    private function isAllowedRight(string $right): bool
    {
        if ($right === '') {
            return false;
        }

        return str_ends_with($right, '_public')
            || in_array($right, ['knowbase', 'faq'], true);
    }

    /**
     * Build the SQL condition used to filter profiles eligible to a given right.
     *
     * @return array<string, mixed>
     */
    private function getProfileCondition(string $right): array
    {
        $check_right = (READ | CREATE | UPDATE | PURGE);
        $right_to_check = $right;

        if ($right === 'faq') {
            $right_to_check = 'knowbase';
            $check_right    = KnowbaseItem::READFAQ;
        }

        return [
            'glpi_profilerights.name'   => $right_to_check,
            'glpi_profilerights.rights' => ['&', $check_right],
        ];
    }
}
