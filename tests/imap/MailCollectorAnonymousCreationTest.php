<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use CommonITILActor;
use CommonITILObject;
use Glpi\Config\ConfigContainer;
use Glpi\Tests\DbTestCase;
use ITILFollowup;
use Laminas\Mail\Protocol\Imap as ImapProtocol;
use MailCollector;
use NotificationTargetTicket;
use NotImportedEmail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Ticket;
use Ticket_Ticket;
use UserEmail;

/**
 * Collect of emails sent by senders that are unknown from GLPI, with both
 * `use_anonymous_helpdesk` / `use_anonymous_followups` configurations.
 *
 * Messages are pushed by the test itself into a dedicated IMAP folder, so these tests are
 * idempotent and do not interfere with the shared INBOX used by MailCollectorTest::testCollect().
 */
class MailCollectorAnonymousCreationTest extends DbTestCase
{
    private const IMAP_HOST     = 'dovecot';
    private const IMAP_PORT     = 143;
    private const IMAP_LOGIN    = 'testuser';
    private const IMAP_PASSWORD = 'applesauce';

    /** IMAP folder emptied then refilled by each test. */
    private const IMAP_FOLDER = 'anonymous-creation-tests';

    private const ANONYMOUS_SENDER   = 'anonymous-sender@glpi-project.org';
    private const ANONYMOUS_OBSERVER = 'anonymous-observer@glpi-project.org';

    private static ?ImapProtocol $protocol = null;

    /**
     * A message sent by an unknown sender creates a ticket only when anonymous helpdesk is
     * allowed. Otherwise, it is refused with the `USER_UNKNOWN` reason.
     */
    #[TestWith([true], 'anonymous helpdesk allowed')]
    #[TestWith([false], 'anonymous helpdesk denied')]
    public function testTicketCreationFromAnonymousSender(bool $use_anonymous_helpdesk): void
    {
        /** @var ConfigContainer $CFG_GLPI */
        global $CFG_GLPI;

        // --- arrange ---
        $CFG_GLPI['use_anonymous_helpdesk'] = (int) $use_anonymous_helpdesk;

        $initial_tickets_count = countElementsInTable(Ticket::getTable());

        $subject = 'Anonymous ticket creation';
        // CC address is unknown too, to also cover the anonymous observer case
        $this->fillMailbox([
            $this->buildRawMessage(
                [
                    'From'    => 'Anonymous Sender <' . self::ANONYMOUS_SENDER . '>',
                    'To'      => 'GLPI <unittests@glpi-project.org>',
                    'Cc'      => 'Anonymous Observer <' . self::ANONYMOUS_OBSERVER . '>',
                    'Subject' => $subject,
                ],
                'This message is sent by a sender that is unknown from GLPI.'
            ),
        ]);

        // --- act ---
        $this->collect();

        // --- assert ---
        if ($use_anonymous_helpdesk) {
            $this->assertSame(
                $initial_tickets_count + 1,
                countElementsInTable(Ticket::getTable()),
                'Exactly one ticket should have been created.'
            );
            $this->assertSame(0, countElementsInTable(NotImportedEmail::getTable()));

            $ticket = getItemByTypeName(Ticket::class, $subject);

            // requester is an anonymous actor: no `users_id`, email in `alternative_email`
            $requesters = $ticket->getUsers(CommonITILActor::REQUESTER);
            $this->assertCount(1, $requesters);
            $this->assertSame(0, (int) $requesters[0]['users_id']);
            $this->assertSame(self::ANONYMOUS_SENDER, $requesters[0]['alternative_email']);
            $this->assertSame(1, (int) $requesters[0]['use_notification']);

            // collector has `add_cc_to_observer`, so the unknown CC address becomes an observer
            $observers = $ticket->getUsers(CommonITILActor::OBSERVER);
            $this->assertCount(1, $observers);
            $this->assertSame(0, (int) $observers[0]['users_id']);
            $this->assertSame(self::ANONYMOUS_OBSERVER, $observers[0]['alternative_email']);
            $this->assertSame(1, (int) $observers[0]['use_notification']);

            // no user has been created for the anonymous addresses
            $this->assertSame(
                0,
                countElementsInTable(
                    UserEmail::getTable(),
                    ['email' => [self::ANONYMOUS_SENDER, self::ANONYMOUS_OBSERVER]]
                )
            );
        } else {
            $this->assertSame(
                $initial_tickets_count,
                countElementsInTable(Ticket::getTable()),
                'No ticket should have been created.'
            );
            // the only refused mail is ours, and it is refused because the sender is unknown
            $this->assertSame(1, countElementsInTable(NotImportedEmail::getTable()));
            $this->assertSame(
                1,
                countElementsInTable(NotImportedEmail::getTable(), [
                    'from'   => self::ANONYMOUS_SENDER,
                    'reason' => NotImportedEmail::USER_UNKNOWN,
                ])
            );
        }
    }

