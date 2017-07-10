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

use \DbTestCase;

/* Test for inc/notificationtargetticket.class.php */

class NotificationTargetTicket extends DbTestCase {

   public function testgetDataForObject() {
      global $CFG_GLPI;

      $tkt = getItemByTypeName('Ticket', '_ticket01');
      $notiftargetticket = new \NotificationTargetTicket(getItemByTypeName('Entity', '_test_root_entity', true), 'new', $tkt );
      $notiftargetticket->getTags();

      // basic test for ##task.categorycomment## tag
      $expected = [
         'tag'             => 'task.categorycomment',
         'value'           => true,
         'label'           => 'Category comment',
         'events'          => 0,
         'foreach'         => false,
         'lang'            => true,
         'allowed_values'  => [],
         ];

      $this->array($notiftargetticket->tag_descriptions['lang']['##lang.task.categorycomment##'])
         ->isIdenticalTo($expected);
      $this->array($notiftargetticket->tag_descriptions['tag']['##task.categorycomment##'])
         ->isIdenticalTo($expected);

      // basic test for ##task.categorid## tag
      $expected = [
         'tag'             => 'task.categoryid',
         'value'           => true,
         'label'           => 'Category id',
         'events'          => 0,
         'foreach'         => false,
         'lang'            => true,
         'allowed_values'  => [],
         ];
      $this->array($notiftargetticket->tag_descriptions['lang']['##lang.task.categoryid##'])
         ->isIdenticalTo($expected);
      $this->array($notiftargetticket->tag_descriptions['tag']['##task.categoryid##'])
         ->isIdenticalTo($expected);

      // advanced test for ##task.categorycomment## and ##task.categoryid## tags
      // test of the getDataForObject for default language en_GB
      $taskcat = getItemByTypeName('TaskCategory', '_subcat_1');
      $expected = [
                     [
                     '##task.id##'              => '1',
                     '##task.isprivate##'       => 'No',
                     '##task.author##'          => '_test_user',
                     '##task.categoryid##'      => $taskcat->getID(),
                     '##task.category##'        => '_cat_1 > _subcat_1',
                     '##task.categorycomment##' => 'Comment for sub-category _subcat_1',
                     '##task.date##'            => '2016-10-19 11:50',
                     '##task.description##'     => 'Task to be done',
                     '##task.time##'            => '0 seconds',
                     '##task.status##'          => 'To do',
                     '##task.user##'            => '_test_user',
                     '##task.group##'           => '',
                     '##task.begin##'           => '',
                     '##task.end##'             => ''
                     ]
                  ];

      $basic_options = [
         'additionnaloption' => [
            'usertype' => ''
         ]
      ];
      $ret = $notiftargetticket->getDataForObject($tkt, $basic_options);

      $this->array($ret['tasks'])->isIdenticalTo($expected);

      // test of the getDataForObject for default language fr_FR
      $CFG_GLPI['translate_dropdowns'] = 1;
      $_SESSION["glpilanguage"] = \Session::loadLanguage( 'fr_FR' );
      $_SESSION['glpi_dropdowntranslations'] = \DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);

      $ret = $notiftargetticket->getDataForObject($tkt, $basic_options);

      $expected = [
                     [
                     '##task.id##'              => '1',
                     '##task.isprivate##'       => 'Non',
                     '##task.author##'          => '_test_user',
                     '##task.categoryid##'      => $taskcat->getID(),
                     '##task.category##'        => 'FR - _cat_1 > FR - _subcat_1',
                     '##task.categorycomment##' => 'FR - Commentaire pour sous-catégorie _subcat_1',
                     '##task.date##'            => '2016-10-19 11:50',
                     '##task.description##'     => 'Task to be done',
                     '##task.time##'            => '0 seconde',
                     '##task.status##'          => 'A faire',
                     '##task.user##'            => '_test_user',
                     '##task.group##'           => '',
                     '##task.begin##'           => '',
                     '##task.end##'             => ''
                     ]
                  ];

      $this->array($ret['tasks'])->isIdenticalTo($expected);

      // switch back to default language
      $_SESSION["glpilanguage"] = \Session::loadLanguage('en_GB');
   }
}
