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

use Change;
use CommonITILActor;
use DbTestCase;
use Glpi\Toolbox\Sanitizer;
use ITILFollowup as CoreITILFollowup;
use Problem;
use QueryExpression;
use Search;
use Ticket;
use Ticket_User;
use User;

/* Test for inc/itilfollowup.class.php */

class ITILFollowupTest extends DbTestCase
{
    /**
     * Create a new ITILObject and return its id or the object
     *
     * @param string $itemtype ITILObject parent to test followups on
     * @param bool   $as_object
     * @return integer|\CommonDBTM
     */
    private function getNewITILObject($itemtype, bool $as_object = false)
    {
       //create reference ITILObject
        $itilobject = new $itemtype();
        $this->assertGreaterThan(
            0,
            (int)$itilobject->add([
                'name'         => "$itemtype title",
                'description'  => 'a description',
                'content'      => '',
                'entities_id'  => getItemByTypeName('Entity', '_test_root_entity', true),
            ])
        );

        $this->assertFalse($itilobject->isNewItem());
        $this->assertTrue($itilobject->can($itilobject->getID(), \READ));
        return $as_object ? $itilobject : (int)$itilobject->getID();
    }

    public function testACL()
    {
        $this->login();

        $ticketId = $this->getNewITILObject('Ticket');
        $fup      = new \ITILFollowup();
        $tmp      = ['itemtype' => 'Ticket', 'items_id' => $ticketId];
        $this->assertTrue($fup->can(-1, \CREATE, $tmp));

        $fup_id = $fup->add([
            'content'      => "my followup",
            'itemtype'   => 'Ticket',
            'items_id'   => $ticketId
        ]);
        $this->assertGreaterThan(0, $fup_id);
        $this->assertTrue($fup->canViewItem());
        $this->assertTrue($fup->canUpdateItem());
        $this->assertTrue($fup->canPurgeItem());

        $changeId = $this->getNewITILObject('Change');
        $fup      = new \ITILFollowup();
        $tmp      = ['itemtype' => 'Change', 'items_id' => $changeId];
        $this->assertTrue($fup->can(-1, \CREATE, $tmp));

        $fup_id = $fup->add([
            'content'      => "my followup",
            'itemtype'   => 'Change',
            'items_id'   => $changeId
        ]);
        $this->assertGreaterThan(0, $fup_id);
        $this->assertTrue($fup->canViewItem());
        $this->assertTrue($fup->canUpdateItem());
        $this->assertTrue($fup->canPurgeItem());

        $problemId = $this->getNewITILObject('Problem');
        $fup      = new \ITILFollowup();
        $tmp      = ['itemtype' => 'Problem', 'items_id' => $problemId];
        $this->assertTrue($fup->can(-1, \CREATE, $tmp));

        $fup_id = $fup->add([
            'content'      => "my followup",
            'itemtype'   => 'Problem',
            'items_id'   => $problemId
        ]);
        $this->assertGreaterThan(0, $fup_id);
        $this->assertTrue($fup->canViewItem());
        $this->assertTrue($fup->canUpdateItem());
        $this->assertTrue($fup->canPurgeItem());
    }

