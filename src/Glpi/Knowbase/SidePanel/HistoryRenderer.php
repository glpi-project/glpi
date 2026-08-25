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
    /**
     * Number of events rendered at once.
     */
    public const PAGE_SIZE = 50;

    #[Override]
    public function canView(KnowbaseItem $item): bool
    {
        return $item->can($item->getID(), UPDATE);
    }

    #[Override]
    public function getTemplate(): string
    {
        return "pages/tools/kb/sidepanel/revisions.html.twig";
    }

    #[Override]
    public function getParams(KnowbaseItem $item): array
    {
        return $this->getPageParams($item, 0);
    }

    /**
     * Template rendering a single page of events, without the panel around it.
     */
    public function getPageTemplate(): string
    {
        return "pages/tools/kb/sidepanel/revisions_page.html.twig";
    }

    /**
     * Parameters needed to render the page of events starting at $offset.
     *
     * @return array<string, mixed>
     */
    public function getPageParams(KnowbaseItem $item, int $offset): array
    {
        $next_offset = $offset + self::PAGE_SIZE;

        // Build revisions list. Only the events up to the requested page are
        // needed, plus one to know whether there is a page after this one.
        $history = (new HistoryBuilder($item))->buildHistory($next_offset + 1);

        return [
            'id' => $item->getID(),
            'history' => $history->slice($offset, self::PAGE_SIZE),
            'can_revert' => $item->can($item->getID(), UPDATE),
            'users' => new UserCache(),
            // Only the very first event of the history is highlighted as being
            // the one currently displayed.
            'first_page' => $offset === 0,
            'next_offset' => $history->count() > $next_offset ? $next_offset : null,
        ];
    }
}
