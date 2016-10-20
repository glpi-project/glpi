<?php
/*
-------------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2015-2016 Teclib'.

http://glpi-project.org

based on GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2003-2014 by the INDEPNET Development Team.

-------------------------------------------------------------------------

LICENSE

This file is part of GLPI.

GLPI is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
 */

/* Test for inc/notificationtargetticket.class.php */

class NotificationTargetTicketTest extends DbTestCase {


   /**
    * @covers NotificationTargetTicket::getTags
    * @covers NotificationTargetCommonITILObject::getTags
    * @covers NotificationTargetTicket::getDatasForObject
    * @covers NotificationTargetCommonITILObject::getDatasForObject
    */
   public function testgetDatasForObject() {
      global $CFG_GLPI;

      $tkt = getItemByTypeName('Ticket', '_ticket01');
      $notiftargetticket = new NotificationTargetTicket( '', 'new', $tkt );
      $notiftargetticket->getTags( ) ;

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

      $this->assertEquals($expected, $notiftargetticket->tag_descriptions['lang']['##lang.task.categorycomment##']);
      $this->assertEquals($expected, $notiftargetticket->tag_descriptions['tag']['##task.categorycomment##']);

      // advanced test for ##task.categorycomment## tag
      // test of the getDatasForObject for default language en_US
      $expected = [
                     [
                     '##task.id##'              => 1,
                     '##task.isprivate##'       => 'No',
                     '##task.author##'          => '_test_user',
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

      $ret = $notiftargetticket->getDatasForObject( $tkt, array() ) ;

      $this->assertEquals($expected, $ret['tasks']);

      // test of the getDatasForObject for default language fr_FR
      $CFG_GLPI['translate_dropdowns'] = 1 ;
      $_SESSION["glpilanguage"] = Session::loadLanguage( 'fr_FR' ) ;
      $_SESSION['glpi_dropdowntranslations'] = DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);
      //$expected = array('name'    => 'FR - _cat_1 > FR - _subcat_1',
      //                  'comment' => 'FR - Commentaire pour sous-catégorie _subcat_1') ;
      //$ret = Dropdown::getDropdownName( 'glpi_taskcategories',  $subCat->getID(), true, true, false ) ;
      //$this->assertEquals($expected, $ret);

      $ret = $notiftargetticket->getDatasForObject( $tkt, array() ) ;

      $expected = [
                     [
                     '##task.id##'              => 1,
                     '##task.isprivate##'       => 'Non',
                     '##task.author##'          => '_test_user',
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

      $this->assertEquals($expected, $ret['tasks']);

      // switch back to default language
      $_SESSION["glpilanguage"] = Session::loadLanguage( 'en_US' ) ;

   }
}
