<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Agent\Credentials;

class SNMPCredential extends AbstractCredential
{
   private $version;
   private $community;

   protected function declaredType(): int {
      return self::SNMP_TYPE;
   }

   public function load(array $credentials): self {
      $this->version = $credentials['version'] ?? '1';
      $this->community = $credentials['community'] ?? 'public';

      return $this;
   }

   public function getCredentials(): array {
      return [
         'version' => $this->version,
         'community' => $this->community
      ];
   }

   public function  showForm() {
      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Version', 'Versions', 1)."</td>";
      echo "<td align='center'>";
      \Dropdown::showFromArray(
         'version',
         $this->supportedVersions()
      );
      echo "</td>";
      echo "<td>".__('Community')."</td>";
      echo "<td align='center'>";
      echo \Html::input('community', ['value' => $this->community]);
      echo "</td>";
      echo "</tr>";
   }

   public function supportedVersions(): array {
      return [
         1 => 'SNMP v1'/*,
         2 => 'SNMP v2',
         3 => 'SNMP v3'*/
      ];
   }
}