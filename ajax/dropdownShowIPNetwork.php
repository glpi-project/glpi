<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

$network = new IPNetwork();

if ($_POST['ipnetworks_id'] && $network->can($_POST['ipnetworks_id'], READ)) {
    echo "<br>";
    echo "<a href='" . htmlescape($network->getLinkURL()) . "'>" . htmlescape($network->fields['completename']) . "</a><br>";

    $address = $network->getAddress()->getTextual();
    $netmask = $network->getNetmask()->getTextual();
    $gateway = $network->getGateway()->getTextual();

    $start   = new IPAddress();
    $end     = new IPAddress();

    $network->computeNetworkRange($start, $end);

    //TRANS: %1$s is address, %2$s is netmask
    echo htmlescape(sprintf(__('IP network: %1$s/%2$s'), $address, $netmask)) . "<br>";
    echo htmlescape(sprintf(__('First/last addresses: %1$s/%2$s'), $start->getTextual(), $end->getTextual()));
    if (!empty($gateway)) {
        echo "<br>";
        echo htmlescape(sprintf(__('Gateway: %s'), $gateway));
    }
}
