<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Knowbase\SidePanel;

use Glpi\Knowbase\History\HistoryBuilder;
use Glpi\Toolbox\UserCache;
use KnowbaseItem;
use Override;

final class HistoryRenderer implements RendererInterface
{
    #[Override]
    public function canView(KnowbaseItem $item): bool
    {
        return $item->canUpdateItem();
    }

    #[Override]
    public function getTemplate(): string
    {
        return "pages/tools/kb/sidepanel/revisions.html.twig";
    }

    #[Override]
    public function getParams(KnowbaseItem $item): array
    {
        // Build revisions list
        $history = (new HistoryBuilder($item))->buildHistory();
        return [
            'id' => $item->getID(),
            'history' => $history,
            'can_revert' => $item->canUpdateItem(),
            'users' => new UserCache(),
        ];
    }
}
