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

class FileCredential extends AbstractCredential
{
   private $path;

   protected function declaredType(): int {
      return self::FILE_TYPE;
   }
   public function load(array $credentials): self {
      $this->path = $credentials['path'] ?? '';
      return $this;
   }

   public function getCredentials(): array {
      return [
         'path' => $this->path
      ];
   }

   public function  showForm() {
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Path')."</td>";
      echo "<td align='center'>";
      \Html::input('path', ['value' => $this->path]);
      echo "</td>";
      echo "</tr>";
   }
}