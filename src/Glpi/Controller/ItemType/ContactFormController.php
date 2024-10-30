<?php

namespace Glpi\Controller\ItemType;

use Contact;
use Glpi\Controller\GenericFormController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

class ContactFormController extends GenericFormController
{
    #[Route(
        path: "/Contact/Form",
        name: "glpi_itemtype_form_contact",
    )]
    public function __invoke(Request $request): Response
    {
        $request->attributes->set('class', Contact::class);

        if ($request->query->has('getvcard')) {
            return $this->handleVcard($request);
        }

        return parent::__invoke($request);
    }

    private function handleVcard(Request $request)
    {
        $id = $request->query->getInt('id', 1);

        if ($id < 0) {
            return new RedirectResponse($request->getBasePath() . '/front/contact.php');
        }

        $contact = new Contact();
        $contact->check($id, READ);

        return new StreamedResponse(fn () => $contact->generateVcard());
    }
}
