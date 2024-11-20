<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
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
use Psr\Log\LogLevel;

class MailCollector extends DbTestCase
{
    private $collector;
    private $mailgate_id;

    public function testGetEmpty()
    {
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
                   'add_to_to_observer'   => '',
                   'add_cc_to_observer'   => '',
                   'collect_only_unread'  => '',
                   'create_user_from_email' => '',
                   'last_collect_date'    => '',
               ]);
    }

    protected function subjectProvider()
    {
        return [
            [
                'raw'       => 'This is a subject',
                'expected'  => 'This is a subject'
            ], [
                'raw'       => "With a \ncarriage return",
                'expected'  => "With a \ncarriage return"
            ], [
                'raw'       => 'We have a problem, <strong>URGENT</strong>',
                'expected'  => 'We have a problem, <strong>URGENT</strong>'
            ], [ //dunno why...
                'raw'       => 'Subject with =20 character',
                'expected'  => "Subject with \n character"
            ]
        ];
    }

    /**
     * @dataProvider subjectProvider
     */
    public function testCleanSubject($raw, $expected)
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->cleanSubject($raw))
               ->isIdenticalTo($expected);
    }

    public function testPrepareInput()
    {
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

    public function testCounts()
    {
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

    protected function messageIdHeaderProvider()
    {
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
            // Message-Id generated by GLPI < 10.0.0
            [
                'headers'  => [
                    'message-id' => "GLPI-1.{$time}.{$rand}@{$uname}", // for ticket
                ],
                'expected' => true,
            ],
            [
                'headers'  => [
                    'message-id' => "GLPI-SoftwareLicence-1.{$time}.{$rand}@{$uname}", // with object relation
                ],
                'expected' => true,
            ],
            [
                'headers'  => [
                    'message-id' => "GLPI.{$time}.{$rand}@{$uname}", // without object relation
                ],
                'expected' => true,
            ],
            // Message-Id generated by GLPI >= 10.0.0 < 10.0.7
            [
                'headers'  => [
                    'message-id' => "GLPI_{$uuid}-Ticket-1.{$time}.{$rand}@{$uname}", // for ticket
                ],
                'expected' => true,
            ],
            [
                'headers'  => [
                    'message-id' => "GLPI_{$uuid}-SoftwareLicense-1.{$time}.{$rand}@{$uname}", // with object relation
                ],
                'expected' => true,
            ],
            [
                'headers'  => [
                    'message-id' => "GLPI_{$uuid}.{$time}.{$rand}@{$uname}", // without object relation
                ],
                'expected' => true,
            ],
            [
                'headers'  => [
                    'message-id' => "GLPI_notmyuuid-Ticket-1.{$time}.{$rand}@{$uname}", // with object relation
                ],
                'expected' => false,
            ],
            [
                'headers'  => [
                    'message-id' => "GLPI_notmyuuid.{$time}.{$rand}@{$uname}", // without object relation
                ],
                'expected' => false,
            ],
            // Message-Id generated by GLPI >= 10.0.7
            [
                'headers'  => [
                    'message-id' => $ticket_notif->getMessageID(), // for ticket
                ],
                'expected' => true,
            ],
            [
                'headers'  => [
                    'message-id' => $soft_notif->getMessageID(), // with object relation
                ],
                'expected' => true,
            ],
            [
                'headers'  => [
                    'message-id' => $base_notif->getMessageID(), // without object relation
                ],
                'expected' => true,
            ],
            [
                'headers'  => [
                    'message-id' => "GLPI_notmyuuid-Ticket-1/new.{$time}.{$rand}@{$uname}", // with object relation
                ],
                'expected' => false,
            ],
            [
                'headers'  => [
                    'message-id' => "GLPI_notmyuuid/evt.{$time}.{$rand}@{$uname}", // without object relation
                ],
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider messageIdHeaderProvider
     */
    public function testIsMessageSentByGlpi(array $headers, bool $expected)
    {
        $this->newTestedInstance();

        $message = new Message(
            [
                'headers' => $headers,
                'content' => 'Message contents...',
            ]
        );

        $this->boolean($this->testedInstance->isMessageSentByGlpi($message))->isEqualTo($expected);
    }

    protected function itemReferenceHeaderProvider()
    {
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
            // invalid headers
            [
                'headers'           => [
                    'in-reply-to' => 'notavalidvalue',
                    'references'  => 'donotknow',
                ],
                'expected_itemtype' => null,
                'expected_items_id' => null,
                'accepted'          => true,
            ],
            // Message-Id generated by GLPI < 10.0.0 - found item
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
            // Message-Id generated by GLPI < 10.0.0 - invalid items_id
            [
                'headers'           => [
                    'in-reply-to' => "GLPI-9999999.{$time2}.{$rand2}@{$uname1}",
                ],
                'expected_itemtype' => null,
                'expected_items_id' => null,
                'accepted'          => true,
            ],
            // Message-Id generated by GLPI < 10.0.0 - found item
            [
                'headers'           => [
                    'in-reply-to' => "GLPI-SoftwareLicense-{$soft_id}.{$time1}.{$rand2}@{$uname2}",
                ],
                'expected_itemtype' => SoftwareLicense::class,
                'expected_items_id' => $soft_id,
                'accepted'          => true,
            ],
            // Message-Id generated by GLPI < 10.0.0 - invalid itemtype
            [
                'headers'           => [
                    'references'  => "GLPI-UnknownType-{$soft_id}.{$time2}.{$rand2}@{$uname1}",
                ],
                'expected_itemtype' => null,
                'expected_items_id' => null,
                'accepted'          => true,
            ],
            // Message-Id generated by GLPI < 10.0.0 - invalid items_id
            [
                'headers'           => [
                    'references'  => "GLPI-SoftwareLicense-9999999.{$time1}.{$rand1}@{$uname2}",
                ],
                'expected_itemtype' => null,
                'expected_items_id' => null,
                'accepted'          => true,
            ],
            // Message-Id generated by GLPI >= 10.0.0 < 10.0.7 - found item
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
            // Message-Id generated by GLPI >= 10.0.0 < 10.0.7 - invalid itemtype
            [
                'headers'           => [
                    'references'  => "GLPI_{$uuid}-UnknownType-{$ticket_id}.{$time2}.{$rand2}@{$uname1}",
                ],
                'expected_itemtype' => null,
                'expected_items_id' => null,
                'accepted'          => true,
            ],
            // Message-Id generated by GLPI >= 10.0.0 < 10.0.7 - invalid items_id
            [
                'headers'           => [
                    'references'  => "GLPI_{$uuid}-Ticket-9999999.{$time1}.{$rand1}@{$uname1}",
                ],
                'expected_itemtype' => null,
                'expected_items_id' => null,
                'accepted'          => true,
            ],
            // Message-Id generated by GLPI >= 10.0.0 < 10.0.7 - uuid from another GLPI instance
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
            // References generated by GLPI 10.0.7+ - found item, reference event
            [
                'headers'           => [
                    'references'  => "GLPI_{$uuid}-Ticket-{$ticket_id}/new@{$uname1}",
                ],
                'expected_itemtype' => Ticket::class,
                'expected_items_id' => $ticket_id,
                'accepted'          => true,
            ],
            // References generated by GLPI 10.0.7+ - found item, other event
            [
                'headers'           => [
                    'references'  => "GLPI_{$uuid}-Ticket-{$ticket_id}/update.{$time1}.{$rand1}@{$uname1}",
                ],
                'expected_itemtype' => Ticket::class,
                'expected_items_id' => $ticket_id,
                'accepted'          => true,
            ],
            // References generated by GLPI 10.0.7+ - invalid itemtype
            [
                'headers'           => [
                    'references'  => "GLPI_{$uuid}-UnknownType-{$ticket_id}/update@{$uname1}",
                ],
                'expected_itemtype' => null,
                'expected_items_id' => null,
                'accepted'          => true,
            ],
            // References generated by GLPI 10.0.7+ - invalid items_id
            [
                'headers'           => [
                    'references'  => "GLPI_{$uuid}-Ticket-9999999/new@{$uname1}",
                ],
                'expected_itemtype' => null,
                'expected_items_id' => null,
                'accepted'          => true,
            ],
            // References generated by GLPI 10.0.7+ - uuid from another GLPI instance
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

    private function doConnect()
    {
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
            'add_to_to_observer'    => 1,
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

    public function testCollect()
    {
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

       // Hack to allow documents named "1234567890", "1234567890_2", ... (cf 28-multiple-attachments-no-extension.eml)
        $doctype = new \DocumentType();
        $this->integer(
            $doctype->add([
                'name'   => 'Type test',
                'ext'    => '/^1234567890(_\d+)?$/'
            ])
        )->isGreaterThan(0);

       // Collect all mails
        $this->doConnect();
        $this->collector->maxfetch_emails = 1000; // Be sure to fetch all mails from test suite

        $expected_logged_errors = [
            // 05-empty-from.eml
            'The input is not a valid email address. Use the basic format local-part@hostname' => LogLevel::CRITICAL,
            // 17-malformed-email.eml
            'Header with Name date or date not found' => LogLevel::CRITICAL,
        ];

        $msg = null;
        $this->when(
            function () use (&$msg) {
                $msg = $this->collector->collect($this->mailgate_id);
            }
        )
        ->error()
            ->withType(E_USER_WARNING)
            ->withMessage('Invalid header "X-Invalid-Encoding"')
            ->exists();

        // Check error log and clean it (to prevent test failure, see GLPITestCase::afterTestMethod()).
        foreach ($expected_logged_errors as $error_message => $error_level) {
            $this->hasPhpLogRecordThatContains($error_message, $error_level);
        }

        $total_count                     = count(glob(GLPI_ROOT . '/tests/emails-tests/*.eml'));
        $expected_refused_count          = 3;
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
        foreach ($iterator as $data) {
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
                    'Ticket with multiple to',
                ]
            ],
         // Mails having "normal" user as requester
            [
                'users_id'      => $nuid,
                'actor_type'    => \CommonITILActor::REQUESTER,
                'tickets_names' => [
                    'Test import mail avec emoticons 😃 unicode',
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
                    '25 - Test attachment with invalid chars for OS',
                    '26 Illegal char in body',
                    '28 Multiple attachments no extension',
                    '30 - <GLPI> Special & chars',
                    '31 - HTML message without body',
                    '32 - HTML message with attributes on body tag',
                    '33 - HTML message with unwanted tags inside body tag',
                    '34 - Message with no MessageID header',
                    '35 - Message with some invalid headers',
                    '36 - Microsoft specific code',
                    '37 - Image using application/octet-steam content-type',
                    '38 - E-mail address too long',
                    '39 - Link in content',
                    '40.1 - Empty content (multipart)',
                    '40.2 - Empty content (html)',
                    '40.3 - Empty content (plain text)',
                    '41 - Image src without quotes',
                    '42 - Missing Content Type',
                    '43 - Korean encoding issue',
                ]
            ],
            // Mails having "normal" user as observer
            [
                'users_id'      => $nuid,
                'actor_type'    => \CommonITILActor::OBSERVER,
                'tickets_names' => [
                    'Ticket with observer',     // add_cc_to_observer = true
                    'Ticket with multiple to',  // add_to_to_observer = true
                ]
            ],
        ];

       // Tickets on which content should be checked (key is ticket name)
        $tickets_contents = [
         // Plain text on mono-part email
            'PHP fatal error' => <<<PLAINTEXT
On some cases, doing the following:
# blahblah

Will cause a PHP fatal error:
# blahblah

Best regards,
PLAINTEXT,
         // HTML on multi-part email
            'Re: [GLPI #0038927] Update - Issues with new Windows 10 machine' => <<<HTML
<p>This message have reply to header, requester should be get from this header.</p>
HTML,
            'Mono-part HTML message' => <<<HTML
<p>This HTML message does not use <strong>"multipart/alternative"</strong> format.</p>
HTML,
            '26 Illegal char in body' => <<<PLAINTEXT
这是很坏的Minus C Blabla
PLAINTEXT,
            '28 Multiple attachments no extension' => <<<HTML
<div>&nbsp;</div><div>Test</div><div>&nbsp;</div>
HTML,
            '31 - HTML message without body' => <<<HTML
This HTML message does not have a <i>body</i> tag.
HTML,
            '32 - HTML message with attributes on body tag' => <<<HTML
This HTML message has an attribut on its <i>body</i> tag.
HTML,
            '33 - HTML message with unwanted tags inside body tag' => <<<HTML
<p>This HTML message constains style, scripts and meta tags.</p>
    
    <p>It also contains text,</p>
    
    <p>between</p>
    
    <p>these unwanted</p>
    
    <p>tags.</p>
HTML,
            '35 - Message with some invalid headers' => <<<PLAINTEXT
This message has some invalid headers, but it should collected anyways.
PLAINTEXT,
            '36 - Microsoft specific code' => <<<HTML
<div class=WordSection1>
      <p class=MsoNormal>
        <span style='font-family:Roboto'>First Line</span>
      </p>
      <p class=MsoNormal>
        <span style='font-family:Roboto'></span>
      </p>
      <p class=MsoListParagraph style='text-indent:-18.0pt;mso-list:l0 level1 lfo1'>
        <span style='font-family:Roboto'><span style='mso-list:Ignore'>-<span style='font:7.0pt "Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span>
        <span style='font-family:Roboto'>First hyphen</span>
      </p>
      <p class=MsoListParagraph style='text-indent:-18.0pt;mso-list:l0 level1 lfo1'>
        <span style='font-family:Roboto'><span style='mso-list:Ignore'>-<span style='font:7.0pt "Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span>
        <span style='font-family:Roboto'>Second hyphen</span>
      </p>
      <p class=MsoListParagraph style='text-indent:-18.0pt;mso-list:l0 level1 lfo1'>
        <span style='font-family:Roboto'><span style='mso-list:Ignore'>-<span style='font:7.0pt "Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span>
        <span style='font-family:Roboto'>Third hyphen</span>
      </p>
      <p class=MsoListParagraph style='margin-left:72.0pt;text-indent:-18.0pt;mso-list:l0 level2 lfo1'>
        <span style='font-family:"Courier New"'><span style='mso-list:Ignore'>o<span style='font:7.0pt "Times New Roman"'>&nbsp;&nbsp; </span></span></span>
        <span style='font-family:Roboto'>Next tab hyphen</span>
      </p>
      <p class=MsoListParagraph style='margin-left:108.0pt;text-indent:-18.0pt;mso-list:l0 level3 lfo1'>
        <span style='font-family:Wingdings'><span style='mso-list:Ignore'>▪<span style='font:7.0pt "Times New Roman"'>&nbsp; </span></span></span>
        <span style='font-family:Roboto'>Next next tab hypen</span>
      </p>
      <p class=MsoNormal>
        <span style='font-family:Roboto'></span>
      </p>
      
    </div>
HTML,
            '39 - Link in content' => <<<PLAINTEXT
This HTML message has a link  <https://glpi-project.org>.
PLAINTEXT,
            '40.1 - Empty content (multipart)' => '',
            '40.2 - Empty content (html)' => '',
            '40.3 - Empty content (plain text)' => '',
            '42 - Missing Content Type' => <<<PLAINTEXT
Notifications in this message: 3
================================

15:23:03 UPS Notification from rtr.XXXX - Tue, 28 Nov 2023 15:23:03 +0100

Communications with UPS SC1500I lost
15:23:08 UPS Notification from rtr.XXXX - Tue, 28 Nov 2023 15:23:08 +0100

UPS SC1500I is unavailable
15:23:13 UPS Notification from rtr.XXXX - Tue, 28 Nov 2023 15:23:13 +0100

Communications with UPS SC1500I established
PLAINTEXT,
            '43 - Korean encoding issue' => <<<PLAINTEXT
<div class="elementToProof" style="text-align: left; text-indent: 0px; background-color: rgb(255, 255, 255); margin: 0px; font-family: Aptos, Aptos_EmbeddedFont, Aptos_MSFontService, Calibri, Helvetica, sans-serif; font-size: 12pt; color: rgb(0, 0, 0);">
다리는 계절입니다. 따뜻한 날씨와 함께 바다에서 수영하거나 산으로 여행을 떠날 수 있습니다. 또한 친구들과 바비큐 파티를</div>
<div style="text-align: left; text-indent: 0px; background-color: rgb(255, 255, 255); margin: 0px; font-family: Aptos, Aptos_EmbeddedFont, Aptos_MSFontService, Calibri, Helvetica, sans-serif; font-size: 12pt; color: rgb(0, 0, 0);">
뜻한 날씨와&nbsp;</div>
<div style="margin: 0px; font-family: Aptos, Aptos_EmbeddedFont, Aptos_MSFontService, Calibri, Helvetica, sans-serif; font-size: 12pt; color: rgb(0, 0, 0);">
<br>
</div>
<div class="elementToProof" style="font-family: Aptos, Aptos_EmbeddedFont, Aptos_MSFontService, Calibri, Helvetica, sans-serif; font-size: 12pt; color: rgb(0, 0, 0);">
<br>
</div>
PLAINTEXT,
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
            foreach ($iterator as $data) {
                $name = $data['name'];

                if (array_key_exists($name, $tickets_contents)) {
                    $this->string($data['content'])->isEqualTo($tickets_contents[$name]);
                }

                $this->string($data['content'])->notContains('cid:'); // check that image were correctly imported

                $names[] = $name;
            }

            sort($names);
            sort($actor_specs['tickets_names']);
            $this->array($names)->isIdenticalTo($actor_specs['tickets_names']);
        }

       // Check creation of expected documents
        $expected_docs = [
            '00-logoteclib.png' => 'image/png',
            '01-screenshot-2018-4-12-observatoire-france-tres-haut-debit.png' => 'image/png',
            '01-test.JPG' => 'image/jpeg',
            '15-image001.png' => 'image/png',
            '18-blank.gif' => 'image/gif',
            '19-secl-chas.gif' => 'image/gif',
            '20-special-chars.gif' => 'image/gif',
            '24.1-zhang-wen-jian-ming-jiang-dao-zhi-nei-rong-chu-zhi-biao-tou-zhong-de-lian-xu-xing.txt' => 'text/plain',
            '24.2-zhong-guo-zi-fu.txt' => 'text/plain',
            '25-new-text-document.txt' => 'text/plain',
            '1234567890' => 'text/plain',
            '1234567890_2' => 'text/plain',
            '1234567890_3' => 'text/plain',
            '37-red-dot.png' => 'image/png',
            '41-blue-dot.png' => 'image/png',
        ];

        $iterator = $DB->request(
            [
                'SELECT' => ['d.filename', 'd.mime'],
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
        foreach ($iterator as $data) {
            $filenames[$data['filename']] = $data['mime'];
        }
        ksort($filenames);
        ksort($expected_docs);
        $this->array($filenames)->isIdenticalTo($expected_docs);

        $this->integer(count($iterator))->isIdenticalTo(count($expected_docs));

       // Check creation of expected followups
        $expected_followups = [
            [
                'items_id' => 100,
                'users_id' => $tuid,
                'content'  => 'This is a reply that references Ticket 100 in In-Reply-To header (old format).' . "\r\n" . 'It should be added as followup.',
            ],
            [
                'items_id' => 100,
                'users_id' => $tuid,
                'content'  => 'This is a reply that references Ticket 100 in References header (old format).' . "\r\n" . 'It should be added as followup.',
            ],
            [
                'items_id' => 101,
                'users_id' => $tuid,
                'content'  => 'This is a reply that references Ticket 101 in its subject.' . "\r\n" . 'It should be added as followup.',
            ],
            [
                'items_id' => 100,
                'users_id' => $tuid,
                'content'  => 'This is a reply that references Ticket 100 in In-Reply-To header (new format).' . "\r\n" . 'It should be added as followup.',
            ],
            [
                'items_id' => 100,
                'users_id' => $tuid,
                'content'  => 'This is a reply that references Ticket 100 in References header (new format).' . "\r\n" . 'It should be added as followup.',
            ],
            [
                // 29.1-ticket-followup-above-header-and-under-footer-lines.eml
                'items_id' => 101,
                'users_id' => $tuid,
                'content'  => 'My followup starts above header line...' . "\r\n" . '...and finishes under footer line.',
            ],
            [
                // 29.2-ticket-followup-above-header-line.eml
                'items_id' => 101,
                'users_id' => $tuid,
                'content'  => 'My followup is located above header line.',
            ],
            [
                // 29.3-ticket-followup-under-footer-line.eml
                'items_id' => 101,
                'users_id' => $tuid,
                'content'  => 'My followup is located under footer line.',
            ],
            [
                // 29.4-ticket-followup-above-header-line-without-footer-line.eml
                'items_id' => 101,
                'users_id' => $tuid,
                'content'  => 'My followup is located above header line, and there is no footer line.',
            ],
            [
                // 29.5-ticket-followup-under-footer-line-without-header-line.eml
                'items_id' => 101,
                'users_id' => $tuid,
                'content'  => 'My followup is located under footer line, and there is no header line.',
            ],
            [
                // 29.6-ticket-followup-under-header-line-without-footer-line.eml
                'items_id' => 101,
                'users_id' => $tuid,
                'content'  => 'My followup starts under header line...' . "\r\n\r\n"
                    . 'HERE IS THE INITIAL NOTIFICATION CONTENT' . "\r\n\r\n"
                    . '...and there is no footer line.',
            ],
            [
                // 29.7-ticket-followup-under-header-line-without-footer-line.eml
                'items_id' => 101,
                'users_id' => $tuid,
                'content'  => 'My followup starts above footer line...' . "\r\n\r\n"
                    . 'HERE IS THE INITIAL NOTIFICATION CONTENT' . "\r\n\r\n"
                    . '...and there is no header line.',
            ],
        ];

        foreach ($expected_followups as $expected_followup) {
            $this->integer(countElementsInTable(ITILFollowup::getTable(), $expected_followup))
                ->isEqualTo(1, sprintf("Followup not found:\n> %s", $expected_followup['content']));
        }
    }

    protected function mailServerProtocolsProvider()
    {
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


    protected function mailServerProtocolsHookProvider()
    {
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
                        'protocol' => function () {
                            return new \PluginTesterFakeProtocol();
                        },
                        'storage'  => function (array $params) {
                            return new \PluginTesterFakeStorage($params);
                        },
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
