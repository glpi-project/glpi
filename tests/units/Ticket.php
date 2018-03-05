<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

namespace tests\units;

use \atoum;

/* Test for inc/ticket.class.php */

class Ticket extends atoum {
   public function testGetDefaultValues() {
      $input = \Ticket::getDefaultValues();

      $this->integer($input['_users_id_requester'])->isEqualTo(0);
      $this->array($input['_users_id_requester_notif']['use_notification'])->contains('1');
      $this->array($input['_users_id_requester_notif']['alternative_email'])->contains('');

      $this->integer($input['_groups_id_requester'])->isEqualTo(0);

      $this->integer($input['_users_id_assign'])->isEqualTo(0);
      $this->array($input['_users_id_assign_notif']['use_notification'])->contains('1');
      $this->array($input['_users_id_assign_notif']['alternative_email'])->contains('');

      $this->integer($input['_groups_id_assign'])->isEqualTo(0);

      $this->integer($input['_users_id_observer'])->isEqualTo(0);
      $this->array($input['_users_id_observer_notif']['use_notification'])->contains('1');
      $this->array($input['_users_id_observer_notif']['alternative_email'])->contains('');

      $this->integer($input['_suppliers_id_assign'])->isEqualTo(0);
      $this->array($input['_suppliers_id_assign_notif']['use_notification'])->contains('1');
      $this->array($input['_suppliers_id_assign_notif']['alternative_email'])->contains('');

      $this->string($input['name'])->isEqualTo('');
      $this->string($input['content'])->isEqualTo('');
      $this->integer((int) $input['itilcategories_id'])->isEqualTo(0);
      $this->integer((int) $input['urgency'])->isEqualTo(3);
      $this->integer((int) $input['impact'])->isEqualTo(3);
      $this->integer((int) $input['priority'])->isEqualTo(3);
      $this->integer((int) $input['requesttypes_id'])->isEqualTo(1);
      $this->integer((int) $input['actiontime'])->isEqualTo(0);
      $this->integer((int) $input['entities_id'])->isEqualTo(0);
      $this->integer((int) $input['status'])->isEqualTo(\Ticket::INCOMING);
      $this->array($input['followup'])->size->isEqualTo(0);
      $this->string($input['itemtype'])->isEqualTo('');
      $this->integer((int) $input['items_id'])->isEqualTo(0);
      $this->array($input['plan'])->size->isEqualTo(0);
      $this->integer((int) $input['global_validation'])->isEqualTo(\CommonITILValidation::NONE);

      $this->string($input['time_to_resolve'])->isEqualTo('NULL');
      $this->string($input['time_to_own'])->isEqualTo('NULL');
      $this->integer((int) $input['slas_tto_id'])->isEqualTo(0);
      $this->integer((int) $input['slas_ttr_id'])->isEqualTo(0);

      $this->string($input['internal_time_to_resolve'])->isEqualTo('NULL');
      $this->string($input['internal_time_to_own'])->isEqualTo('NULL');
      $this->integer((int) $input['olas_tto_id'])->isEqualTo(0);
      $this->integer((int) $input['olas_ttr_id'])->isEqualTo(0);

      $this->integer((int) $input['_add_validation'])->isEqualTo(0);

      $this->array($input['users_id_validate'])->size->isEqualTo(0);
      $this->integer((int) $input['type'])->isEqualTo(\Ticket::INCIDENT_TYPE);
      $this->array($input['_documents_id'])->size->isEqualTo(0);
      $this->array($input['_tasktemplates_id'])->size->isEqualTo(0);
      $this->array($input['_filename'])->size->isEqualTo(0);
      $this->array($input['_tag_filename'])->size->isEqualTo(0);
   }
}
