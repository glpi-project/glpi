<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

// CleanSoftwareCron cron task
$cron_em = new CronTask();
$tasks = $cron_em->find(['name' => CleanSoftwareCron::TASK_NAME]);

// Task already exist
if (count($tasks) === 0) {
   $cron_em->add([
      'itemtype'      => CleanSoftwareCron::class,
      'name'          => CleanSoftwareCron::TASK_NAME,
      'frequency'     => MONTH_TIMESTAMP,
      'state'         => 0,
      'param'         => 1000,
      'mode'          => 2,
      'allowmode'     => 3,
      'logs_lifetime' => 300,
   ]);
}
// /CleanSoftwareCron cron task
