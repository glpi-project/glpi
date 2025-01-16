<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units\Glpi\Form\Helpdesk\TilesManagerTest;

use DbTestCase;
use Glpi\Helpdesk\Tile\ExternalPageTile;
use Glpi\Helpdesk\Tile\FormTile;
use Glpi\Helpdesk\Tile\GlpiPageTile;
use Glpi\Helpdesk\Tile\Profile_Tile;
use Glpi\Helpdesk\Tile\TilesManager;
use Glpi\Session\SessionInfo;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use InvalidArgumentException;
use Profile;

final class TilesManagerTest extends DbTestCase
{
    use FormTesterTrait;

    private function getManager(): TilesManager
    {
        return new TilesManager();
    }

    public function testTilesCanBeAddedToHelpdeskProfiles(): void
    {
        // Arrange: create a self service profile
        $manager = $this->getManager();
        $profile = $this->createItem(Profile::class, [
            'name' => 'Helpdesk profile',
            'interface' => 'helpdesk',
        ]);

        // Act: add two tile
        $manager->addTile($profile, ExternalPageTile::class, [
            'title'        => "GLPI project",
            'description'  => "Link to GLPI project website",
            'illustration' => "request-service",
            'url'          => "https://glpi-project.org",
        ]);
        $manager->addTile($profile, GlpiPageTile::class, [
            'title'        => "FAQ",
            'description'  => "Link to the FAQ",
            'illustration' => "browse-kb",
            'page'         => GlpiPageTile::PAGE_FAQ,
        ]);

        // Assert: there should be two tiles defined for our profile
        $session = new SessionInfo(profile_id: $profile->getID());
        $tiles = $manager->getTiles($session);
        $this->assertCount(2, $tiles);

        $first_tile = $tiles[0];
        $this->assertInstanceOf(ExternalPageTile::class, $first_tile);
        $this->assertEquals("GLPI project", $first_tile->getTitle());
        $this->assertEquals("Link to GLPI project website", $first_tile->getDescription());
        $this->assertEquals("request-service", $first_tile->getIllustration());
        $this->assertEquals("https://glpi-project.org", $first_tile->getTileUrl());

        $second_tile = $tiles[1];
        $this->assertInstanceOf(GlpiPageTile::class, $second_tile);
        $this->assertEquals("FAQ", $second_tile->getTitle());
        $this->assertEquals("Link to the FAQ", $second_tile->getDescription());
        $this->assertEquals("browse-kb", $second_tile->getIllustration());
        $this->assertEquals("/glpi/front/helpdesk.faq.php", $second_tile->getTileUrl());
    }

    public function testTilesCantBeAddedToCentralProfiles(): void
    {
        // Arrange: create a central profile
        $manager = $this->getManager();
        $profile = $this->createItem(Profile::class, [
            'name' => 'Central profile',
            'interface' => 'central',
        ]);

        // Expect a failure
        $this->expectException(InvalidArgumentException::class);

        // Act: add a tile
        $manager->addTile($profile, ExternalPageTile::class, [
            'title'        => "GLPI project",
            'description'  => "Link to GLPI project website",
            'illustration' => "request-service",
            'url'          => "https://glpi-project.org",
        ]);
    }

    public function testOnlyActiveFormTileAreFound(): void
    {
        $test_entity_id = $this->getTestRootEntity(only_id: true);

        // Arrange: create a self service profile and mutliple form tiles
        $forms = [];
        $manager = $this->getManager();
        $profile = $this->createItem(Profile::class, [
            'name' => 'Helpdesk profile',
            'interface' => 'helpdesk',
        ]);

        $builder = new FormBuilder("Inactive form");
        $builder->setIsActive(false);
        $builder->setEntitiesId($test_entity_id);
        $builder->allowAllUsers();
        $forms[] = $this->createForm($builder);

        $builder = new FormBuilder("Active form");
        $builder->setIsActive(true);
        $builder->setEntitiesId($test_entity_id);
        $builder->allowAllUsers();
        $forms[] = $this->createForm($builder);

        foreach ($forms as $form) {
            $manager->addTile($profile, FormTile::class, [
                'forms_forms_id' => $form->getID(),
            ]);
        }

        // Act: get tiles
        $session = new SessionInfo(
            profile_id: $profile->getID(),
            active_entities_ids: [$test_entity_id],
        );
        $tiles = $manager->getTiles($session);

        // Assert: only the active form tile should be found
        $form_names = array_map(fn($tile) => $tile->getTitle(), $tiles);
        $this->assertEquals(["Active form"], $form_names);
    }

