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

declare(strict_types=1);

namespace Glpi\Controller\Rule;

use Glpi\Controller\AbstractController;
use Glpi\Event;
use Glpi\Exception\Http\BadRequestHttpException;
use Html;
use Psr\Log\LogLevel;
use RuleCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

use function Sabre\HTTP\toDate;

class RuleFormController extends AbstractController
{
    use RuleControllerTrait;

    private RuleCollection $ruleCollection;

    #[Route("/{class}/Form", name: "fixme", priority: -1)]
    public function __invoke(Request $request): Response
    {
        // - checks
        $class = $request->attributes->getString('class');
        $this->checkClassAttributeIsRule($class);
        $this->ruleCollection = $this->getRuleCollectionInstanceFromRuleSubtype($class, (int)$_SESSION['glpiactive_entity']);

        // - init
        $id = (int) $request->get('id');
        $action_add = $request->request->get('add_action');
        $submit_add = $request->request->get('add');
        $submit_update = $request->request->get('update');
        $submit_purge = $request->request->get('purge');

        try {
            // - dispatch
            // actions do the redirection, only displayForm() returns a Response

            // add rule - redirection to rule display after saving
            if ($submit_add) {
                $this->addRule($this->getDataFromRequest($request));
                // @todo tester avec données invalides
            }
            // update rule
            if ($submit_update) {
                $this->updateRule(
                    $request->request->get('id'),
                    $this->getDataFromRequest($request)
                );
            }
            // purge rule
            if ($submit_purge) {
                $this->purgeRule($id, $this->getDataFromRequest($request));
            }

            // default : show form
            return $this->displayForm($id);
        }
        // @todo time to introduce ValidationException ? probably not now.
        catch (\RuntimeException $e) {
            // @todo catch Error or fallback to Glpi error handler ?
            // or throw an Exception that results in a redirect ? (probably not BadRequestHttpException))
            $message = 'An error occurred while processing the form : ' . $e->getMessage();
            // @todo maybe a persistant message on the page is better, a way to do it ?
            \Session::addMessageAfterRedirect($message, message_type: ERROR);

            $this->log($message, $id ?: 0, 3); // level 3 = error @see \CommonDBTM::getLogDefaultLevel()

            // debug
            Html::back();
        }
    }

    private function displayForm(int $id): Response
    {
        $this->ruleCollection->checkGlobal(READ);
        $rulecollection = $this->ruleCollection;
        $rule = $rulecollection->getRuleClass();

        return new StreamedResponse(static function () use ($rulecollection, $rule, $id) {
            $menus = ['admin', $rulecollection->menu_type, $rulecollection->menu_option];
            $rule::displayFullPageForItem($id, $menus, [
                'formoptions' => " data-track-changes='true'"
            ]);
        });
    }

    /**
     * @param array<string, mixed> $rule_fields
     * @return never
     * @throws \Glpi\Exception\RedirectException
     */
    private function addRule(array $rule_fields): never
    {
        $this->ruleCollection->checkGlobal(CREATE);
        $rule = $this->ruleCollection->getRuleClass();

        $creation_id = $rule->add($rule_fields);
        false === $creation_id && throw new \RuntimeException('Failed to add the rule.');
        $this->logSuccess('add', $creation_id);
        // @todo message de success

        Html::redirect($rule->getFormURLWithID($creation_id));
    }

    /**
     * @param int $rule_id
     * @param array<string, mixed> $rule_fields
     * @return never
     */
    private function updateRule(int $rule_id, array $rule_fields): never
    {
        $this->ruleCollection->checkGlobal(UPDATE);
        $rule = $this->ruleCollection->getRuleClass();

        // @todo ajouter un champ 'update' pour traitement spé ? /src/CommonDBTM.php:1686
        // utile juste pour sauvegarder données en session pour réédition.

        $updated = $rule->update(['id' => $rule_id] + $rule_fields);
        false === $updated && throw new \RuntimeException('Failed to update the rule.');
        $this->logSuccess('update', $rule_id);
        // @todo message de success
//        \Session::addMessageAfterRedirect($message, message_type: ERROR);

        Html::back();
    }

    /**
     * @param int $id
     * @param array<string, string> $data
     * @return never
     */
    private function purgeRule(int $id, array $data): never
    {
        $this->ruleCollection->checkGlobal(PURGE);
        $this->ruleCollection->deleteRuleOrder((int) $data['ranking']);
        $rule = $this->ruleCollection->getRuleClass();
        // pass all data in case a plugin hook use it
        // @todo At the moment, as in legacy controller, we receive data from the form, not the data from the database, so unexpected things can happen
        $purged = $rule->delete(['id' => $id] + $data, true);
        false === $purged && throw new \RuntimeException('Failed to purge the rule.');
        $this->logSuccess('purge', $id);
        // @todo message de success

        $rule->redirectToList();
    }

    /**
     * @param class-string<\Rule> $class
     * @throw BadRequestHttpException
     */
    private function checkClassAttributeIsRule(string $class): void
    {
        if (!$class) {
            throw new BadRequestHttpException('The "class" attribute is mandatory for rules form routes.');
        }

        /** @phpstan-ignore-next-line  */
        if (!\is_subclass_of($class, \Rule::class)) {
            throw new BadRequestHttpException('The "class" attribute must be a subclass of Rule.');
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array<string, string>
     */
    private function getDataFromRequest(Request $request): array
    {
        // ranking is not defined at creation time
        $fields = ['name', 'description', 'match', 'is_active', 'condition', 'comment', 'entities_id', 'sub_type', 'ranking'];
        // @todo data sanitize ?
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = $request->request->get($field);
        }

        return array_filter($data);
    }

    private function logSuccess(string $action, int $id): void
    {
        $message = sprintf(__('%1$s executes the "%2$s" action on the item %3$s'), $_SESSION["glpiname"], $action, 'Rule #' . $id);
        $this->log($message, $id);
    }

    private function log(string $message, int $rule_id, ?int $log_level = null): void
    {
        Event::log(
            $rule_id,
            \Rule::getType(),
            $log_level ?? \Rule::getLogDefaultLevel(),
            \Rule::getLogDefaultServiceName(),
            $message
        );
    }
}