    /**
     * @return iterable<string, array{
     *     use_anonymous_helpdesk: bool,
     *     use_anonymous_followups: bool,
     *     expected: 'refused'|'followup'|'linked_ticket',
     * }>
     */
    public static function openTicketReplyProvider(): iterable
    {
        yield 'both denied' => [
            'use_anonymous_helpdesk'  => false,
            'use_anonymous_followups' => false,
            'expected'                => 'refused',
        ];
        yield 'anonymous followups allowed' => [
            'use_anonymous_helpdesk'  => false,
            'use_anonymous_followups' => true,
            'expected'                => 'followup',
        ];
        yield 'anonymous helpdesk allowed only' => [
            'use_anonymous_helpdesk'  => true,
            'use_anonymous_followups' => false,
            'expected'                => 'linked_ticket',
        ];
        yield 'both allowed' => [
            'use_anonymous_helpdesk'  => true,
            'use_anonymous_followups' => true,
            'expected'                => 'followup',
        ];
    }

    /**
     * @return iterable<string, array{
     *     use_anonymous_helpdesk: bool,
     *     use_anonymous_followups: bool,
     *     expected: 'refused'|'followup'|'linked_ticket',
     * }>
     */
    public static function closedTicketReplyProvider(): iterable
    {
        yield 'both denied' => [
            'use_anonymous_helpdesk'  => false,
            'use_anonymous_followups' => false,
            'expected'                => 'refused',
        ];
        yield 'anonymous followups allowed' => [
            'use_anonymous_helpdesk'  => false,
            'use_anonymous_followups' => true,
            'expected'                => 'refused',
        ];
        yield 'anonymous helpdesk allowed only' => [
            'use_anonymous_helpdesk'  => true,
            'use_anonymous_followups' => false,
            'expected'                => 'linked_ticket',
        ];
        yield 'both allowed' => [
            'use_anonymous_helpdesk'  => true,
            'use_anonymous_followups' => true,
            'expected'                => 'linked_ticket',
        ];
    }

    /**
     * A reply sent by an unknown sender to an open ticket is either refused, added as a followup,
     * or turned into a new ticket linked to the original one, depending on the anonymous
     * helpdesk / followups configuration.
     */
    #[DataProvider('openTicketReplyProvider')]
    public function testFollowupCreationFromAnonymousSenderOnOpenTicket(
        bool $use_anonymous_helpdesk,
        bool $use_anonymous_followups,
        string $expected
    ): void {
        $this->checkAnonymousReplyOutcome(
            $use_anonymous_helpdesk,
            $use_anonymous_followups,
            false,
            $expected
        );
    }

    /**
     * A closed ticket never accepts a followup, whatever the anonymous followups configuration:
     * the reply is either refused, or turned into a new ticket linked to the closed one.
     */
    #[DataProvider('closedTicketReplyProvider')]
    public function testFollowupCreationFromAnonymousSenderOnClosedTicket(
        bool $use_anonymous_helpdesk,
        bool $use_anonymous_followups,
        string $expected
    ): void {
        $this->checkAnonymousReplyOutcome(
            $use_anonymous_helpdesk,
            $use_anonymous_followups,
            true,
            $expected
        );
    }

