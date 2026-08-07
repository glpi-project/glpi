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
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldStrategy;
use Glpi\Form\Destination\CommonITILField\RequesterField;
use Glpi\Form\Destination\CommonITILField\RequesterFieldConfig;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Session;
use Ticket;
use Ticket_User;

class MailFieldTest extends DbTestCase
{
    use FormTesterTrait;

    /**
     * @return iterable<array{string}>
     */
    public static function emailProvider()
    {
        yield ['josé@example.com'];
        yield ['françois@example.fr'];
        yield ['müller@example.de'];
        yield ['andré.garcía@example.es'];
        yield ['jürgen.øvergård@example.no'];
        yield ['søren@example.dk'];
        yield ['björk@example.is'];
        yield ['naïve@example.com'];
        yield ['user+tag@example.com'];
        yield ['user.name+tag@example.com'];
        yield ['test.user%tag@example.com'];
        yield ["o'brien@example.com"];
        yield ['user_name@example.com'];
        yield ['user-name@example.com'];
        yield ['test@example.com'];
        yield ['user.name@example.com'];
        yield ['admin@sub.example.com'];
        yield ['contact@example123.com'];
    }

    #[DataProvider('emailProvider')]
    public function testFormSubmissionWithEmailRequester(string $email): void
    {
        $this->login();

        $builder = new FormBuilder();
        $builder->addQuestion("Requester email", QuestionTypeEmail::class);
        $form = $this->createForm($builder);

        $requester_config = new RequesterFieldConfig(
            [ITILActorFieldStrategy::LAST_VALID_ANSWER]
        );

        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->assertNotFalse($destination);
        $this->updateItem(
            $destination::class,
            $destination->getId(),
            ['config' => [
                (new RequesterField())->getKey() => $requester_config->jsonSerialize(),
            ],
            ],
            ["config"],
        );

        $answers_handler = AnswersHandler::getInstance();

        $users_id = Session::getLoginUserID();
        $this->assertIsInt($users_id);

        $answers = $answers_handler->saveAnswers(
            $form,
            [
                $this->getQuestionId($form, 'Requester email') => $email,
            ],
            $users_id
        );

        $created_items = $answers->getCreatedItems();
        $this->assertCount(1, $created_items, 'One ticket should be created');

        $ticket = current($created_items);
        $this->assertInstanceOf(Ticket::class, $ticket);

        $ticket_users = (new Ticket_User())->find([
            'tickets_id' => $ticket->getID(),
            'type' => CommonITILActor::REQUESTER,
        ]);
        $this->assertCount(
            1,
            $ticket_users,
            sprintf('One requester should be associated with ticket for email "%s"', $email)
        );

        $ticket_user = array_shift($ticket_users);
        $this->assertSame(
            $email,
            $ticket_user['alternative_email'],
            sprintf('Alternative email should be "%s"', $email)
        );
    }
}