    public function testUpdateAndDelete()
    {
        $this->login();

        $ticketId = $this->getNewITILObject('Ticket');
        $fup      = new \ITILFollowup();

        $fup_id = $fup->add([
            'content'      => "my followup",
            'itemtype'   => 'Ticket',
            'items_id'   => $ticketId
        ]);
        $this->assertGreaterThan(0, (int)$fup_id);

        $this->assertTrue(
            $fup->update([
                'id'         => $fup_id,
                'content'    => "my followup updated",
                'itemtype'   => 'Ticket',
                'items_id'   => $ticketId
            ])
        );

        $this->assertTrue(
            $fup->getFromDB($fup_id)
        );
        $this->assertEquals('my followup updated', $fup->fields['content']);

        $this->assertTrue(
            $fup->delete([
                'id'  => $fup_id
            ])
        );
        $this->assertFalse($fup->getFromDB($fup_id));

        $changeId = $this->getNewITILObject('Change');
        $fup      = new \ITILFollowup();

        $fup_id = $fup->add([
            'content'      => "my followup",
            'itemtype'   => 'Change',
            'items_id'   => $changeId
        ]);
        $this->assertGreaterThan(0, (int)$fup_id);

        $this->assertTrue(
            $fup->update([
                'id'         => $fup_id,
                'content'    => "my followup updated",
                'itemtype'   => 'Change',
                'items_id'   => $changeId
            ])
        );

        $this->assertTrue(
            $fup->getFromDB($fup_id)
        );
        $this->assertEquals('my followup updated', $fup->fields['content']);

        $this->assertTrue(
            $fup->delete([
                'id'  => $fup_id
            ])
        );
        $this->assertFalse($fup->getFromDB($fup_id));

        $problemId = $this->getNewITILObject('Problem');
        $fup      = new \ITILFollowup();

        $fup_id = $fup->add([
            'content'      => "my followup",
            'itemtype'   => 'Problem',
            'items_id'   => $problemId
        ]);
        $this->assertGreaterThan(0, (int)$fup_id);

        $this->assertTrue(
            $fup->update([
                'id'         => $fup_id,
                'content'    => "my followup updated",
                'itemtype'   => 'Problem',
                'items_id'   => $problemId
            ])
        );

        $this->assertTrue(
            $fup->getFromDB($fup_id)
        );
        $this->assertEquals('my followup updated', $fup->fields['content']);

        $this->assertTrue(
            $fup->delete([
                'id'  => $fup_id
            ])
        );
        $this->assertFalse($fup->getFromDB($fup_id));
    }

    /**
     * Test _do_not_compute_takeintoaccount flag
     */
    public function testDoNotComputeTakeintoaccount()
    {
        $this->login();

        $ticket = new \Ticket();
        $oldConf = [
            'glpiset_default_tech'      => $_SESSION['glpiset_default_tech'],
            'glpiset_default_requester' => $_SESSION['glpiset_default_requester'],
        ];

        $_SESSION['glpiset_default_tech'] = 0;
        $_SESSION['glpiset_default_requester'] = 0;

        // Normal behavior, no flag specified
        $ticketID = $this->getNewITILObject('Ticket');
        $this->assertGreaterThan(0, $ticketID);

        $ITILFollowUp = new \ITILFollowup();
        $this->assertGreaterThan(
            0,
            $ITILFollowUp->add([
                'date'                            => $_SESSION['glpi_currenttime'],
                'users_id'                        => \Session::getLoginUserID(),
                'content'                         => "Functional test",
                'items_id'                        => $ticketID,
                'itemtype'                        => \Ticket::class,
            ])
        );

        $this->assertTrue($ticket->getFromDB($ticketID));
        $this->assertGreaterThan(0, (int) $ticket->fields['takeintoaccount_delay_stat']);
        $this->assertEquals($_SESSION['glpi_currenttime'], $ticket->fields['takeintoaccountdate']);

        // Now using the _do_not_compute_takeintoaccount flag
        $ticketID = $this->getNewITILObject('Ticket');
        $this->assertGreaterThan(0, $ticketID);

        $ITILFollowUp = new \ITILFollowup();
        $this->assertGreaterThan(
            0,
            $ITILFollowUp->add([
                'date'                            => $_SESSION['glpi_currenttime'],
                'users_id'                        => \Session::getLoginUserID(),
                'content'                         => "Functional test",
                '_do_not_compute_takeintoaccount' => true,
                'items_id'                        => $ticketID,
                'itemtype'                        => \Ticket::class,
            ])
        );

        $this->assertTrue($ticket->getFromDB($ticketID));
        $this->assertEquals(0, (int) $ticket->fields['takeintoaccount_delay_stat']);
        $this->assertNull($ticket->fields['takeintoaccountdate']);

        // Reset conf
        $_SESSION['glpiset_default_tech']      = $oldConf['glpiset_default_tech'];
        $_SESSION['glpiset_default_requester'] = $oldConf['glpiset_default_requester'];
    }

