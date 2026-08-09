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

namespace Glpi\ContentTemplates\Parameters;

use CommonDBTM;
use Glpi\ContentTemplates\Parameters\ParametersTypes\AttributeParameter;
use Ticket;

class TicketCrParameters extends AbstractParameters
{
    public static function getDefaultNodeName(): string
    {
        return 'ticket';
    }

    public static function getObjectLabel(): string
    {
        return __('Created tickets');
    }
    /**
	* @return array<class-string<CommonDBTM>>
	*/
    protected function getTargetClasses(): array
    {
        return [Ticket::class];
    }

    public function getAvailableParameters(): array
    {
        return [
            new AttributeParameter("id", __('ID')),
			new AttributeParameter("link", _n('Link', 'Links', 1), "raw"),
			new AttributeParameter("url", __('URL')),
			new AttributeParameter("name", __('Title')),
			new AttributeParameter("content", __('Description'), "raw"),
			new AttributeParameter("status", __('Status')),
        ];
    }

    protected function defineValues(CommonDBTM $ticket): array
    {
        /** @var Ticket $ticket */
	    $fields = $ticket->fields;
        $values= [
            'id'        => $fields['id'],
            'link'      => $ticket->getLink(),
			'url'	    => ltrim($ticket->getLinkURL(), '/'),
			'name'      => $fields['name'],
			'content'   => $fields['content'],
			'status'    => $ticket::getStatus($fields['status']),
		];

    return $values;
    }
}
