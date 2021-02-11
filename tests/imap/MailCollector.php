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

namespace tests\units;

use Config;
use DbTestCase;
use ITILFollowup;
use Laminas\Mail\Storage\Message;
use NotificationTarget;
use NotificationTargetSoftwareLicense;
use NotificationTargetTicket;
use SoftwareLicense;
use Ticket;

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
      $_SESSION['glpicronuserrunning'] = 'cron_phpunit';
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
      $_SESSION['glpicronuserrunning'] = 'cron_phpunit';
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

   protected function messageIdHeaderProvider() {
      $root_ent_id = getItemByTypeName('Entity', '_test_root_entity', true);

      $ticket_notif = new NotificationTargetTicket($root_ent_id, 'test_event', getItemByTypeName('Ticket', '_ticket01'));
      $soft_notif = new NotificationTargetSoftwareLicense($root_ent_id, 'test_event', getItemByTypeName('SoftwareLicense', '_test_softlic_1'));
      $base_notif = new NotificationTarget();

      $uuid = Config::getUuid('notification');

      $time = time();
      $rand = rand();
      $uname = 'localhost';

      return [
         [
            'headers'  => [],
            'expected' => false,
         ],
         [
            'headers'  => [
               'message-id' => 'donotknow',
            ],
            'expected' => false,
         ],
         [
            'headers'  => [
               'message-id' => "GLPI-1.{$time}.{$rand}@{$uname}", // old ticket format
            ],
            'expected' => true,
         ],
         [
            'headers'  => [
               'message-id' => "GLPI-SoftwareLicence-1.{$time}.{$rand}@{$uname}", // old format with object relation
            ],
            'expected' => true,
         ],
         [
            'headers'  => [
               'message-id' => "GLPI.{$time}.{$rand}@{$uname}", // old format without object relation
            ],
            'expected' => true,
         ],
         [
            'headers'  => [
               'message-id' => $ticket_notif->getMessageID(), // new format for ticket
            ],
            'expected' => true,
         ],
         [
            'headers'  => [
               'message-id' => $soft_notif->getMessageID(), // new format with object relation
            ],
            'expected' => true,
         ],
         [
            'headers'  => [
               'message-id' => $base_notif->getMessageID(), // new format without object relation
            ],
            'expected' => true,
         ],
         [
            'headers'  => [
               'message-id' => "GLPI_notmyuuid-Ticket-1.{$time}.{$rand}@{$uname}", // new format with object relation
            ],
            'expected' => false,
         ],
         [
            'headers'  => [
               'message-id' => "GLPI_notmyuuid.{$time}.{$rand}@{$uname}", // new format without object relation
            ],
            'expected' => false,
         ],
      ];
   }

   /**
    * @dataProvider messageIdHeaderProvider
    */
   public function testIsMessageSentByGlpi(array $headers, bool $expected) {
      $this->newTestedInstance();

      $message = new Message(
         [
            'headers' => $headers,
            'content' => 'Message contents...',
         ]
      );

      $this->boolean($this->testedInstance->isMessageSentByGlpi($message))->isEqualTo($expected);
   }

   protected function itemReferenceHeaderProvider() {
      $root_ent_id = getItemByTypeName('Entity', '_test_root_entity', true);

      $ticket_id = getItemByTypeName('Ticket', '_ticket01', true);
      $ticket_notif = new NotificationTargetTicket($root_ent_id, 'test_event', getItemByTypeName('Ticket', '_ticket01'));

      $soft_id   = getItemByTypeName('SoftwareLicense', '_test_softlic_1', true);
      $soft_notif = new NotificationTargetSoftwareLicense($root_ent_id, 'test_event', getItemByTypeName('SoftwareLicense', '_test_softlic_1'));

      $uuid = Config::getUuid('notification');

      $time1 = time() - 548;
      $time2 = $time1 - 1567;
      $rand1 = rand();
      $rand2 = rand();
      $uname1 = 'localhost';
      $uname2 = 'mail.glpi-project.org';

      return [
         // invalid header
         [
            'headers'           => [
               'in-reply-to' => 'notavalidvalue',
               'references'  => 'donotknow',
            ],
            'expected_itemtype' => null,
            'expected_items_id' => null,
            'accepted'          => true,
         ],
         // old ticket format - found item
         [
            'headers'           => [
               'in-reply-to' => "GLPI-{$ticket_id}.{$time1}.{$rand1}@{$uname1}",
            ],
            'expected_itemtype' => Ticket::class,
            'expected_items_id' => $ticket_id,
            'accepted'          => true,
         ],
         [
            'headers'           => [
               'references'  => "GLPI-{$ticket_id}.{$time1}.{$rand1}@{$uname2}",
            ],
            'expected_itemtype' => Ticket::class,
            'expected_items_id' => $ticket_id,
            'accepted'          => true,
         ],
         // old ticket format - invalid items_id
         [
            'headers'           => [
               'in-reply-to' => "GLPI-9999999.{$time2}.{$rand2}@{$uname1}",
            ],
            'expected_itemtype' => null,
            'expected_items_id' => null,
            'accepted'          => true,
         ],
         // old items header format - found item
         [
            'headers'           => [
               'in-reply-to' => "GLPI-SoftwareLicense-{$soft_id}.{$time1}.{$rand2}@{$uname2}",
            ],
            'expected_itemtype' => SoftwareLicense::class,
            'expected_items_id' => $soft_id,
            'accepted'          => true,
         ],
         // old items header format - invalid itemtype
         [
            'headers'           => [
               'references'  => "GLPI-UnknownType-{$soft_id}.{$time2}.{$rand2}@{$uname1}",
            ],
            'expected_itemtype' => null,
            'expected_items_id' => null,
            'accepted'          => true,
         ],
         // old items header format - invalid items_id
         [
            'headers'           => [
               'references'  => "GLPI-SoftwareLicense-9999999.{$time1}.{$rand1}@{$uname2}",
            ],
            'expected_itemtype' => null,
            'expected_items_id' => null,
            'accepted'          => true,
         ],
         // new header format - found item
         [
            'headers'           => [
               'in-reply-to' => $ticket_notif->getMessageID(),
            ],
            'expected_itemtype' => Ticket::class,
            'expected_items_id' => $ticket_id,
            'accepted'          => true,
         ],
         [
            'headers'           => [
               'references'  => $soft_notif->getMessageID(),
            ],
            'expected_itemtype' => SoftwareLicense::class,
            'expected_items_id' => $soft_id,
            'accepted'          => true,
         ],
         [
            'headers'           => [
               'in-reply-to' => 'notavalidvalue',
               'references'  => $soft_notif->getMessageID(),
            ],
            'expected_itemtype' => SoftwareLicense::class,
            'expected_items_id' => $soft_id,
            'accepted'          => true,
         ],
         [
            'headers'           => [
               'in-reply-to' => $soft_notif->getMessageID(),
               'references'  => 'donotknow',
            ],
            'expected_itemtype' => SoftwareLicense::class,
            'expected_items_id' => $soft_id,
            'accepted'          => true,
         ],
         // new header format - invalid itemtype
         [
            'headers'           => [
               'references'  => "GLPI_{$uuid}-UnknownType-{$ticket_id}.{$time2}.{$rand2}@{$uname1}",
            ],
            'expected_itemtype' => null,
            'expected_items_id' => null,
            'accepted'          => true,
         ],
         // new header format - invalid items_id
         [
            'headers'           => [
               'references'  => "GLPI_{$uuid}-Ticket-9999999.{$time1}.{$rand1}@{$uname1}",
            ],
            'expected_itemtype' => null,
            'expected_items_id' => null,
            'accepted'          => true,
         ],
         // new header format - uuid from another GLPI instance
         [
            'headers'           => [
               'in-reply-to' => "GLPI_notmyuuid-Ticket-{$ticket_id}.{$time1}.{$rand1}@{$uname2}",
            ],
            'expected_itemtype' => null,
            'expected_items_id' => null,
            'accepted'          => false,
         ],
         [
            'headers'           => [
               'references'  => "GLPI_notmyuuid-Ticket-{$ticket_id}.{$time2}.{$rand2}@{$uname1}",
            ],
            'expected_itemtype' => null,
            'expected_items_id' => null,
            'accepted'          => false,
         ],
      ];
   }

   /**
    * @dataProvider itemReferenceHeaderProvider
    */
   public function testGetItemFromHeader(
      array $headers,
      ?string $expected_itemtype,
      ?int $expected_items_id,
      bool $accepted
   ) {
      $this->newTestedInstance();

      $message = new Message(
         [
            'headers' => $headers,
            'content' => 'Message contents...',
         ]
      );

      $item = $this->testedInstance->getItemFromHeaders($message);

      if ($expected_itemtype === null) {
         $this->variable($item)->isNull();
      } else {
         $this->object($item)->isInstanceOf($expected_itemtype);
         $this->integer($item->getId())->isEqualTo($expected_items_id);
      }
   }

   /**
    * @dataProvider itemReferenceHeaderProvider
    */
   public function testIsResponseToMessageSentByAnotherGlpi(
      array $headers,
      ?string $expected_itemtype,
      ?int $expected_items_id,
      bool $accepted
   ) {
      $this->newTestedInstance();

      $message = new Message(
         [
            'headers' => $headers,
            'content' => 'Message contents...',
         ]
      );

      $this->boolean($this->testedInstance->isResponseToMessageSentByAnotherGlpi($message))->isEqualTo(!$accepted);
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
         'is_active'             => true,
         'passwd'                => 'applesauce',
         'mail_server'           => 'dovecot',
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
      $this->string($collector->fields['host'])->isIdenticalTo('{dovecot:143/imap/novalidate-cert}');
      $collector->connect();
      $this->variable($collector->fields['errors'])->isEqualTo(0);
   }

   public function testCollect() {
      global $DB;
      $_SESSION['glpicronuserrunning'] = 'cron_phpunit';

      // Force notification_uuid
      Config::setConfigurationValues('core', ['notification_uuid' => 't3StN0t1f1c4tiOnUUID']);

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
      $expected_blacklist_count        = 3;
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
               'Attachement having filename using RFC5987 (single line)',
               'Mono-part HTML message',
               '24.1 Test attachment with long multibyte filename',
               '24.2 Test attachment with short multibyte filename',
               '25 - Test attachment with invalid chars for OS'
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
         // Space is missing between "France" and "très" due to a bug in laminas-mail
         '01-Screenshot-2018-4-12 Observatoire - Francetrès haut débit.png',
         '01-test.JPG',
         '15-image001.png',
         '18-blank.gif',
         '19-ʂǷèɕɩɐɫ ȼɦâʁȿ.gif',
         '20-specïal chars.gif',
         '24.1-长文件名，将导致内容处置标头中的连续行.txt',
         '24.2-中国字符.txt',
         '25-New Text - Document.txt',
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

      // Check creation of expected followups
      $expected_followups = [
         [
            'items_id' => 100,
            'users_id' => $tuid,
            'content'  => 'This is a reply that references Ticket 100 in In-Reply-To header (old format).&lt;br /&gt;It should be added as followup.',
         ],
         [
            'items_id' => 100,
            'users_id' => $tuid,
            'content'  => 'This is a reply that references Ticket 100 in References header (old format).&lt;br /&gt;It should be added as followup.',
         ],
         [
            'items_id' => 101,
            'users_id' => $tuid,
            'content'  => 'This is a reply that references Ticket 101 in its subject.&lt;br /&gt;It should be added as followup.',
         ],
         [
            'items_id' => 100,
            'users_id' => $tuid,
            'content'  => 'This is a reply that references Ticket 100 in In-Reply-To header (new format).&lt;br /&gt;It should be added as followup.',
         ],
         [
            'items_id' => 100,
            'users_id' => $tuid,
            'content'  => 'This is a reply that references Ticket 100 in References header (new format).&lt;br /&gt;It should be added as followup.',
         ],
      ];

      foreach ($expected_followups as $expected_followup) {
         $this->integer(countElementsInTable(ITILFollowup::getTable(), $expected_followup))->isEqualTo(1);
      }
   }

   protected function mailServerProtocolsProvider() {
      return [
         [
            'cnx_string'        => '',
            'expected_type'     => '',
            'expected_protocol' => null,
            'expected_storage'  => null,
         ],
         [
            'cnx_string'        => '{mail.domain.org/imap}',
            'expected_type'     => 'imap',
            'expected_protocol' => \Laminas\Mail\Protocol\Imap::class,
            'expected_storage'  => \Laminas\Mail\Storage\Imap::class,
         ],
         [
            'cnx_string'        => '{mail.domain.org/imap/ssl/debug}INBOX',
            'expected_type'     => 'imap',
            'expected_protocol' => \Laminas\Mail\Protocol\Imap::class,
            'expected_storage'  => \Laminas\Mail\Storage\Imap::class,
         ],
         [
            'cnx_string'        => '{mail.domain.org/pop}',
            'expected_type'     => 'pop',
            'expected_protocol' => \Laminas\Mail\Protocol\Pop3::class,
            'expected_storage'  => \Laminas\Mail\Storage\Pop3::class,
         ],
         [
            'cnx_string'        => '{mail.domain.org/pop/ssl/tls}',
            'expected_type'     => 'pop',
            'expected_protocol' => \Laminas\Mail\Protocol\Pop3::class,
            'expected_storage'  => \Laminas\Mail\Storage\Pop3::class,
         ],
         [
            'cnx_string'        => '{mail.domain.org/unknown-type/ssl}',
            'expected_type'     => '',
            'expected_protocol' => null,
            'expected_storage'  => null,
         ],
      ];
   }

   /**
    * @dataProvider mailServerProtocolsProvider
    */
   public function testGetMailServerProtocols(
      string $cnx_string,
      string $expected_type,
      ?string $expected_protocol,
      ?string $expected_storage
   ) {
      $type = \Toolbox::parseMailServerConnectString($cnx_string)['type'];

      $this->string($type)->isEqualTo($expected_type);

      if ($expected_protocol !== null) {
         $this->object(\Toolbox::getMailServerProtocolInstance($type))->isInstanceOf($expected_protocol);
      } else {
         $this->variable(\Toolbox::getMailServerProtocolInstance($type))->isNull();
      }

      $params = [
         'host'     => 'dovecot',
         'user'     => 'testuser',
         'password' => 'applesauce',
      ];
      if ($expected_storage !== null) {
         $this->object(\Toolbox::getMailServerStorageInstance($type, $params))->isInstanceOf($expected_storage);
      } else {
         $this->variable(\Toolbox::getMailServerStorageInstance($type, $params))->isNull();
      }
   }


   protected function mailServerProtocolsHookProvider() {
      // Create valid classes
      eval(<<<CLASS
class PluginTesterFakeProtocol implements Glpi\Mail\Protocol\ProtocolInterface {
   public function setNoValidateCert(bool \$novalidatecert) {}
   public function connect(\$host, \$port = null, \$ssl = false) {}
   public function login(\$user, \$password) {}
}
class PluginTesterFakeStorage extends Laminas\Mail\Storage\Imap {
   public function __construct(\$params) {}
   public function close() {}
}
CLASS
      );

      return [
         // Check that invalid hook result does not alter core protocols specs
         [
            'hook_result'        => 'invalid result',
            'type'               => 'imap',
            'expected_warning'   => 'Invalid value returned by "mail_server_protocols" hook.',
            'expected_protocol'  => 'Laminas\Mail\Protocol\Imap',
            'expected_storage'   => 'Laminas\Mail\Storage\Imap',
         ],
         // Check that hook cannot alter core protocols specs
         [
            'hook_result'        => [
               'imap' => [
                  'label'    => 'Override test',
                  'protocol' => 'SomeClass',
                  'storage'  => 'SomeClass',
               ],
            ],
            'type'               => 'imap',
            'expected_warning'   => 'Protocol "imap" is already defined and cannot be overwritten.',
            'expected_protocol'  => 'Laminas\Mail\Protocol\Imap',
            'expected_storage'   => 'Laminas\Mail\Storage\Imap',
         ],
         // Check that hook cannot alter core protocols specs
         [
            'hook_result'        => [
               'pop' => [
                  'label'    => 'Override test',
                  'protocol' => 'SomeClass',
                  'storage'  => 'SomeClass',
               ],
            ],
            'type'               => 'pop',
            'expected_warning'   => 'Protocol "pop" is already defined and cannot be overwritten.',
            'expected_protocol'  => 'Laminas\Mail\Protocol\Pop3',
            'expected_storage'   => 'Laminas\Mail\Storage\Pop3',
         ],
         // Check that class must exists
         [
            'hook_result'        => [
               'custom-protocol' => [
                  'label'    => 'Invalid class',
                  'protocol' => 'SomeClass1',
                  'storage'  => 'SomeClass2',
               ],
            ],
            'type'               => 'custom-protocol',
            'expected_warning'   => 'Invalid specs for protocol "custom-protocol".',
            'expected_protocol'  => null,
            'expected_storage'   => null,
         ],
         // Check that class must implements expected functions
         [
            'hook_result'        => [
               'custom-protocol' => [
                  'label'    => 'Invalid class',
                  'protocol' => 'Plugin',
                  'storage'  => 'Migration',
               ],
            ],
            'type'               => 'custom-protocol',
            'expected_warning'   => 'Invalid specs for protocol "custom-protocol".',
            'expected_protocol'  => null,
            'expected_storage'   => null,
         ],
         // Check valid case using class names
         [
            'hook_result'        => [
               'custom-protocol' => [
                  'label'    => 'Custom email protocol',
                  'protocol' => 'PluginTesterFakeProtocol',
                  'storage'  => 'PluginTesterFakeStorage',
               ],
            ],
            'type'               => 'custom-protocol',
            'expected_warning'   => null,
            'expected_protocol'  => 'PluginTesterFakeProtocol',
            'expected_storage'   => 'PluginTesterFakeStorage',
         ],
         // Check valid case using callback
         [
            'hook_result'        => [
               'custom-protocol' => [
                  'label'    => 'Custom email protocol',
                  'protocol' => function () { return new \PluginTesterFakeProtocol(); },
                  'storage'  => function (array $params) { return new \PluginTesterFakeStorage($params); },
               ],
            ],
            'type'               => 'custom-protocol',
            'expected_warning'   => null,
            'expected_protocol'  => 'PluginTesterFakeProtocol',
            'expected_storage'   => 'PluginTesterFakeStorage',
         ],
      ];
   }

   /**
    * @dataProvider mailServerProtocolsHookProvider
    */
   public function testGetAdditionnalMailServerProtocols(
      $hook_result,
      string $type,
      ?string $expected_warning,
      ?string $expected_protocol,
      ?string $expected_storage
   ) {
      global $PLUGIN_HOOKS;

      $hooks_backup = $PLUGIN_HOOKS;

      $PLUGIN_HOOKS['mail_server_protocols']['tester'] = function () use ($hook_result) {
         return $hook_result;
      };

      // Get protocol
      $protocol  = null;
      $getProtocol = function () use ($type, &$protocol) {
         $protocol = \Toolbox::getMailServerProtocolInstance($type);
      };
      if ($expected_warning !== null) {
         $this->when($getProtocol)
            ->error()
            ->withType(E_USER_WARNING)
            ->withMessage($expected_warning)
            ->exists();
      } else {
         $getProtocol();
      }

      // Get storage
      $storage   = null;
      $getStorage = function () use ($type, &$storage) {
         $params = [
            'host'     => 'dovecot',
            'user'     => 'testuser',
            'password' => 'applesauce',
         ];
         $storage = \Toolbox::getMailServerStorageInstance($type, $params);
      };
      if ($expected_warning !== null) {
         $this->when($getStorage)
         ->error()
         ->withType(E_USER_WARNING)
         ->withMessage($expected_warning)
         ->exists();
      } else {
         $getStorage();
      }

      $PLUGIN_HOOKS = $hooks_backup;

      if ($expected_protocol !== null) {
         $this->object($protocol)->isInstanceOf($expected_protocol);
      } else {
         $this->variable($protocol)->isNull();
      }

      if ($expected_storage !== null) {
         $this->object($storage)->isInstanceOf($expected_storage);
      } else {
         $this->variable($storage)->isNull();
      }
   }
}
