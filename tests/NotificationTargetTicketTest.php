<<<<<<< HEAD
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
      $notiftargetticket = new NotificationTargetTicket(getItemByTypeName('Entity', '_test_root_entity',  true), 'new', $tkt );
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
      $this->assertEquals($expected, $notiftargetticket->tag_descriptions['lang']['##lang.task.categoryid##']);
      $this->assertEquals($expected, $notiftargetticket->tag_descriptions['tag']['##task.categoryid##']);

      // advanced test for ##task.categorycomment## and ##task.categoryid## tags
      // test of the getDatasForObject for default language en_US
      $taskcat = getItemByTypeName('TaskCategory', '_subcat_1');
      $expected = [
                     [
                     '##task.id##'              => 1,
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

      $ret = $notiftargetticket->getDatasForObject( $tkt, array() ) ;

      $this->assertEquals($expected, $ret['tasks']);

      // test of the getDatasForObject for default language fr_FR
      $CFG_GLPI['translate_dropdowns'] = 1 ;
      $_SESSION["glpilanguage"] = Session::loadLanguage( 'fr_FR' ) ;
      $_SESSION['glpi_dropdowntranslations'] = DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);

      $ret = $notiftargetticket->getDatasForObject( $tkt, array() ) ;

      $expected = [
                     [
                     '##task.id##'              => 1,
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

      $this->assertEquals($expected, $ret['tasks']);

      // switch back to default language
      $_SESSION["glpilanguage"] = Session::loadLanguage( 'en_US' ) ;

   }
}
=======
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

      $this->assertEquals($expected, $notiftargetticket->tag_descriptions['lang']['##lang.task.categoryid##']);
      $this->assertEquals($expected, $notiftargetticket->tag_descriptions['tag']['##task.categoryid##']);


      // advanced test for ##task.categorycomment## and ##task.categoryid## tags
      // test of the getDatasForObject for default language en_US
      $taskcat = getItemByTypeName('TaskCategory', '_subcat_1');
      $expected = [
                     [
                     '##task.id##'              => 1,
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

      $ret = $notiftargetticket->getDatasForObject( $tkt, array() ) ;

      $this->assertEquals($expected, $ret['tasks']);


      // test of the getDatasForObject for default language fr_FR
      $CFG_GLPI['translate_dropdowns'] = 1 ;
      $_SESSION["glpilanguage"] = Session::loadLanguage( 'fr_FR' ) ;
      $_SESSION['glpi_dropdowntranslations'] = DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);

      $ret = $notiftargetticket->getDatasForObject( $tkt, array() ) ;

      $expected = [
                     [
                     '##task.id##'              => 1,
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

      $this->assertEquals($expected, $ret['tasks']);

      // switch back to default language
      $_SESSION["glpilanguage"] = Session::loadLanguage( 'en_US' ) ;

   }
}
>>>>>>> upstream/9.1/bugfixes
