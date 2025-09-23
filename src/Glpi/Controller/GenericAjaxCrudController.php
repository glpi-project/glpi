<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Controller;

use CommonDBTM;
use Glpi\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Ajax controller for handling "update", "delete", "restore" and "purge"
 * actions on CommonDBTM objects
 */
class GenericAjaxCrudController extends AbstractController
{
    /**
     * Object to update, delete, restore or purge
     * @var CommonDBTM
     */
    protected CommonDBTM $item;

    #[Route("/GenericAjaxCrud", name: "glpi_generic_ajax_crud")]
    public function __invoke(Request $request): Response
    {
        $input = $request->toArray();

        // Validate id as soon as possible so all sub-methods can assume
        // $input['id'] exist
        if (!isset($input['id'])) {
            return $this->errorReponse(400, __s("Invalid id")); // response string will not be escaped when inserted to the DOM
        }

        // Validate and instanciate item from supplied itemtype
        $itemtype = $input['itemtype'] ?? "";
        if (!\is_string($itemtype) || !\is_a($itemtype, CommonDBTM::class, true)) {
            return $this->errorReponse(400, __s("Invalid itemtype")); // response string will not be escaped when inserted to the DOM
        }
        if (!$this->isClassAllowed($itemtype)) {
            return $this->errorReponse(403, __s("Forbidden itemtype")); // response string will not be escaped when inserted to the DOM
        }
        $this->item = new $itemtype();

        // Handle requested action
        try {
            // READ right is historically always checked no matter the action
            $this->check((int) $input['id'], READ, $input);

            return $this->handleAction($input);
        } catch (HttpException $e) {
            return $this->errorReponse($e->getStatusCode(), \htmlescape($e->getMessage())); // response string will not be escaped when inserted to the DOM
        }
    }

    /**
     * Handle the requested action ("update", "delete", "restore" or "purge")
     * Can be overloaded by potential children classes to add new actions
     *
     * @param array $input Input data
     *
     * @return Response
     */
    protected function handleAction(array $input): Response
    {
        switch ($input['_action'] ?? "") {
            default:
                return $this->errorReponse(400, __s("Invalid action")); // response string will not be escaped when inserted to the DOM

            case "update":
                return $this->handleUpdateAction($input);

            case "delete":
                return $this->handleDeleteAction($input);

            case "restore":
                return $this->handleRestoreAction($input);

            case "purge":
                return $this->handlePurgeAction($input);
        }
    }

    /**
     * Create an error response when the controller wasn't able to handle
     * the request (lack or rights, invalid parameters, ...)
     *
     * @param int    $code    HTTP status code
     * @param string $message Error message
     *
     * @return Response
     *
     * @psalm-taint-specialize (to report each unsafe usage as a distinct error)
     * @psalm-taint-sink html $message (string will be added to HTML source)
     */
    final protected function errorReponse(int $code, string $message): Response
    {
        $body = [
            'messages' => ['error' => [$message]],
        ];
        $body = $this->insertSessionMessages($body);
        return new JsonResponse($body, $code);
    }

    /**
     * Create a success response when the controller handled the request
     * successfully
     *
     * @param int   $code HTTP status code
     * @param array $body Response's body
     *
     * @return Response
     */
    final protected function successResponse(int $code, array $body): Response
    {
        // TODO: we probably also need to insert the updated `date_mod` field to
        // the response so it can be applied in the UX but its not yet displayed
        // for Forms so there is no way to test it right now
        $body = $this->insertSessionMessages($body);
        return new JsonResponse($body, $code);
    }

    /**
     * Insert session messages into the body of a response
     *
     * @param array $body
     *
     * @return array Updated body
     */
    final protected function insertSessionMessages(array $body): array
    {
        // Session messages are already handled by GLPI in case of a redirection
        if (isset($body['redirect'])) {
            return $body;
        }

        // Get each message types
        $info    = $body['messages']['info'] ?? [];
        $warning = $body['messages']['warning'] ?? [];
        $error   = $body['messages']['error'] ?? [];

        // Add session messages to body
        $messages = $_SESSION['MESSAGE_AFTER_REDIRECT'] ?? [];
        if (isset($messages[INFO])) {
            array_push($info, ...$messages[INFO]);
        }
        if (isset($messages[WARNING])) {
            array_push($warning, ...$messages[WARNING]);
        }
        if (isset($messages[ERROR])) {
            array_push($error, ...$messages[ERROR]);
        }

        // Clear session
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];

