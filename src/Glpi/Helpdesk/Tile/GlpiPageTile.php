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

namespace Glpi\Helpdesk\Tile;

use CommonDBTM;
use Glpi\Session\SessionInfo;
use Glpi\UI\IllustrationManager;
use Html;
use Override;
use TicketValidation;

final class GlpiPageTile extends CommonDBTM implements TileInterface
{
    public static $rightname = 'config';

    public const PAGE_SERVICE_CATALOG = 'service_catalog';
    public const PAGE_FAQ = 'faq';
    public const PAGE_RESERVATION = 'reservation';
    public const PAGE_APPROVAL = 'approval';

    #[Override]
    public function getLabel(): string
    {
        return __("GLPI page");
    }

    #[Override]
    public static function canCreate(): bool
    {
        return self::canUpdate();
    }

    #[Override]
    public static function canPurge(): bool
    {
        return self::canUpdate();
    }

    public static function getPossiblesPages(): array
    {
        return [
            self::PAGE_SERVICE_CATALOG => __("Service catalog"),
            self::PAGE_FAQ             => __("FAQ"),
            self::PAGE_RESERVATION     => _n("Reservation", "Reservations", 1),
            self::PAGE_APPROVAL        => _n('Approval', 'Approvals', 1)
        ];
    }

    #[Override]
    public function getTitle(): string
    {
        return $this->fields['title'] ?? "";
    }

    #[Override]
    public function getDescription(): string
    {
        return $this->fields['description'] ?? "";
    }

    #[Override]
    public function getIllustration(): string
    {
        return $this->fields['illustration'] ?? IllustrationManager::DEFAULT_ILLUSTRATION;
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
    public function isAvailable(SessionInfo $session_info): bool
    {
        return match ($this->fields['page']) {
            self::PAGE_SERVICE_CATALOG => true,
            self::PAGE_FAQ             => true,
            self::PAGE_RESERVATION     => $session_info->hasRight('reservation', READ),
            self::PAGE_APPROVAL        => $session_info->hasAnyRights('ticketvalidation', [
                TicketValidation::VALIDATEINCIDENT,
                TicketValidation::VALIDATEREQUEST,
            ]),
            default                    => false,
        };
    }

    #[Override]
    public function getDatabaseId(): int
    {
        return $this->fields['id'];
    }

    #[Override]
    public function getConfigFieldsTemplate(): string
    {
        return "pages/admin/glpi_page_tile_config_fields.html.twig";
    }

    public function getPage(): string
    {
        return $this->fields['page'] ?? "";
    }
}
