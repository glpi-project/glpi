<?php
namespace Glpi\ContentTemplates\Parameters;

use CommonDBTM;
use Glpi\ContentTemplates\Parameters\ParametersTypes\AttributeParameter;
use Ticket;

class TicketCrParameters extends AbstractParameters
{
    public static function getDefaultNodeName(): string
    {
        return 'ticket';
    }

    public static function getObjectLabel(): string
    {
        return __('Created tickets');
    }

    protected function getTargetClasses(): array
    {
        return [Ticket::class];
    }

    public function getAvailableParameters(): array
    {
        return [
            new AttributeParameter("id", __('ID')),
			new AttributeParameter("ref", __("Reference (# + id)")),
			new AttributeParameter("link", _n('Link', 'Links', 1), "raw"),
			new AttributeParameter("url", __('URL').', '. __('internal')),
			new AttributeParameter("name", __('Title')),
			new AttributeParameter("content", __('Description'), "raw"),
			new AttributeParameter("status", __('Status')),
        ];
    }

    protected function defineValues(CommonDBTM $ticket): array
    {
	$fields = $ticket->fields;
    $values= [
            'id'        => $fields['id'],
            'ref'       => "#" . $fields['id'],
            'link'      => $ticket->getLink(),
			'url'	=> ltrim($ticket->getLinkURL(), '/'),
			'name'      => $fields['name'],
			'content'   => $fields['content'],
			'status'    => $ticket::getStatus($fields['status']),
			];

    return $values;
    }
}
