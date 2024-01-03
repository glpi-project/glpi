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

use CommonITILActor;
use DbTestCase;
use Glpi\Toolbox\Sanitizer;
use ITILFollowup as CoreITILFollowup;
use Ticket;
use Ticket_User;
use User;

/* Test for inc/itilfollowup.class.php */

class ITILFollowup extends DbTestCase
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
        $this->integer((int)$itilobject->add([
            'name'         => "$itemtype title",
            'description'  => 'a description',
            'content'      => '',
            'entities_id'  => getItemByTypeName('Entity', '_test_root_entity', true),
        ]))->isGreaterThan(0);

        $this->boolean($itilobject->isNewItem())->isFalse();
        $this->boolean($itilobject->can($itilobject->getID(), \READ))->isTrue();
        return $as_object ? $itilobject : (int)$itilobject->getID();
    }

    public function testACL()
    {
        $this->login();

        $ticketId = $this->getNewITILObject('Ticket');
        $fup      = new \ITILFollowup();
        $tmp      = ['itemtype' => 'Ticket', 'items_id' => $ticketId];
        $this->boolean((bool) $fup->can(-1, \CREATE, $tmp))->isTrue();

        $fup_id = $fup->add([
            'content'      => "my followup",
            'itemtype'   => 'Ticket',
            'items_id'   => $ticketId
        ]);
        $this->integer($fup_id)->isGreaterThan(0);
        $this->boolean((bool) $fup->canViewItem())->isTrue();
        $this->boolean((bool) $fup->canUpdateItem())->isTrue();
        $this->boolean((bool) $fup->canPurgeItem())->isTrue();

        $changeId = $this->getNewITILObject('Change');
        $fup      = new \ITILFollowup();
        $tmp      = ['itemtype' => 'Change', 'items_id' => $changeId];
        $this->boolean((bool) $fup->can(-1, \CREATE, $tmp))->isTrue();

        $fup_id = $fup->add([
            'content'      => "my followup",
            'itemtype'   => 'Change',
            'items_id'   => $changeId
        ]);
        $this->integer($fup_id)->isGreaterThan(0);
        $this->boolean((bool) $fup->canViewItem())->isTrue();
        $this->boolean((bool) $fup->canUpdateItem())->isTrue();
        $this->boolean((bool) $fup->canPurgeItem())->isTrue();

        $problemId = $this->getNewITILObject('Problem');
        $fup      = new \ITILFollowup();
        $tmp      = ['itemtype' => 'Problem', 'items_id' => $problemId];
        $this->boolean((bool) $fup->can(-1, \CREATE, $tmp))->isTrue();

        $fup_id = $fup->add([
            'content'      => "my followup",
            'itemtype'   => 'Problem',
            'items_id'   => $problemId
        ]);
        $this->integer($fup_id)->isGreaterThan(0);
        $this->boolean((bool) $fup->canViewItem())->isTrue();
        $this->boolean((bool) $fup->canUpdateItem())->isTrue();
        $this->boolean((bool) $fup->canPurgeItem())->isTrue();
    }

    public function testUpdateAndDelete()
    {
        $this->login();

        $ticketId = $this->getNewITILObject('Ticket');
        $fup      = new \ITILFollowup();
        $tmp      = ['itemtype' => 'Ticket', 'items_id' => $ticketId];

        $fup_id = $fup->add([
            'content'      => "my followup",
            'itemtype'   => 'Ticket',
            'items_id'   => $ticketId
        ]);
        $this->integer((int)$fup_id)->isGreaterThan(0);

        $this->boolean(
            $fup->update([
                'id'         => $fup_id,
                'content'    => "my followup updated",
                'itemtype'   => 'Ticket',
                'items_id'   => $ticketId
            ])
        )->isTrue();

        $this->boolean(
            $fup->getFromDB($fup_id)
        )->isTrue();
        $this->string((string) $fup->fields['content'])->isEqualTo('my followup updated');

        $this->boolean(
            $fup->delete([
                'id'  => $fup_id
            ])
        )->isTrue();
        $this->boolean((bool) $fup->getFromDB($fup_id))->isFalse();

        $changeId = $this->getNewITILObject('Change');
        $fup      = new \ITILFollowup();
        $tmp      = ['itemtype' => 'Change', 'items_id' => $changeId];

        $fup_id = $fup->add([
            'content'      => "my followup",
            'itemtype'   => 'Change',
            'items_id'   => $changeId
        ]);
        $this->integer((int)$fup_id)->isGreaterThan(0);

        $this->boolean(
            $fup->update([
                'id'         => $fup_id,
                'content'    => "my followup updated",
                'itemtype'   => 'Change',
                'items_id'   => $changeId
            ])
        )->isTrue();

        $this->boolean(
            $fup->getFromDB($fup_id)
        )->isTrue();
        $this->string((string) $fup->fields['content'])->isEqualTo('my followup updated');

        $this->boolean(
            $fup->delete([
                'id'  => $fup_id
            ])
        )->isTrue();
        $this->boolean((bool) $fup->getFromDB($fup_id))->isFalse();

        $problemId = $this->getNewITILObject('Problem');
        $fup      = new \ITILFollowup();
        $tmp      = ['itemtype' => 'Problem', 'items_id' => $problemId];

        $fup_id = $fup->add([
            'content'      => "my followup",
            'itemtype'   => 'Problem',
            'items_id'   => $problemId
        ]);
        $this->integer((int)$fup_id)->isGreaterThan(0);

        $this->boolean(
            $fup->update([
                'id'         => $fup_id,
                'content'    => "my followup updated",
                'itemtype'   => 'Problem',
                'items_id'   => $problemId
            ])
        )->isTrue();

        $this->boolean(
            $fup->getFromDB($fup_id)
        )->isTrue();
        $this->string((string) $fup->fields['content'])->isEqualTo('my followup updated');

        $this->boolean(
            $fup->delete([
                'id'  => $fup_id
            ])
        )->isTrue();
        $this->boolean((bool) $fup->getFromDB($fup_id))->isFalse();
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

       // Normal behaviior, no flag specified
        $ticketID = $this->getNewITILObject('Ticket');
        $this->integer($ticketID);

        $ITILFollowUp = new \ITILFollowup();
        $this->integer($ITILFollowUp->add([
            'date'                            => $_SESSION['glpi_currenttime'],
            'users_id'                        => \Session::getLoginUserID(),
            'content'                         => "Functional test",
            'items_id'                        => $ticketID,
            'itemtype'                        => \Ticket::class,
        ]));

        $this->boolean($ticket->getFromDB($ticketID))->isTrue();
        $this->integer((int) $ticket->fields['takeintoaccount_delay_stat'])->isGreaterThan(0);
        $this->string($ticket->fields['takeintoaccountdate'])->isEqualTo($_SESSION['glpi_currenttime']);

       // Now using the _do_not_compute_takeintoaccount flag
        $ticketID = $this->getNewITILObject('Ticket');
        $this->integer($ticketID);

        $ITILFollowUp = new \ITILFollowup();
        $this->integer($ITILFollowUp->add([
            'date'                            => $_SESSION['glpi_currenttime'],
            'users_id'                        => \Session::getLoginUserID(),
            'content'                         => "Functional test",
            '_do_not_compute_takeintoaccount' => true,
            'items_id'                        => $ticketID,
            'itemtype'                        => \Ticket::class,
        ]));

        $this->boolean($ticket->getFromDB($ticketID))->isTrue();
        $this->integer((int) $ticket->fields['takeintoaccount_delay_stat'])->isEqualTo(0);
        $this->variable($ticket->fields['takeintoaccountdate'])->isEqualTo(null);

       // Reset conf
        $_SESSION['glpiset_default_tech']      = $oldConf['glpiset_default_tech'];
        $_SESSION['glpiset_default_requester'] = $oldConf['glpiset_default_requester'];
    }

    protected function testIsFromSupportAgentProvider()
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
     * @dataprovider testIsFromSupportAgentProvider
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
        $this->integer($ticket_id);

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
        $this->integer($users_id)->isGreaterThan(0);

       // Insert a followup
        $fup = new CoreITILFollowup();
        $fup_id = $fup->add([
            'content'  => "testIsFromSupportAgent",
            'users_id' => $users_id,
            'items_id' => $ticket_id,
            'itemtype' => "Ticket",
        ]);
        $this->integer($fup_id);
        $this->boolean($fup->getFromDB($fup_id))->isTrue();

       // Remove any roles that may have been set after insert
        $DB->delete(Ticket_User::getTable(), ['tickets_id' => $ticket_id]);
        $this->array($ticket->getITILActors())->hasSize(0);

       // Insert roles
        $tuser = new Ticket_User();
        foreach ($roles as $role) {
            $this->integer($tuser->add([
                'tickets_id' => $ticket_id,
                'users_id'   => $users_id,
                'type'       => $role,
            ]));
        }

       // Execute test
        $result = $fup->isFromSupportAgent();
        $this->boolean($result)->isEqualTo($expected);

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
        $this->boolean($ticket->isNewItem())->isFalse();

        $base64Image = base64_encode(file_get_contents(__DIR__ . '/../fixtures/uploads/foo.png'));
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
        copy(__DIR__ . '/../fixtures/uploads/foo.png', GLPI_TMP_DIR . '/' . $filename);

        $instance->add($input);
        $this->boolean($instance->isNewItem())->isFalse();
        $this->boolean($instance->getFromDB($instance->getId()))->isTrue();
        $expected = 'a href="/front/document.send.php?docid=';
        $this->string($instance->fields['content'])->contains($expected);

       // Test uploads for item update
        $base64Image = base64_encode(file_get_contents(__DIR__ . '/../fixtures/uploads/bar.png'));
        $filename = '5e5e92ffd9bd91.44444444image_paste55555555.png';
        copy(__DIR__ . '/../fixtures/uploads/bar.png', GLPI_TMP_DIR . '/' . $filename);
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
        $this->boolean($success)->isTrue();
        $this->boolean($instance->getFromDB($instance->getId()))->isTrue();
        $expected = 'a href="/front/document.send.php?docid=';
        $this->string($instance->fields['content'])->contains($expected);
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
        $this->boolean($ticket->isNewItem())->isFalse();

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
        copy(__DIR__ . '/../fixtures/uploads/foo.txt', GLPI_TMP_DIR . '/' . $filename);
        $instance->add($input);
        $this->boolean($instance->isNewItem())->isFalse();
        $this->string($instance->fields['content'])->contains('testUploadDocuments');
        $count = (new \DBUtils())->countElementsInTable(\Document_Item::getTable(), [
            'itemtype' => 'ITILFollowup',
            'items_id' => $instance->getID(),
        ]);
        $this->integer($count)->isEqualTo(1);

       // Test uploads for item update (adds a 2nd document)
        $filename = '5e5e92ffd9bd91.44444444bar.txt';
        copy(__DIR__ . '/../fixtures/uploads/bar.png', GLPI_TMP_DIR . '/' . $filename);
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
        $this->boolean($success)->isTrue();
        $this->string($instance->fields['content'])->contains('update testUploadDocuments');
        $count = (new \DBUtils())->countElementsInTable(\Document_Item::getTable(), [
            'itemtype' => 'ITILFollowup',
            'items_id' => $instance->getID(),
        ]);
        $this->integer($count)->isEqualTo(2);
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
        $this->integer($templates_id)->isGreaterThan(0);
        $fup = new \ITILFollowup();
        $fups_id = $fup->add([
            '_itilfollowuptemplates_id' => $templates_id,
            'itemtype'                  => 'Ticket',
            'items_id'                  => $ticket->fields['id'],
        ]);
        $this->integer($fups_id)->isGreaterThan(0);

        $this->string($fup->fields['content'])->isEqualTo(Sanitizer::sanitize('<p>test template</p>', false));
        $this->integer($fup->fields['is_private'])->isEqualTo(1);

        $fups_id = $fup->add([
            '_itilfollowuptemplates_id' => $templates_id,
            'itemtype'                  => 'Ticket',
            'items_id'                  => $ticket->fields['id'],
            'content'                   => 'test template2',
            'is_private'                => 0,
        ]);
        $this->integer($fups_id)->isGreaterThan(0);

        $this->string($fup->fields['content'])->isEqualTo('test template2');
        $this->integer($fup->fields['is_private'])->isEqualTo(0);
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
     * @dataProvider testIsParentAlreadyLoadedProvider
     *
     * @param CoreITILFollowup $followup
     * @param bool $is_parent_loaded
     *
     * @return void
     */
    public function testIsParentAlreadyLoaded(
        CoreITILFollowup $followup,
        bool $is_parent_loaded
    ): void {
        $this->boolean(
            $this->callPrivateMethod($followup, 'isParentAlreadyLoaded')
        )->isEqualTo($is_parent_loaded);
    }
}