        // Update body
        $body['messages'] = [
            'info'    => $info,
            'warning' => $warning,
            'error'   => $error,
        ];
        return $body;
    }

    /**
     * Handle "update" action
     *
     * @param array $input Input data
     *
     * @return Response
     */
    final protected function handleUpdateAction(array $input): Response
    {
        // Validate rights
        $this->check((int) $input['id'], UPDATE, $input);

        if ($this->item->update($input)) {
            // Successfull update
            // Feedback message is already handled by CommonDBTM::addMessageOnUpdateAction
            return $this->successResponse(200, [
                "friendlyname" => $this->item->getFriendlyName(),
            ]);
        } else {
            // Failed update
            $error = $this->item->formatSessionMessageAfterAction(
                __("Failed to update item")
            );
            return $this->errorReponse(422, $error);
        }
    }

    /**
     * Handle "delete" action
     *
     * @param array $input Input data
     *
     * @return Response
     */
    final protected function handleDeleteAction(array $input): Response
    {
        // Validate rights
        $this->check((int) $input['id'], DELETE, $input);

        if ($this->item->delete($input)) {
            // Successfull deletion
            // Feedback message is already handled by CommonDBTM::addMessageOnDeleteAction
            return $this->successResponse(200, [
                "is_deleted" => true,
            ]);
        } else {
            // Failed deletion
            $error = $this->item->formatSessionMessageAfterAction(
                __("Failed to delete item")
            );
            return $this->errorReponse(422, $error);
        }
    }

    /**
     * Handle "restore" action
     *
     * @param array $input Input data
     *
     * @return Response
     */
    final protected function handleRestoreAction(array $input): Response
    {
        // Validate rights
        $this->check((int) $input['id'], DELETE, $input);

        if ($this->item->restore($input)) {
            // Successfull restoration
            // Feedback message is already handled by CommonDBTM::addMessageOnRestoreAction
            return $this->successResponse(200, [
                "is_deleted" => false,
            ]);
        } else {
            // Failed to restore
            $error = $this->item->formatSessionMessageAfterAction(
                __("Failed to restore item")
            );
            return $this->errorReponse(422, $error);
        }
    }

    /**
     * Handle "purge" action
     *
     * @param array $input Input data
     *
     * @return Response
     */
    final protected function handlePurgeAction(array $input): Response
    {
        // Validate rights
        $this->check((int) $input['id'], PURGE, $input);

        if ($this->item->delete($input, true)) {
            // Successfull purge
            // Feedback message is already handled by CommonDBTM::addMessageOnPurgeAction
            return $this->successResponse(200, [
                "redirect" => $this->item->getSearchURL(),
            ]);
        } else {
            // Failed purge
            $error = $this->item->formatSessionMessageAfterAction(
                __("Failed to purge item")
            );
            return $this->errorReponse(422, $error);
        }
    }

    /**
     * Similar checks than done in CommonDBTM::check() without parts that aren't
     * compatible with an AJAX response
     *
     * If a check fail, throws an  exception
     *
     * @param int   $id     Target item's id
     * @param int   $right  Specific right to check
     * @param array $input  Input data
     *
     * @return void
     *
     * @throws HttpException
     */
    final protected function check(int $id, int $right, array $input): void
    {
        if (!$this->item->checkIfExistOrNew($id)) {
            throw new HttpException(
                404,
                __("Item not found")
            );
        } elseif (!$this->item->can($id, $right, $input)) {
            throw new HttpException(
                403,
                __("You don't have permission to perform this action.")
            );
        }
    }

    /**
     * Check that the given class is allowed for ajax updates
     * This is needed as we don't want others controllers to be bypassed by this
     * endpoint.
     *
     * Temporary method, this check should be deleted once we are confident
     * that our rights management is good enough to allow all classes to access
     * this endpoint.
     *
     * @param string $class Given class
     *
     * @return bool
     */
    final protected function isClassAllowed(string $class): bool
    {
        $allowed_classes = [
            Form::class,
        ];

        return in_array($class, $allowed_classes);
    }
}