    public function testOnlyFormWithValidAccessPoliciesAreFound(): void
    {
        $test_entity_id = $this->getTestRootEntity(only_id: true);

        // Arrange: create a self service profile and mutliple form tiles
        $forms = [];
        $manager = $this->getManager();
        $profile = $this->createItem(Profile::class, [
            'name' => 'Helpdesk profile',
            'interface' => 'helpdesk',
        ]);

        $builder = new FormBuilder("Form without access policies");
        $builder->setIsActive(true);
        $builder->setEntitiesId($test_entity_id);
        $forms[] = $this->createForm($builder);

        $builder = new FormBuilder("Form with access policies");
        $builder->setIsActive(true);
        $builder->setEntitiesId($test_entity_id);
        $builder->allowAllUsers();
        $forms[] = $this->createForm($builder);

        foreach ($forms as $form) {
            $manager->addTile($profile, FormTile::class, [
                'forms_forms_id' => $form->getID(),
            ]);
        }

        // Act: get tiles
        $session = new SessionInfo(
            profile_id: $profile->getID(),
            active_entities_ids: [$test_entity_id],
        );
        $tiles = $manager->getTiles($session);

        // Assert: only the form with a valid access policy should be found
        $form_names = array_map(fn($tile) => $tile->getTitle(), $tiles);
        $this->assertEquals(["Form with access policies"], $form_names);
    }

    public function testOnlyFormVisibleFromActiveEntityAreFound(): void
    {
        $test_entity_id = $this->getTestRootEntity(only_id: true);

        // Arrange: create a self service profile and mutliple form tiles
        $forms = [];
        $manager = $this->getManager();
        $profile = $this->createItem(Profile::class, [
            'name' => 'Helpdesk profile',
            'interface' => 'helpdesk',
        ]);

        $builder = new FormBuilder("Form inside current entity");
        $builder->setIsActive(true);
        $builder->setEntitiesId($test_entity_id);
        $builder->allowAllUsers();
        $forms[] = $this->createForm($builder);

        $builder = new FormBuilder("Form outside current entity");
        $builder->setIsActive(true);
        $builder->setEntitiesId(0);
        $builder->setIsRecursive(false);
        $builder->allowAllUsers();
        $forms[] = $this->createForm($builder);

        $builder = new FormBuilder("Form inside recursive parent entity");
        $builder->setIsActive(true);
        $builder->setEntitiesId(0);
        $builder->setIsRecursive(true);
        $builder->allowAllUsers();
        $forms[] = $this->createForm($builder);

        foreach ($forms as $form) {
            $manager->addTile($profile, FormTile::class, [
                'forms_forms_id' => $form->getID(),
            ]);
        }

        // Act: get tiles
        $session = new SessionInfo(
            profile_id: $profile->getID(),
            active_entities_ids: [$test_entity_id],
        );
        $tiles = $manager->getTiles($session);

        // Assert: only the form with a valid access policy should be found
        $form_names = array_map(fn($tile) => $tile->getTitle(), $tiles);
        $this->assertEquals([
            "Form inside current entity",
            "Form inside recursive parent entity",
        ], $form_names);
    }

    public function testTilesAreOrderedByRanks(): void
    {
        // Arrange: create three tiles and modify their orders
        $manager = $this->getManager();
        $profile = $this->createItem(Profile::class, [
            'name' => 'Helpdesk profile',
            'interface' => 'helpdesk',
        ]);
        $manager->addTile($profile, ExternalPageTile::class, [
            'title'        => "GLPI project",
            'description'  => "Link to GLPI project website",
            'illustration' => "request-service",
            'url'          => "https://glpi-project.org",
        ]);
        $profile_tile_id = $manager->addTile($profile, GlpiPageTile::class, [
            'title'        => "FAQ",
            'description'  => "Link to the FAQ",
            'illustration' => "browse-kb",
            'page'         => GlpiPageTile::PAGE_FAQ,
        ]);
        $manager->addTile($profile, ExternalPageTile::class, [
            'title'        => "Support",
            'description'  => "Link to teclib support",
            'illustration' => "report-issue",
            'url'          => "https://support.teclib.org",
        ]);

        // Get the second tile and move it at the end
        $this->updateItem(Profile_Tile::class, $profile_tile_id, [
            'rank' => 10,
        ]);

        // Act: get tiles
        $session = new SessionInfo(profile_id: $profile->getID());
        $tiles = $manager->getTiles($session);

        // Assert: tiles must be in the expected order
        $this->assertCount(3, $tiles);

        $first_tile = $tiles[0];
        $this->assertInstanceOf(ExternalPageTile::class, $first_tile);
        $this->assertEquals("GLPI project", $first_tile->getTitle());
        $this->assertEquals("Link to GLPI project website", $first_tile->getDescription());
        $this->assertEquals("request-service", $first_tile->getIllustration());
        $this->assertEquals("https://glpi-project.org", $first_tile->getTileUrl());

        $second_tile = $tiles[1];
        $this->assertInstanceOf(ExternalPageTile::class, $second_tile);
        $this->assertEquals("Support", $second_tile->getTitle());
        $this->assertEquals("Link to teclib support", $second_tile->getDescription());
        $this->assertEquals("report-issue", $second_tile->getIllustration());
        $this->assertEquals("https://support.teclib.org", $second_tile->getTileUrl());

        $third_tile = $tiles[2];
        $this->assertInstanceOf(GlpiPageTile::class, $third_tile);
        $this->assertEquals("FAQ", $third_tile->getTitle());
        $this->assertEquals("Link to the FAQ", $third_tile->getDescription());
        $this->assertEquals("browse-kb", $third_tile->getIllustration());
        $this->assertEquals("/glpi/front/helpdesk.faq.php", $third_tile->getTileUrl());
    }

