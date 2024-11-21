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

namespace Glpi\Helpdesk\Tile;

use CommonDBTM;
use Glpi\Session\SessionInfo;
use Html;
use Override;

final class GlpiPageTile extends CommonDBTM implements TileInterface
{
    public const PAGE_SERVICE_CATALOG = 'service_catalog';
    public const PAGE_FAQ = 'faq';
    public const PAGE_RESERVATION = 'reservation';
    public const PAGE_APPROVAL = 'approval';

    #[Override]
    public function getTitle(): string
    {
        return $this->fields['title'];
    }

    #[Override]
    public function getDescription(): string
    {
        return $this->fields['description'];
    }

    #[Override]
    public function getIllustration(): string
    {
        return $this->fields['illustration'];
    }

    #[Override]
    public function getTileUrl(): string
    {
        $url = match ($this->fields['page']) {
            self::PAGE_SERVICE_CATALOG => '/ServiceCatalog',
            self::PAGE_FAQ             => '/front/helpdesk.faq.php',
            self::PAGE_RESERVATION     => '/front/reservationitem.php',
            // TODO: apply correct search filter
            self::PAGE_APPROVAL        => '/front/ticket.php',
            default                    => '/Helpdesk',
        };

        return Html::getPrefixedUrl($url);
    }

    #[Override]
    public function isValid(SessionInfo $session_info): bool
    {
        // We could check rights here for extra safety but it is not really needed
        // since tiles are defined per profile so a page tile defined for a
        // profile should be accessible to the user of that profile.
        // TODO: add extra safety check here when we have more time.
        return true;
    }
}