    public static function testIsFromSupportAgentProvider()
    {
        return [
            [
            // Case 1: user is not an actor of the ticket
                "roles"    => [],
                "profile" => "Technician",
                "expected" => true,
            ],
            [
            // Case 2: user is a requester
                "roles"    => [CommonITILActor::REQUESTER],
                "profile" => "Technician",
                "expected" => false,
            ],
            [
            // Case 3: user is an observer with a central profile
                "roles"    => [CommonITILActor::OBSERVER],
                "profile" => "Technician",
                "expected" => true,
            ],
            [
            // Case 3b: user is an observer without central profiles
                "roles"    => [CommonITILActor::OBSERVER],
                "profile" => "Self-Service",
                "expected" => false,
            ],
            [
            // Case 4: user is assigned
                "roles"    => [CommonITILActor::ASSIGN],
                "profile" => "Technician",
                "expected" => true,
            ],
            [
            // Case 5: user is observer and assigned
                "roles"    => [
                    CommonITILActor::OBSERVER,
                    CommonITILActor::ASSIGN,
                ],
                "profile" => "Technician",
                "expected" => true,
            ],
        ];
    }

    /**
     * @dataProvider testIsFromSupportAgentProvider
     */
    public function testIsFromSupportAgent(
        array $roles,
        string $profile,
        bool $expected
    ) {
        global $CFG_GLPI, $DB;

        // Disable notifications
        $old_conf = $CFG_GLPI['use_notifications'];
        $CFG_GLPI['use_notifications'] = false;

        $this->login();

        // Insert a ticket;
        $ticket = new Ticket();
        $ticket_id = $ticket->add([
            "name"    => "testIsFromSupportAgent",
            "content" => "testIsFromSupportAgent",
        ]);
        $this->assertGreaterThan(0, $ticket_id);

        // Create test user
        $rand = mt_rand();
        $user = new User();
        $users_id = $user->add([
            'name' => "testIsFromSupportAgent$rand",
            'password' => 'testIsFromSupportAgent',
            'password2' => 'testIsFromSupportAgent',
            '_profiles_id' => getItemByTypeName('Profile', $profile, true),
            '_entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $this->assertGreaterThan(0, $users_id);

        // Insert a followup
        $fup = new CoreITILFollowup();
        $fup_id = $fup->add([
            'content'  => "testIsFromSupportAgent",
            'users_id' => $users_id,
            'items_id' => $ticket_id,
            'itemtype' => "Ticket",
        ]);
        $this->assertGreaterThan(0, $fup_id);
        $this->assertTrue($fup->getFromDB($fup_id));

        // Remove any roles that may have been set after insert
        $this->assertTrue($DB->delete(Ticket_User::getTable(), ['tickets_id' => $ticket_id]));
        $this->assertCount(0, $ticket->getITILActors());

        // Insert roles
        $tuser = new Ticket_User();
        foreach ($roles as $role) {
            $this->assertGreaterThan(
                0,
                $tuser->add([
                    'tickets_id' => $ticket_id,
                    'users_id'   => $users_id,
                    'type'       => $role,
                ])
            );
        }

        // Execute test
        $result = $fup->isFromSupportAgent();
        $this->assertEquals($expected, $result);

        // Reset conf
        $CFG_GLPI['use_notifications'] = $old_conf;
    }

    public function testScreenshotConvertedIntoDocument()
    {

        $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

        // Test uploads for item creation
        $ticket = new \Ticket();
        $ticket->add([
            'name' => $this->getUniqueString(),
            'content' => 'test',
        ]);
        $this->assertFalse($ticket->isNewItem());

        $base64Image = base64_encode(file_get_contents(FIXTURE_DIR . '/uploads/foo.png'));
        $user = getItemByTypeName('User', TU_USER, true);
        $filename = '5e5e92ffd9bd91.11111111image_paste22222222.png';
        $instance = new \ITILFollowup();
        $input = [
            'users_id' => $user,
            'items_id' => $ticket->getID(),
            'itemtype' => 'Ticket',
            'name'    => 'a followup',
            'content' => Sanitizer::sanitize(<<<HTML
<p>Test with a ' (add)</p>
<p><img id="3e29dffe-0237ea21-5e5e7034b1d1a1.00000000" src="data:image/png;base64,{$base64Image}" width="12" height="12"></p>
HTML
            ),
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1d1a1.00000000',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.11111111',
            ]
        ];
        copy(FIXTURE_DIR . '/uploads/foo.png', GLPI_TMP_DIR . '/' . $filename);

        $this->assertGreaterThan(0, $instance->add($input));
        $this->assertFalse($instance->isNewItem());
        $this->assertTrue($instance->getFromDB($instance->getId()));
        $expected = 'a href="/front/document.send.php?docid=';
        $this->assertStringContainsString($expected, $instance->fields['content']);

        // Test uploads for item update
        $base64Image = base64_encode(file_get_contents(FIXTURE_DIR . '/uploads/bar.png'));
        $filename = '5e5e92ffd9bd91.44444444image_paste55555555.png';
        copy(FIXTURE_DIR . '/uploads/bar.png', GLPI_TMP_DIR . '/' . $filename);
        $success = $instance->update([
            'id' => $instance->getID(),
            'content' => Sanitizer::sanitize(<<<HTML
<p>Test with a ' (update)</p>
<p><img id="3e29dffe-0237ea21-5e5e7034b1d1a1.33333333" src="data:image/png;base64,{$base64Image}" width="12" height="12"></p>
HTML
            ),
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1d1a1.33333333',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.44444444',
            ]
        ]);
        $this->assertTrue($success);
        $this->assertTrue($instance->getFromDB($instance->getId()));
        $expected = 'a href="/front/document.send.php?docid=';
        $this->assertStringContainsString($expected, $instance->fields['content']);
    }