    public function testTilesOrderCanBeSet(): void
    {
        // Arrange: create three tiles
        $manager = $this->getManager();
        $profile = $this->createItem(Profile::class, [
            'name' => 'Helpdesk profile',
            'interface' => 'helpdesk',
        ]);
        $profile_tile_id_1 = $manager->addTile($profile, ExternalPageTile::class, [
            'title'        => "GLPI project",
            'description'  => "Link to GLPI project website",
            'illustration' => "request-service",
            'url'          => "https://glpi-project.org",
        ]);
        $profile_tile_id_2 = $manager->addTile($profile, GlpiPageTile::class, [
            'title'        => "FAQ",
            'description'  => "Link to the FAQ",
            'illustration' => "browse-kb",
            'page'         => GlpiPageTile::PAGE_FAQ,
        ]);
        $profile_tile_id_3 = $manager->addTile($profile, ExternalPageTile::class, [
            'title'        => "Support",
            'description'  => "Link to teclib support",
            'illustration' => "report-issue",
            'url'          => "https://support.teclib.org",
        ]);

        // Act: set a new order
        $manager->setOrderForProfile($profile, [
            $profile_tile_id_3,
            $profile_tile_id_1,
            $profile_tile_id_2,
        ]);

        // Assert: confirm the new order
        $session = new SessionInfo(profile_id: $profile->getID());
        $tiles = $manager->getTiles($session);

        $first_tile = $tiles[0];
        $this->assertInstanceOf(ExternalPageTile::class, $first_tile);
        $this->assertEquals("Support", $first_tile->getTitle());
        $this->assertEquals("Link to teclib support", $first_tile->getDescription());
        $this->assertEquals("report-issue", $first_tile->getIllustration());
        $this->assertEquals("https://support.teclib.org", $first_tile->getTileUrl());

        $second_tile = $tiles[1];
        $this->assertInstanceOf(ExternalPageTile::class, $second_tile);
        $this->assertEquals("GLPI project", $second_tile->getTitle());
        $this->assertEquals("Link to GLPI project website", $second_tile->getDescription());
        $this->assertEquals("request-service", $second_tile->getIllustration());
        $this->assertEquals("https://glpi-project.org", $second_tile->getTileUrl());

        $third_tile = $tiles[2];
        $this->assertInstanceOf(GlpiPageTile::class, $third_tile);
        $this->assertEquals("FAQ", $third_tile->getTitle());
        $this->assertEquals("Link to the FAQ", $third_tile->getDescription());
        $this->assertEquals("browse-kb", $third_tile->getIllustration());
        $this->assertEquals("/glpi/front/helpdesk.faq.php", $third_tile->getTileUrl());
    }

    public function testDeleteTile(): void
    {
        // Arrange: create a profile with some tiles
        $manager = $this->getManager();
        $profile = $this->createItem(Profile::class, [
            'name' => 'Helpdesk profile',
            'interface' => 'helpdesk',
        ]);
        $manager->addTile($profile, ExternalPageTile::class, [
            'title'        => "GLPI project",
            'description'  => "Link to GLPI project website",
            'illustration' => "request-service",
            'url'          => "https://glpi-project.org",
        ]);
        $profile_tile_id_2 = $manager->addTile($profile, GlpiPageTile::class, [
            'title'        => "FAQ",
            'description'  => "Link to the FAQ",
            'illustration' => "browse-kb",
            'page'         => GlpiPageTile::PAGE_FAQ,
        ]);
        $manager->addTile($profile, ExternalPageTile::class, [
            'title'        => "Support",
            'description'  => "Link to teclib support",
            'illustration' => "report-issue",
            'url'          => "https://support.teclib.org",
        ]);

        // Act: delete the second tile
        $profile_tile = Profile_Tile::getById($profile_tile_id_2);
        $tile_id = $profile_tile->fields['items_id'];
        $this->getManager()->deleteTile(GlpiPageTile::getById($tile_id));

        // Assert: the tile must not be found and must be cleared from the DB
        $session = new SessionInfo(profile_id: $profile->getID());
        $tiles = $manager->getTiles($session);
        $this->assertCount(2, $tiles);

        $first_tile = $tiles[0];
        $second_tile = $tiles[1];
        $this->assertNotEquals("FAQ", $first_tile->getTitle());
        $this->assertNotEquals("FAQ", $second_tile->getTitle());

        $this->assertFalse(Profile_Tile::getById($profile_tile_id_2));
        $this->assertFalse(GlpiPageTile::getById($tile_id));
    }
}
