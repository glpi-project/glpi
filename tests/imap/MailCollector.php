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

namespace tests\units;

use \atoum;

use \DbTestCase;

class MailCollector extends DbTestCase {
   private $collector;

   public function testGetEmpty() {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->array($this->testedInstance->fields)
               ->isIdenticalTo([])
            ->boolean($this->testedInstance->getEmpty())
            ->array($this->testedInstance->fields)
               ->isIdenticalTo([
                  'id'              => '',
                  'name'            => '',
                  'host'            => '',
                  'login'           => '',
                  'filesize_max'    => '2097152',
                  'is_active'       => 1,
                  'date_mod'        => '',
                  'comment'         => '',
                  'passwd'          => '',
                  'accepted'        => '',
                  'refused'         => '',
                  'use_kerberos'    => '',
                  'errors'          => '',
                  'use_mail_date'   => '',
                  'date_creation'   => '',
                  'requester_field' => ''
               ]);
   }

   protected function subjectProvider() {
      return [
         [
            'raw'       => 'This is a subject',
            'expected'  => 'This is a subject'
         ], [
            'raw'       => "With a \ncarriage return",
            'expected'  => "With a \ncarriage return"
         ], [
            'raw'       => 'We have a problem, <strong>URGENT</strong>',
            'expected'  => 'We have a problem, &lt;strong&gt;URGENT&lt;/strong&gt;'
         ], [ //dunno why...
            'raw'       => 'Subject with =20 character',
            'expected'  => "Subject with \n character"
         ]
      ];
   }