    public function testUploadDocuments()
    {

        $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

        // Test uploads for item creation
        $ticket = new \Ticket();
        $ticket->add([
            'name' => $this->getUniqueString(),
            'content' => 'test',
        ]);
        $this->assertFalse($ticket->isNewItem());

        $user = getItemByTypeName('User', TU_USER, true);
        // Test uploads for item creation
        $filename = '5e5e92ffd9bd91.11111111' . 'foo.txt';
        $instance = new \ITILFollowup();
        $input = [
            'users_id' => $user,
            'items_id' => $ticket->getID(),
            'itemtype' => 'Ticket',
            'name'    => 'a followup',
            'content' => 'testUploadDocuments',
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1ffff.00000000',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.11111111',
            ]
        ];
        copy(FIXTURE_DIR . '/uploads/foo.txt', GLPI_TMP_DIR . '/' . $filename);
        $instance->add($input);
        $this->assertFalse($instance->isNewItem());
        $this->assertStringContainsString('testUploadDocuments', $instance->fields['content']);
        $count = (new \DBUtils())->countElementsInTable(\Document_Item::getTable(), [
            'itemtype' => 'ITILFollowup',
            'items_id' => $instance->getID(),
        ]);
        $this->assertEquals(1, $count);

        // Test uploads for item update (adds a 2nd document)
        $filename = '5e5e92ffd9bd91.44444444bar.txt';
        copy(FIXTURE_DIR . '/uploads/bar.png', GLPI_TMP_DIR . '/' . $filename);
        $success = $instance->update([
            'id' => $instance->getID(),
            'content' => 'update testUploadDocuments',
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1ffff.33333333',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.44444444',
            ]
        ]);
        $this->assertTrue($success);
        $this->assertStringContainsString('update testUploadDocuments', $instance->fields['content']);
        $count = (new \DBUtils())->countElementsInTable(\Document_Item::getTable(), [
            'itemtype' => 'ITILFollowup',
            'items_id' => $instance->getID(),
        ]);
        $this->assertEquals(2, $count);
    }

    public function testAddFromTemplate()
    {
        $this->login();

        $ticket = $this->getNewITILObject('Ticket', true);
        $template = new \ITILFollowupTemplate();
        $templates_id = $template->add([
            'name'               => 'test template',
            'content'            => 'test template',
            'is_private'         => 1,
        ]);
        $this->assertGreaterThan(0, $templates_id);
        $fup = new \ITILFollowup();
        $fups_id = $fup->add([
            '_itilfollowuptemplates_id' => $templates_id,
            'itemtype'                  => 'Ticket',
            'items_id'                  => $ticket->fields['id'],
        ]);
        $this->assertGreaterThan(0, $fups_id);

        $this->assertEquals(Sanitizer::sanitize('<p>test template</p>', false), $fup->fields['content']);
        $this->assertEquals(1, $fup->fields['is_private']);

        $fups_id = $fup->add([
            '_itilfollowuptemplates_id' => $templates_id,
            'itemtype'                  => 'Ticket',
            'items_id'                  => $ticket->fields['id'],
            'content'                   => 'test template2',
            'is_private'                => 0,
        ]);
        $this->assertGreaterThan(0, $fups_id);

        $this->assertEquals('test template2', $fup->fields['content']);
        $this->assertEquals(0, $fup->fields['is_private']);
    }

    /**
     * Data provider for testIsParentAlreadyLoaded
     *
     * @return iterable
     */
    protected function testIsParentAlreadyLoadedProvider(): iterable
    {
        $this->login();
        $entity = getItemByTypeName('Entity', '_test_root_entity', true);

        // Obviously false, no data was loaded
        $followup = new CoreITILFollowup();
        yield [$followup, false];

        // Create two tickets and a followup
        $parent_1 = $this->createItem('Ticket', [
            'entities_id' => $entity,
            'name'        => 'Test ticket 1',
            'content'     => '',
        ]);
        $parent_2 = $this->createItem('Ticket', [
            'entities_id' => $entity,
            'name'        => 'Test ticket 2',
            'content'     => '',
        ]);
        $parent_3 = $this->createItem('Problem', [
            'entities_id' => $entity,
            'name'        => 'Test problem 1',
            'content'     => '',
        ]);
        $followup = $this->createItem('ITILFollowup', [
            'itemtype' => Ticket::getType(),
            'items_id' => $parent_1->getID(),
            'content'  => 'Test followup',
        ]);

        // Correct parent
        $followup->setParentItem($parent_1);
        yield [$followup, true];

        // Invadid parent (wrong id)
        $followup->setParentItem($parent_2);
        yield [$followup, false];

        // Invadid parent (wrong itemtype)
        $followup->setParentItem($parent_3);
        yield [$followup, false];
    }

    /**
     * Tests for the isParentAlreadyLoaded method
     *
     * @return void
     */
    public function testIsParentAlreadyLoaded(): void
    {
        $provider = $this->testIsParentAlreadyLoadedProvider();
        foreach ($provider as $row) {
            [$followup, $is_parent_loaded] = $row;
            $this->assertEquals(
                $is_parent_loaded,
                $this->callPrivateMethod($followup, 'isParentAlreadyLoaded')
            );
        }
    }

    public function testAddDefaultWhereTakeEntitiesIntoAccount(): void
    {
        $this->login();
        $this->setEntity('_test_child_2', false);

        // Add followups in an entity our user can see
        $number_of_visible_followups = $this->countVisibleFollowupsForLoggedInUser();
        $this->createFollowupInEntityForType('_test_child_2', Ticket::class);
        $this->createFollowupInEntityForType('_test_child_2', Problem::class);
        $this->createFollowupInEntityForType('_test_child_2', Change::class);
        $this->assertEquals(
            $number_of_visible_followups + 3, // 3 new followup found
            $this->countVisibleFollowupsForLoggedInUser()
        );

        // Add followups in a visible that our user can't see
        $number_of_visible_followups = $this->countVisibleFollowupsForLoggedInUser();
        $this->createFollowupInEntityForType('_test_root_entity', Ticket::class);
        $this->createFollowupInEntityForType('_test_root_entity', Problem::class);
        $this->createFollowupInEntityForType('_test_root_entity', Change::class);
        $this->assertEquals(
            $number_of_visible_followups, // No new followups found
            $this->countVisibleFollowupsForLoggedInUser()
        );
    }

    private function countVisibleFollowupsForLoggedInUser(): int
    {
        /** @var \DBMysql $DB */
        global $DB;

        $already_linked_tables = [];
        $results = $DB->request([
            'COUNT' => 'number_of_followups',
            'FROM' => CoreITILFollowup::getTable(),
            'JOIN' => [
                new QueryExpression(
                    Search::addDefaultJoin(
                        CoreITILFollowup::class,
                        CoreITILFollowup::getTable(),
                        $already_linked_tables
                    )
                )
            ],
            'WHERE' => new QueryExpression(
                Search::addDefaultWhere(CoreITILFollowup::class)
            ),
        ]);

        return (int) iterator_to_array($results)[0]['number_of_followups'];
    }

    private function createFollowupInEntityForType(
        string $entity_name,
        string $itemtype
    ): void {
        $itil = $this->createItem($itemtype, [
            'entities_id' => getItemByTypeName('Entity', $entity_name, true),
            'name'        => 'Test ticket',
            'content'     => '',
        ]);
        $this->createItem(CoreITILFollowup::class, [
            'itemtype' => $itemtype,
            'items_id' => $itil->getID(),
            'content'  => 'Test followup',
        ]);
    }
}
