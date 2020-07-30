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

namespace tests\units;

use \atoum;

use \DbTestCase;

class MailCollector extends DbTestCase {
   private $collector;
   private $mailgate_id;

   public function testGetEmpty() {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->array($this->testedInstance->fields)
               ->isIdenticalTo([])
            ->boolean($this->testedInstance->getEmpty())
            ->array($this->testedInstance->fields)
               ->isIdenticalTo([
                  'id'                   => '',
                  'name'                 => '',
                  'host'                 => '',
                  'login'                => '',
                  'filesize_max'         => '2097152',
                  'is_active'            => 1,
                  'date_mod'             => '',
                  'comment'              => '',
                  'passwd'               => '',
                  'accepted'             => '',
                  'refused'              => '',
                  'errors'               => '',
                  'use_mail_date'        => '',
                  'date_creation'        => '',
                  'requester_field'      => '',
                  'add_cc_to_observer'   => ''
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

      $prepared = $this->testedInstance->prepareInput($oinput, 'add');
      $this->array($prepared)
         ->boolean['is_active']->isTrue()
         ->string['passwd']->isNotEqualTo($oinput['passwd']);

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
         $collector = $this->testedInstance;
         $this->collector = $collector;
      } else {
         $collector = $this->collector;
      }

      $this->mailgate_id = (int)$collector->add([
         'name'               => 'testuser',
         'login'              => 'testuser',
         'is_active'          => true,
         'passwd'             => 'applesauce',
         'mail_server'        => '127.0.0.1',
         'server_type'        => '/imap',
         'server_port'        => 143,
         'server_ssl'         => '',
         'server_cert'        => '/novalidate-cert',
         'add_cc_to_observer' => 1, //add ccuser as observer in ticket
         'requester_field'    => \MailCollector::REQUESTER_FIELD_REPLY_TO,
      ]);

      $this->integer($this->mailgate_id)->isGreaterThan(0);

      $this->boolean($collector->getFromDB($this->mailgate_id))->isTrue();
      $this->string($collector->fields['host'])->isIdenticalTo('{127.0.0.1:143/imap/novalidate-cert}');
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

      /* FUPs
      $ticket = new \Ticket();
      $tid = $ticket->add([
         'name'                  => 'database issue',
         'content'               => 'It seems one field from the last migration has not been added in my database.',
         '_users_id_requester'   => $tuid
      ]);
      $this->integer($tid)->isGreaterThan(0);
      $DB->update(\Ticket::getTable(), ['id' => 1155], ['id' => $tid]);*/

      //$collector = new \mock\MailCollector;
      //$this->calling($collector)->getReplyMatch = "/GLPI-Ticket-(1155)/";
      //$this->collector = $collector;

      $this->doConnect();
      $this->collector->maxfetch_emails = 1000; // Be sure to fetch all mails from test suite
      $msg = $this->collector->collect($this->mailgate_id);
      $this->variable($msg)->isIdenticalTo('Number of messages: available=16, retrieved=16, refused=2, errors=1, blacklisted=0');
      $rejecteds = iterator_to_array($DB->request(['FROM' => \NotImportedEmail::getTable()]));

      $this->array($rejecteds)->hasSize(2);
      foreach ($rejecteds as $rejected) {
         $this->array($rejected)
            ->variable['from']->isIdenticalTo('unknown@glpi-project.org')
            ->variable['reason']->isEqualTo(\NotImportedEmail::USER_UNKNOWN);
      }

      // Check mails having "tech" user as requester
      $iterator = $DB->request([
         'SELECT' => ['t.id', 't.name', 't.content', 'tu.users_id'],
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

      $this->integer(count($iterator))->isIdenticalTo(5);
      $names = [];
      while ($data = $iterator->next()) {
         $names[] = $data['name'];
         $this->dump($data['content']);
      }

      $expected_names = [
         'PHP fatal error',
         'Re: [GLPI #0001155] New ticket database issue',
         'Ticket with observer',
         'Re: [GLPI #0038927] Update - Issues with new Windows 10 machine',
         'A message without to header',
      ];
      $this->array($names)->isIdenticalTo($expected_names);

      // Check mails having "normal" user as requester
      $iterator = $DB->request([
         'SELECT' => ['t.id', 't.name', 't.content', 'tu.users_id'],
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
            'tu.users_id'  => $nuid,
            'tu.type'      => \CommonITILActor::REQUESTER
         ]
      ]);

      $this->integer(count($iterator))->isIdenticalTo(9);
      $names = [];
      while ($data = $iterator->next()) {
         $names[] = $data['name'];
         $this->dump($data['content']);
      }

      $expected_names = [
         'Test import mail avec emoticons unicode',
         'Test images',
         'Test\'ed issue',
         'Test Email from Outlook',
         'No contenttype',
         'проверка',
         'тест2',
         'Inlined image with no Content-Disposition',
         'This is a mail without subject.', // No subject = name is set using ticket contents
      ];
      $this->array($names)->isIdenticalTo($expected_names);

      //load ticket with observer for user normal
      //see function doConnect
      //wich allow to add cc as observer (add_cc_to_observer = true)
      $iterator = $DB->request([
         'SELECT' => ['t.id', 't.name', 't.content', 'tu.users_id'],
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
            'tu.users_id'  => $nuid,
            'tu.type'      => \CommonITILActor::OBSERVER
         ]
      ]);

      $this->integer(count($iterator))->isIdenticalTo(1);
      $names = [];
      while ($data = $iterator->next()) {
         $names[] = $data['name'];
         $this->dump($data['content']);
      }

      $expected_names = [
         'Ticket with observer',
      ];
      $this->array($names)->isIdenticalTo($expected_names);

      /* FUPs
       * A followup should have een created from mail 04
       * but I've not been able to setup tests correctly on this point.
      $iterator = $DB->request(['FROM' => \ITILFollowup::getTable()]);
      var_dump('Checking fups');
      while ($data = $iterator->next()) {
         print_r($data);
      }
       */

      // Check creation of expected documents
      $expected_docs = [
         '00-logoteclib.png',
         '01-Screenshot-2018-4-12 Observatoire - France très haut débit.png',
         '01-test.JPG',
         '15-image001.png',
      ];

      $iterator = $DB->request(
         [
            'SELECT' => ['d.filename'],
            'FROM'   => \Document::getTable() . " AS d",
            'INNER JOIN'   => [
               \Document_Item::getTable() . " AS d_item"  => [
                  'ON'  => [
                     'd'      => 'id',
                     'd_item' => 'documents_id',
                     [
                        'AND' => [
                           'd_item.itemtype'      => 'Ticket',
                           'd_item.date_creation' => $_SESSION["glpi_currenttime"],
                        ]
                     ]
                  ]
               ]
            ]
         ]
      );

      $this->integer(count($iterator))->isIdenticalTo(count($expected_docs));

      $filenames = [];
      while ($data = $iterator->next()) {
         $filenames[] = $data['filename'];
      }
      $this->array($filenames)->isIdenticalTo($expected_docs);
   }
}
