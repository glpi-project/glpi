<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
use Glpi\Http\Response;

/**
 * Ajax controller for handling "update", "delete", "restore" and "purge"
 * actions on CommonDBTM objects
 */
class CommonAjaxController
{
    /**
     * Object to update, delete, restore or purge
     * @var CommonDBTM
     */
    protected CommonDBTM $item;

    /**
     * Handle a given POST request
     *
     * @param array $post POST data
     *
     * @return Response
     */
    final public function handleRequest(array $post): Response
    {
        // Validate id as soon as possible so all sub-methods can assume
        // $post['id'] exist
        if (!isset($post['id'])) {
            return $this->errorReponse(400, __("Invalid id"));
        }

        // Validate and instanciate item from supplied itemtype
        $itemtype = $post['itemtype'] ?? "";
        if (!$this->isClassWhitelisted($itemtype)) {
            return $this->errorReponse(403, __("Forbidden itemtype"));
        }
        $this->item = new $itemtype();

        // Handle requested action
        return $this->handleAction($post);
    }

    /**
     * Handle the requested action ("update", "delete", "restore" or "purge")
     * Can be overloaded by potential children classes to add new actions
     *
     * @param array $post POST data
     *
     * @return Response
     */
    protected function handleAction(array $post): Response
    {
        switch ($post['_action'] ?? "") {
            default:
                return $this->errorReponse(400, __("Invalid action"));

            case "update":
                return $this->handleUpdateAction($post);

            case "delete":
                return $this->handleDeleteAction($post);

            case "restore":
                return $this->handleRestoreAction($post);

            case "purge":
                return $this->handlePurgeAction($post);
        }
    }

    /**
     * Create an error response when the controller wasn't able to handle
     * the request (lack or rights, invalid paramters, ...)
     *
     * @param int    $code    HTTP status code
     * @param string $message Error message
     *
     * @return Response
     */
    final protected function errorReponse(int $code, string $message): Response
    {
        $body = [
            'messages' => ['error' => [$message]]
        ];
        $body = $this->insertSessionMessages($body);
        return $this->jsonResponse($code, $body);
    }

    /**
     * Create a success response when the controller handled the request
     * succesfully
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
        return $this->jsonResponse($code, $body);
    }

    /**
     * Build a simple JSON response
     *
     * @param int   $code HTTP status code
     * @param array $body Response's body
     *
     * @return Response
     */
    final protected function jsonResponse(int $code, array $body): Response
    {
        return new Response(
            $code,
            ['Content-Type' => 'application/json'],
            json_encode($body),
        );
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
     * @param array $post POST data
     *
     * @return Response
     */
    final protected function handleUpdateAction(array $post): Response
    {
        // Validate rights
        $error_response = $this->check((int) $post['id'], UPDATE, $post);
        if ($error_response instanceof Response) {
            return $error_response;
        }

        if ($this->item->update($post)) {
            // Successfull update
            // Feedback message is already handled by CommonDBTM::addMessageOnUpdateAction
            return $this->successResponse(200, [
                "friendlyname" => $this->item->getFriendlyName(),
            ]);
        } else {
            // Failed update
            $error = $this->item->formatSessionMessageAfterAction(
                __("Failed to udpate item")
            );
            return $this->errorReponse(422, $error);
        }
    }

    /**
     * Handle "delete" action
     *
     * @param array $post POST data
     *
     * @return Response
     */
    final protected function handleDeleteAction(array $post): Response
    {
        // Validate rights
        $error_response = $this->check((int) $post['id'], DELETE, $post);
        if ($error_response instanceof Response) {
            return $error_response;
        }

        if ($this->item->delete($post)) {
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
     * @param array $post POST data
     *
     * @return Response
     */
    final protected function handleRestoreAction(array $post): Response
    {
        // Validate rights
        $error_response = $this->check((int) $post['id'], DELETE, $post);
        if ($error_response instanceof Response) {
            return $error_response;
        }

        if ($this->item->restore($post)) {
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
     * @param array $post POST data
     *
     * @return Response
     */
    final protected function handlePurgeAction(array $post): Response
    {
        // Validate rights
        $error_response = $this->check((int) $post['id'], PURGE, $post);
        if ($error_response instanceof Response) {
            return $error_response;
        }

        if ($this->item->delete($post, true)) {
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
     * If a check fail, an error reponse will be returned
     * If everything is OK, `null` is returned
     *
     * @param int   $id     Target item's id
     * @param int   $right  Specific right to check
     * @param array $post   POST data
     *
     * @return Response|null
     */
    final protected function check(int $id, int $right, array $post): ?Response
    {
        if (!$this->item->checkIfExistOrNew($id)) {
            return $this->errorReponse(403, __("Item not found"));
        } elseif (!$this->item->can($id, $right, $input)) {
            return $this->errorReponse(
                403,
                __("You don't have permission to perform this action.")
            );
        } else {
            return null;
        }
    }

    /**
     * Check that the given class is whitelisted for ajax updates
     * This is needed as we don't want others controllers to be bypassed by this
     * endpoint
     *
     * @param string $class Given class
     *
     * @return bool
     */
    final protected function isClassWhitelisted(string $class): bool
    {
        return in_array($class, CommonAjaxControllerWhitelist::getClasses());
    }
}
