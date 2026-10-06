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

namespace tests\units\Glpi\Controller\Form;

use Glpi\Altcha\AltchaManager;
use Glpi\Controller\Form\SubmitAnswerController;
use Glpi\Form\AccessControl\ControlType\DirectAccess;
use Glpi\Form\AccessControl\ControlType\DirectAccessConfig;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Ticket;

final class SubmitAnswerControllerTest extends DbTestCase
{
    use FormTesterTrait;

    public function testRedirectToCreatedItemWhenBackcreatedIsEnabled(): void
    {
        $this->login();
        $_SESSION['glpibackcreated'] = 1;

        $form = $this->createSimpleForm();
        $response = $this->submitForm($form);

        $tickets = (new Ticket())->find([], ['id DESC'], 1);
        $ticket = Ticket::getById(current($tickets)['id']);
        $this->assertSame($ticket->getLinkURL(), $response['redirect_url']);
    }

    public function testNoRedirectWhenBackcreatedIsDisabled(): void
    {
        $this->login();
        $_SESSION['glpibackcreated'] = 0;

        $form = $this->createSimpleForm();
        $response = $this->submitForm($form);

        $this->assertNull($response['redirect_url']);
    }

    public function testNoRedirectWhenCreatedItemCannotBeViewed(): void
    {
        $this->login();
        $_SESSION['glpibackcreated'] = 1;

        $form = $this->createSimpleForm();
        $tickets_count = countElementsInTable(Ticket::getTable());

        // Remove all ticket rights so the created ticket cannot be viewed
        $_SESSION['glpiactiveprofile']['ticket'] = 0;
        $response = $this->submitForm($form);

        // Ticket is created but not viewable by the current user
        $this->assertSame($tickets_count + 1, countElementsInTable(Ticket::getTable()));
        $this->assertNull($response['redirect_url']);
    }

    public function testNoRedirectWhenMultipleItemsAreCreated(): void
    {
        $this->login();
        $_SESSION['glpibackcreated'] = 1;

        $builder = new FormBuilder("Test form with multiple destinations");
        $builder->addQuestion("Name", QuestionTypeShortText::class);
        $builder->setShouldInitDestinations(false);
        $builder->addDestination(FormDestinationTicket::class, "First ticket");
        $builder->addDestination(FormDestinationTicket::class, "Second ticket");
        $form = $this->createForm($builder);
        $tickets_count = countElementsInTable(Ticket::getTable());

        $response = $this->submitForm($form);

        // Both tickets are created and viewable, but no redirection is made
        $this->assertSame($tickets_count + 2, countElementsInTable(Ticket::getTable()));
        $this->assertCount(2, $response['links_to_created_items']);
        $this->assertNull($response['redirect_url']);
    }

    public function testNoRedirectForAnonymousSubmission(): void
    {
        $builder = new FormBuilder("Test anonymous form");
        $builder->addQuestion("Name", QuestionTypeShortText::class);
        $builder->setUseDefaultAccessPolicies(false);
        $builder->addAccessControl(DirectAccess::class, new DirectAccessConfig(
            token: 'my_token',
            allow_unauthenticated: true,
        ));
        $form = $this->createForm($builder);
        $tickets_count = countElementsInTable(Ticket::getTable());

        $this->logOut();
        // Must be ignored as the user is not authenticated
        $_SESSION['glpibackcreated'] = 1;
        $response = $this->submitForm($form, ['token' => 'my_token'], [
            'altcha' => $this->solveAltchaChallenge(),
        ]);

        $this->assertSame($tickets_count + 1, countElementsInTable(Ticket::getTable()));
        $this->assertNull($response['redirect_url']);
    }

    private function createSimpleForm(): Form
    {
        $builder = new FormBuilder("Test form");
        $builder->addQuestion("Name", QuestionTypeShortText::class);

        return $this->createForm($builder);
    }

    private function solveAltchaChallenge(): string
    {
        $challenge = AltchaManager::getInstance()->generateChallenge();

        $number = null;
        for ($i = 0; $i <= $challenge->maxNumber; $i++) {
            if (hash_equals(hash("sha256", $challenge->salt . $i), $challenge->challenge)) {
                $number = $i;
                break;
            }
        }

        return base64_encode(json_encode([
            'algorithm' => $challenge->algorithm,
            'challenge' => $challenge->challenge,
            'number'    => $number,
            'salt'      => $challenge->salt,
            'signature' => $challenge->signature,
        ]));
    }

    private function submitForm(
        Form $form,
        array $query_parameters = [],
        array $extra_data = []
    ): array {
        $question_id = $this->getQuestionId($form, "Name");
        $uri = '/Form/SubmitAnswers';
        if ($query_parameters !== []) {
            $uri .= '?' . http_build_query($query_parameters);
        }
        $request = Request::create($uri, 'POST', [
            'forms_id'             => $form->getID(),
            "answers_$question_id" => "John",
        ] + $extra_data);

        $controller = new SubmitAnswerController(new NullLogger());
        $response = $controller->__invoke($request);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        return json_decode($response->getContent(), true);
    }
}
