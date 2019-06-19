<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Trait for shared functions between Event Management hosts and services.
 * @since 10.0.0
 **/
trait Monitored {

   private function getMonitoredField(string $field)
   {
      $class = static::class;
      if ($class == 'SIEMHost') {
         $service = $this->getAvailabilityService();
         if ($service) {
            return $service->fields[$field];
         } else {
            return null;
         }
      } else {
         return $this->fields[$field];
      }
   }

   /**
    * Returns true if the host or service is currently flapping.
    * @since 10.0.0
    */
   public function isFlapping() : bool
   {
      $flapping = $this->getMonitoredField('is_flapping');
      return (!is_null($flapping) && $flapping);
   }

   public function getStatus() : int
   {
      $class = static::class;
      if (($class == 'SIEMHost') && !($this->fields['is_reachable'])) {
         return SIEMHost::STATUS_UNREACHABLE;
      } else {
         $status = $this->getMonitoredField('status');
         return !is_null($status) ? $status : SIEMHost::STATUS_UNKNOWN;
      }
   }

   public function isHardStatus() : bool
   {
      $flapping = $this->getMonitoredField('is_hard_status');
      return (!is_null($flapping) && $flapping);
   }

   public function getLastStatusCheck()
   {
      return $this->getMonitoredField('last_check');
   }

   public function getLastStatusChange()
   {
      return $this->getMonitoredField('status_since');
   }

   /**
    * Returns the translated name of the host or service's current status.
    * @since 10.0.0
    */
   public static abstract function getStatusName() : string;

   /**
    * Returns true if the host or service is scheduled for downtime right now.
    * @since 10.0.0
    */
   public function isScheduledDown() : bool
   {
      static $is_scheduleddown = null;
      if ($is_scheduleddown == null) {
         $iterator = ScheduledDowntime::getForHostOrService($this->getID(), static::class == 'SIEMService');
         while ($data = $iterator->next()) {
            if ($data['is_fixed']) {
               $is_scheduleddown = true;
            } else {
               $downtime = new ScheduledDowntime();
               $is_scheduleddown = true;
            }
            $is_scheduleddown = true;
            break;
         }
         $is_scheduleddown = false;
      }
      return $is_scheduleddown;
   }

   public function getHost() {
      static $host = null;
      if ($host == null) {
         if ($class == 'SIEMHost') {
            return $this;
         } else {
            $host = new SIEMHost();
            $host->getFromDB($this->fields['siemhosts_id']);
         }
      }
      return $host;
   }

   /**
    * Returns the name of this host (or service's host).
    * @since 10.0.0
    */
   public function getHostName() : string
   {
      if (static::class == 'SIEMHost') {
         $hosttype = $this->fields['itemtype'];
         $iterator = $DB->request([
            'SELECT' => ['name'],
            'FROM'   => $hosttype::getTable(),
            'WHERE'  => [
               'id'  => $this->fields['items_id']
            ]
         ]);
         return $iterator->next()['name'];
      } else if ($class == 'SIEMService') {
         if ($this->isHostless()) {
            return '';
         }
         $host = $this->getHost();
         return $host ? $host->getHostName() : null;
      } else {
         return '';
      }
   }

   /**
    * 
    */
   public abstract function getEventRestrictCriteria() : array;
}