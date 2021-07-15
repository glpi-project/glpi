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

class LoginPassCredential extends AbstractCredential
{
   private $login;
   private $password;

   protected function declaredType(): int {
      return self::LOGIN_TYPE;
   }

   public function load(array $credentials): self {
      $this->login = $credentials['login'] ?? '1';
      $this->password = $credentials['password'] ?? 'public';

      return $this;
   }

   public function getCredentials(): array {
      return [
         'login' => $this->login,
         'password' => $this->password
      ];
   }

   public function  showForm() {
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Login')."</td>";
      echo "<td align='center'>";
      \Html::input('login', ['value' => $this->login ?? '']);
      echo "</td>";
      echo "<td>".__('Password')."</td>";
      echo "<td align='center'>";
      echo \Html::input('password', ['type' => 'password²']);
      echo "</td>";
      echo "</tr>";
   }

}