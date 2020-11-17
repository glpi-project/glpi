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
                  'add_cc_to_observer'   => '',
                  'collect_only_unread'  => ''
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
         'is_active' => 1
      ]);
      $this->integer($nid)->isGreaterThan(0);

      $this->integer($this->testedInstance->countActiveCollectors())->isIdenticalTo(1);
      $this->integer($this->testedInstance->countCollectors(true))->isIdenticalTo(1);
      $this->integer($this->testedInstance->countCollectors())->isIdenticalTo(1);

      $this->boolean(
         $this->testedInstance->update([
            'id'        => $this->testedInstance->fields['id'],
            'is_active' => 0
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
         'name'                  => 'testuser',
         'login'                 => 'testuser',
         'is_active'             => 1,
         'passwd'                => 'applesauce',
         'mail_server'           => '127.0.0.1',
         'server_type'           => '/imap',
         'server_port'           => 143,
         'server_ssl'            => '',
         'server_cert'           => '/novalidate-cert',
         'add_cc_to_observer'    => 1,
         'collect_only_unread'   => 1,
         'requester_field'       => \MailCollector::REQUESTER_FIELD_REPLY_TO,
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

      // Collect all mails
      $this->doConnect();
      $this->collector->maxfetch_emails = 1000; // Be sure to fetch all mails from test suite
      $msg = $this->collector->collect($this->mailgate_id);

      $total_count                     = count(glob(GLPI_ROOT . '/tests/emails-tests/*.eml'));
      $expected_refused_count          = 2;
      $expected_error_count            = 2;
      $expected_blacklist_count        = 0;
      $expected_expected_already_seen  = 0;

      $this->variable($msg)->isIdenticalTo(
         sprintf(
            'Number of messages: available=%1$s, already imported=%2$d, retrieved=%3$s, refused=%4$s, errors=%5$s, blacklisted=%6$s',
            $total_count,
            $expected_expected_already_seen,
            $total_count - $expected_expected_already_seen,
            $expected_refused_count,
            $expected_error_count,
            $expected_blacklist_count
         )
      );

      // Check not imported emails
      $not_imported_specs = [
         [
            'subject' => 'Have a problem, can you help me?',
            'from'    => 'unknown@glpi-project.org',
            'to'      => 'unittests@glpi-project.org',
            'reason'  => \NotImportedEmail::USER_UNKNOWN,
         ],
         [
            'subject' => 'Test\'ed issue',
            'from'    => 'unknown@glpi-project.org',
            'to'      => 'unittests@glpi-project.org',
            'reason'  => \NotImportedEmail::USER_UNKNOWN,
         ],
         [
            'subject' => null, // Subject is empty has mail was not processed
            'from'    => '', // '' as value is not nullable in DB
            'to'      => '', // '' as value is not nullable in DB
            'reason'  => \NotImportedEmail::FAILED_OPERATION,
         ]
      ];
      $iterator = $DB->request(['FROM' => \NotImportedEmail::getTable()]);
      $this->integer(count($iterator))->isIdenticalTo(count($not_imported_specs));

      $not_imported_values = [];
      while ($data = $iterator->next()) {
         $not_imported_values[] = [
            'subject' => $data['subject'],
            'from'    => $data['from'],
            'to'      => $data['to'],
            'reason'  => $data['reason'],
         ];
         $this->integer($data['mailcollectors_id'])->isIdenticalTo($this->mailgate_id);
      }
      $this->array($not_imported_values)->isIdenticalTo($not_imported_specs);

      // Check created tickets and their actors
      $actors_specs = [
         // Mails having "tech" user as requester
         [
            'users_id'      => $tuid,
            'actor_type'    => \CommonITILActor::REQUESTER,
            'tickets_names' => [
               'PHP fatal error',
               'Re: [GLPI #0001155] New ticket database issue',
               'Ticket with observer',
               'Re: [GLPI #0038927] Update - Issues with new Windows 10 machine',
               'A message without to header',
            ]
         ],
         // Mails having "normal" user as requester
         [
            'users_id'      => $nuid,
            'actor_type'    => \CommonITILActor::REQUESTER,
            'tickets_names' => [
               'Test import mail avec emoticons unicode',
               'Test images',
               'Test\'ed issue',
               'Test Email from Outlook',
               'No contenttype',
               'проверка',
               'тест2',
               'Inlined image with no Content-Disposition',
               'This is a mail without subject.', // No subject = name is set using ticket contents
               'Image tag splitted on multiple lines',
               'Attachement having filename using RFC5987 (multiple lines)',
               'Mono-part HTML message',
            ]
         ],
         // Mails having "normal" user as observer (add_cc_to_observer = true)
         [
            'users_id'      => $nuid,
            'actor_type'    => \CommonITILActor::OBSERVER,
            'tickets_names' => [
               'Ticket with observer',
            ]
         ],
      ];

      // Tickets on which content should be checked (key is ticket name)
      $tickets_contents = [
         // Plain text on mono-part email
         'PHP fatal error' => 'On some cases, doing the following:&lt;br /&gt;# blahblah&lt;br /&gt;&lt;br /&gt;Will cause a PHP fatal error:&lt;br /&gt;# blahblah&lt;br /&gt;&lt;br /&gt;Best regards,',
         // HTML on multi-part email
         'Re: [GLPI #0038927] Update - Issues with new Windows 10 machine' => '&lt;p&gt;This message have reply to header, requester should be get from this header.&lt;/p&gt;',
         'Mono-part HTML message' => '&lt;p&gt;This HTML message does not use &lt;strong&gt;"multipart/alternative"&lt;/strong&gt; format.&lt;/p&gt;',
      ];

      foreach ($actors_specs as $actor_specs) {
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
               'tu.users_id'  => $actor_specs['users_id'],
               'tu.type'      => $actor_specs['actor_type'],
            ]
         ]);

         $this->integer(count($iterator))->isIdenticalTo(count($actor_specs['tickets_names']));

         $names = [];
         while ($data = $iterator->next()) {
            $name = $data['name'];

            if (array_key_exists($name, $tickets_contents)) {
               $this->string($data['content'])->isEqualTo($tickets_contents[$name]);
            }

            $this->string($data['content'])->notContains('cid:'); // check that image were correctly imported

            $names[] = $name;
         }

         $this->array($names)->isIdenticalTo($actor_specs['tickets_names']);
      }

      // Check creation of expected documents
      $expected_docs = [
         '00-logoteclib.png',
         '01-Screenshot-2018-4-12 Observatoire - France très haut débit.png',
         '01-test.JPG',
         '15-image001.png',
         '18-blank.gif',
         '19-ʂǷèɕɩɐɫ ȼɦâʁȿ.gif',
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

      $filenames = [];
      while ($data = $iterator->next()) {
         $filenames[] = $data['filename'];
      }
      $this->array($filenames)->isIdenticalTo($expected_docs);

      $this->integer(count($iterator))->isIdenticalTo(count($expected_docs));
   }
}