   /**
    * @dataProvider subjectProvider
    */
   public function testCleanSubject($raw, $expected) {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->cleanSubject($raw))
               ->isIdenticalTo($expected);
   }

   public function testListEncodings() {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->array($this->testedInstance->listEncodings())
               ->containsValues(['utf-8', 'iso-8859-1', 'iso-8859-14', 'cp1252']);
   }

   public function testPrepareInput() {
      $this->newTestedInstance();

      $oinput = [
         'passwd'    => 'Ph34r',
         'is_active' => true
      ];

      $this->array($this->testedInstance->prepareInput($oinput, 'add'))
         ->isIdenticalTo([
            'passwd'    => \Toolbox::encrypt($oinput["passwd"], GLPIKEY),
            'is_active' => true
         ]);

      //empty password means no password.
      $oinput = [
         'passwd'    => '',
         'is_active' => true
      ];

      $this->array($this->testedInstance->prepareInput($oinput, 'add'))
         ->isIdenticalTo(['is_active' => true]);

      //manage host
      $oinput = [
         'mail_server' => 'mail.example.com'
      ];

      $this->array($this->testedInstance->prepareInput($oinput, 'add'))
           ->isIdenticalTo(['mail_server' => 'mail.example.com', 'host' => '{mail.example.com}']);

      //manage host
      $oinput = [
         'mail_server'     => 'mail.example.com',
         'server_port'     => 143,
         'server_mailbox'  => 'bugs'
      ];

      $this->array($this->testedInstance->prepareInput($oinput, 'add'))
           ->isIdenticalTo([
              'mail_server'      => 'mail.example.com',
              'server_port'      => 143,
              'server_mailbox'   => 'bugs',
              'host'             => '{mail.example.com:143}bugs'
           ]);

      $oinput = [
         'passwd'          => 'Ph34r',
         '_blank_passwd'   => true
      ];
      $this->array($this->testedInstance->prepareInputForUpdate($oinput))
         ->isIdenticalTo(['passwd' => '', '_blank_passwd' => true]);
   }

   public function testCounts() {
      $this->newTestedInstance();

      $this->integer($this->testedInstance->countActiveCollectors())->isIdenticalTo(0);
      $this->integer($this->testedInstance->countCollectors(true))->isIdenticalTo(0);
      $this->integer($this->testedInstance->countCollectors())->isIdenticalTo(0);

      //Add an active collector
      $nid = (int)$this->testedInstance->add([
         'name'      => 'Maille name',
         'is_active' => true
      ]);
      $this->integer($nid)->isGreaterThan(0);

      $this->integer($this->testedInstance->countActiveCollectors())->isIdenticalTo(1);
      $this->integer($this->testedInstance->countCollectors(true))->isIdenticalTo(1);
      $this->integer($this->testedInstance->countCollectors())->isIdenticalTo(1);

      $this->boolean(
         $this->testedInstance->update([
            'id'        => $this->testedInstance->fields['id'],
            'is_active' => false
         ])
      )->isTrue();

      $this->integer($this->testedInstance->countActiveCollectors())->isIdenticalTo(0);
      $this->integer($this->testedInstance->countCollectors(true))->isIdenticalTo(0);
      $this->integer($this->testedInstance->countCollectors())->isIdenticalTo(1);
   }

   private function doConnect() {
      if (null === $this->collector) {
         $this->newTestedInstance();
         $collector = $this->testedInstance();
      } else {
         $collector = $this->collector;
      }

      $this->integer(
         (int)$collector->add([
            'name'         => 'testuser',
            'login'        => 'testuser',
            'is_active'    => true,
            'passwd'       => 'applesauce',
            'mail_server'  => '127.0.0.1',
            'server_type'  => '/imap',
            'server_port'  => 143,
            'server_ssl'   => '',
            'server_cert'  => '/novalidate-cert'
         ])
      )->isGreaterThan(0);

      $this->boolean($collector->getFromDB($collector->fields['id']))->isTrue();
      $this->string($collector->fields['host'])->isIdenticalTo('{127.0.0.1:143/imap}');
      $collector->connect();
      $this->variable($collector->fields['errors'])->isEqualTo(0);
   }

   public function testCollect() {
      global $DB;

      //assign email to user
      $nuid = getItemByTypeName('User', 'normal', true);
      $uemail = new \UserEmail();
      $this->integer(
         (int)$uemail->add([
            'users_id'     => $nuid,
            'is_default'   => 1,
            'email'        => 'normal@glpi-project.org'
         ])
      )->isGreaterThan(0);
      $tuid = getItemByTypeName('User', 'tech', true);
      $this->integer(
         (int)$uemail->add([
            'users_id'     => $tuid,
            'is_default'   => 1,
            'email'        => 'tech@glpi-project.org'
         ])
      )->isGreaterThan(0);

      $ticket = new \Ticket();
      $tid = $ticket->add([
         'name'                  => 'database issue',
         'content'               => 'It seems one field from the last migration has not been added in my database.',
         '_users_id_requester'   => $tuid
      ]);
      $this->integer($tid)->isGreaterThan(0);

      $collector = new \mock\MailCollector;
      $this->calling($collector)->getMessageMatch = "/GLPI-(1155)\.[0-9]+\.[0-9]+@\w*/";
      $this->collector = $collector;

      $this->doConnect();
      $msg = $collector->collect();
      $this->variable($msg)->isIdenticalTo('Number of messages: available=5, retrieved=5, refused=1, errors=0, blacklisted=0');
      $rejecteds = iterator_to_array($DB->request(['FROM' => \NotImportedEmail::getTable()]));

      $this->array($rejecteds)->hasSize(1);
      $this->array(array_pop($rejecteds))
         ->variable['from']->isIdenticalTo('unknwon@glpi-project.org')
         ->variable['reason']->isEqualTo(\NotImportedEmail::USER_UNKNOWN);

      $iterator = $DB->request([
         'SELECT' => ['t.id', 't.name', 'tu.users_id'],
         'FROM'   => \Ticket::getTable() . " AS t",
         'INNER JOIN'   => [
            \Ticket_User::getTable() . " AS tu"  => [
               'ON'  => [
                  't'   => 'id',
                  'tu'  => 'tickets_id'
               ]
            ]
         ],
         'WHERE'  => [
            'tu.users_id'  => $tuid,
            'tu.type'      => \CommonITILActor::REQUESTER
         ]
      ]);

      $this->integer(count($iterator))->isIdenticalTo(3);
      $names = [];
      while ($data = $iterator->next()) {
         $names[] = $data['name'];
      }

      $expected_names = [
         'database issue',
         'PHP fatal error',
         'Re: [GLPI #0001155] New ticket database issue'
      ];
      $this->array($names)->isIdenticalTo($expected_names);

      /**
       * A followup should have een created from mail 04; with
       * mock'ed getMessageMatch method
       * but I've not been able to setup tests correctly on this point.
      $iterator = $DB->request(['FROM' => \ITILFollowup::getTable()]);
      while ($data = $iterator->next()) {
         print_r($data);
      }
       */
   }
}