    /**
     * Pushes a reply from an unknown sender to `_ticket01`, collects it, and checks the outcome.
     *
     * @param string $expected `refused`, `followup` or `linked_ticket`
     */
    private function checkAnonymousReplyOutcome(
        bool $use_anonymous_helpdesk,
        bool $use_anonymous_followups,
        bool $is_ticket_closed,
        string $expected
    ): void {
        /**
         * @var ConfigContainer $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        // --- arrange ---
        $CFG_GLPI['use_anonymous_helpdesk']  = (int) $use_anonymous_helpdesk;
        $CFG_GLPI['use_anonymous_followups'] = (int) $use_anonymous_followups;

        $ticket = getItemByTypeName(Ticket::class, '_ticket01');
        // close ticket if needed
        if ($is_ticket_closed) {
            // status is not updated through `updateItem()`: allowed transitions are read from the
            // profile status matrix, which is not loaded as these tests do not open a session
            $updated = $DB->update(
                Ticket::getTable(),
                ['status' => CommonITILObject::CLOSED],
                ['id' => $ticket->getID()]
            );
            assert($updated);
            $ticket->getFromDB($ticket->getID());
        }
        // ensure ticket is closed or open
        assert(($ticket->fields['status'] == CommonITILObject::CLOSED) === $is_ticket_closed);
        // ensure no followup currently linked to ticket
        assert(
            countElementsInTable(
                ITILFollowup::getTable(),
                ['itemtype' => Ticket::class, 'items_id' => $ticket->getID()]
            ) === 0
        );

        $initial_tickets_count = countElementsInTable(Ticket::getTable());

        // reply is bound to the ticket through the Message-Id of a GLPI notification
        $message_id = (new NotificationTargetTicket($ticket->fields['entities_id'], 'new', $ticket))
            ->getMessageID();
        assert(str_contains($message_id, sprintf('-Ticket-%d/', $ticket->getID())));

        $subject = 'Re: reply from an anonymous sender';
        $this->fillMailbox([
            $this->buildRawMessage(
                [
                    'From'        => 'Anonymous Sender <' . self::ANONYMOUS_SENDER . '>',
                    'To'          => 'GLPI <unittests@glpi-project.org>',
                    'Subject'     => $subject,
                    'In-Reply-To' => $message_id,
                ],
                'This reply is sent by a sender that is unknown from GLPI.'
            ),
        ]);

        // --- act ---
        $this->collect();

        // --- assert ---
        $followups_count = countElementsInTable(
            ITILFollowup::getTable(),
            ['itemtype' => Ticket::class, 'items_id' => $ticket->getID()]
        );
        $tickets_count = countElementsInTable(Ticket::getTable());

        switch ($expected) {
            case 'refused':
                $this->assertSame(0, $followups_count, 'No followup should have been added.');
                $this->assertSame(
                    $initial_tickets_count,
                    $tickets_count,
                    'No ticket should have been created.'
                );
                $this->assertSame(1, countElementsInTable(NotImportedEmail::getTable()));
                $this->assertSame(
                    1,
                    countElementsInTable(NotImportedEmail::getTable(), [
                        'from'   => self::ANONYMOUS_SENDER,
                        'reason' => NotImportedEmail::USER_UNKNOWN,
                    ])
                );
                break;

            case 'followup':
                $this->assertSame(1, $followups_count, 'A followup should have been added.');
                $this->assertSame(
                    $initial_tickets_count,
                    $tickets_count,
                    'No ticket should have been created.'
                );
                $this->assertSame(0, countElementsInTable(NotImportedEmail::getTable()));
                break;

            case 'linked_ticket':
                // followup is not allowed, so the reply is imported as a new ticket, linked to
                // the ticket it was replying to (see `_linkedto` in MailCollector::buildTicket())
                $this->assertSame(0, $followups_count, 'No followup should have been added.');
                $this->assertSame(
                    $initial_tickets_count + 1,
                    $tickets_count,
                    'Exactly one ticket should have been created.'
                );
                $this->assertSame(0, countElementsInTable(NotImportedEmail::getTable()));
                $links = Ticket_Ticket::getLinkedTo(Ticket::class, $ticket->getID());
                $this->assertCount(1, $links, 'The replied ticket should have a single link.');
                $link = reset($links);
                $this->assertSame(
                    getItemByTypeName(Ticket::class, $subject, true),
                    $link['items_id'],
                    'The created ticket should be linked to the replied ticket.'
                );
                $this->assertSame(Ticket_Ticket::LINK_TO, $link['link']);
                break;

            default:
                $this->fail(sprintf('Unexpected expectation "%s".', $expected));
        }
    }

    /**
     * Empties the dedicated IMAP folder, then pushes the given raw messages into it.
     *
     * @param string[] $raw_messages
     */
    private function fillMailbox(array $raw_messages): void
    {
        $protocol = $this->getImapProtocol();

        if (array_key_exists(self::IMAP_FOLDER, $protocol->listMailbox())) {
            $deleted = $protocol->delete(self::IMAP_FOLDER);
            assert($deleted);
        }
        $created = $protocol->create(self::IMAP_FOLDER);
        assert($created);

        foreach ($raw_messages as $raw_message) {
            $appended = $protocol->append(self::IMAP_FOLDER, $raw_message);
            assert($appended);
        }
    }

    /**
     * Authentication on the test IMAP server takes about 2 seconds, so the connection used to
     * push the messages is shared by all the tests of this class.
     */
    private function getImapProtocol(): ImapProtocol
    {
        if (self::$protocol === null) {
            $protocol = new ImapProtocol();
            $protocol->connect(self::IMAP_HOST, self::IMAP_PORT);
            $logged_in = $protocol->login(self::IMAP_LOGIN, self::IMAP_PASSWORD);
            assert($logged_in);

            self::$protocol = $protocol;
        }

        return self::$protocol;
    }

    /**
     * Creates a mail collector on the dedicated IMAP folder and collects its messages.
     */
    private function collect(): void
    {
        $_SESSION['glpicronuserrunning'] = 'cron_phpunit';

        // collector is not created with `createItem()`, `collect()` needs the instance it is
        // called on to be a different one than the collected mailgate
        $collector    = new MailCollector();
        $collector_id = (int) $collector->add([
            'name'                   => 'anonymous-creation-tests',
            'login'                  => self::IMAP_LOGIN,
            'passwd'                 => self::IMAP_PASSWORD,
            'is_active'              => 1,
            'mail_server'            => self::IMAP_HOST,
            'server_type'            => '/imap',
            'server_port'            => self::IMAP_PORT,
            'server_ssl'             => '',
            'server_cert'            => '/novalidate-cert',
            'server_mailbox'         => self::IMAP_FOLDER,
            'add_to_to_observer'     => 0,
            'add_cc_to_observer'     => 1,
            'collect_only_unread'    => 1,
            'create_user_from_email' => 0,
            'requester_field'        => MailCollector::REQUESTER_FIELD_FROM,
        ]);
        assert($collector_id > 0);

        $collector->collect($collector_id);
    }

    /**
     * Builds a raw RFC 822 message. Only the headers that are relevant to the tested behaviour
     * have to be given, the technical ones are added here.
     *
     * @param array<string, string> $headers
     */
    private function buildRawMessage(array $headers, string $content): string
    {
        $headers += [
            'Date'                      => date(DATE_RFC2822),
            'Message-ID'                => sprintf('<%s@glpi-project.org>', uniqid('anon-test-')),
            'MIME-Version'              => '1.0',
            'Content-Type'              => 'text/plain; charset=utf-8',
            'Content-Transfer-Encoding' => '7bit',
        ];

        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = sprintf('%s: %s', $name, $value);
        }
        $lines[] = '';
        $lines[] = $content;

        return implode("\r\n", $lines);
    }

}
