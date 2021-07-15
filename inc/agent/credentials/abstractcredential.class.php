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

/**
 * Handle credentials for agent
 */
abstract class AbstractCredential
{
   public const LOGIN_TYPE = 1;
   public const FILE_TYPE = 2;
   public const SNMP_TYPE = 3;
   public const TOKEN_TYPE = 4;

   private $type;

   public function __construct(array $dbcredentials = null) {
      if ($dbcredentials !== null) {
         $this->load($dbcredentials);
      }
      $this->type = $this->declaredType();
   }

   abstract public function load(array $credentials): self;

   abstract protected function declaredType(): int;

   abstract public function getCredentials(): array;

   public function  getType(): int {
      return $this->type;
   }

   public static function getLabelledTypes(): array {
      return [
         self::LOGIN_TYPE => __('Login and password'),
         self::FILE_TYPE => __('File'),
         self::SNMP_TYPE => __('SNMP'),
         self::TOKEN_TYPE => __('Token')
      ];
   }

   public static function getTypesClassNames(): array {
      return [
         self::LOGIN_TYPE => 'LoginPass',
         self::FILE_TYPE => 'File',
         self::SNMP_TYPE => 'SNMP',
         self::TOKEN_TYPE => 'Token'
      ];
   }

   public static function factory(int $type, array $credentials = null): self {
      $name = sprintf(
         '\Glpi\Agent\Credentials\%sCredential',
         self::getTypesClassNames()[$type]
      );
      if (!class_exists($name)) {
         \Toolbox::logError('Missing class for agent credentials type "' . $type . '"');
      }
      return new $name($credentials);
   }
}