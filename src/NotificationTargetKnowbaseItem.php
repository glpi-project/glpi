<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class NotificationTargetKnowbaseItem extends NotificationTarget
{
    public function getEvents()
    {
        return [
            'newknowbase'     => __('New knowbase'),
            'deletingknowbase' => __('Deleting a knowbase'),
            'updateknowbase' => __('Update of a knowbase')
        ];
    }

    public function addNotificationTargets($entity)
    {
        $this->addGroupsToTargets($entity);
    }

    public function addDataForTemplate($event, $options = [])
    {
        $knowbase = $this->obj;

        // Define all language tags
        $this->data['##lang.knowbaseitem.url##']                = __('URL');
        $this->data['##lang.knowbaseitem.subject##']            = __('Name');
        $this->data['##lang.knowbaseitem.content##']            = __('Content');
        $this->data['##lang.knowbaseitem.categories##']         = __('Categories');
        $this->data['##lang.knowbaseitem.is_faq##']             = __('FAQ');
        $this->data['##lang.knowbaseitem.begin_date##']         = __('Begin Date');
        $this->data['##lang.knowbaseitem.end_date##']           = __('End Date');
        $this->data['##lang.knowbaseitem.numberofdocuments##']  = __('Number of documents');
        $this->data['##lang.document.name##']                   = __('Document name');
        $this->data['##lang.document.downloadurl##']            = __('Document download URL');
        $this->data['##lang.document.url##']                    = __('Document URL');
        $this->data['##lang.document.filename##']               = __('Document filename');
        $this->data['##lang.document.weblink##']                = __('Document weblink');
        $this->data['##lang.document.id##']                     = __('Document ID');
        $this->data['##lang.document.heading##']                = __('Document heading');
        $this->data['##lang.target.url##']                      = __('URL');
        $this->data['##lang.target.name##']                     = __('Name');
        $this->data['##lang.target.itemtype##']                 = __('Type');

        // Set data
        $this->data['##knowbaseitem.url##']           = $knowbase->getLink();
        $this->data['##knowbaseitem.subject##']      = $knowbase->fields['name'];
        $this->data['##knowbaseitem.content##']      = $knowbase->fields['answer'];
        $knowbaseitemcategory = new KnowbaseItem_KnowbaseItemCategory();
        foreach (
            $knowbaseitemcategory->find([
                'knowbaseitems_id' => $knowbase->getID()
            ]) as $knowbasecategory
        ) {
            $category = KnowbaseItemCategory::getById($knowbasecategory['knowbaseitemcategories_id']);
            $listofcategories[]      = $category->fields['name'];
        }
        if (isset($listofcategories)) {
            $this->data['##knowbaseitem.categories##']      = implode(', ', $listofcategories);
        } else {
            $this->data['##knowbaseitem.categories##']      = '';
        }
        $this->data['##knowbaseitem.is_faq##']      = Dropdown::getYesNo($knowbase->fields['is_faq']);
        $this->data['##knowbaseitem.begin_date##']      = $knowbase->fields['begin_date'];
        $this->data['##knowbaseitem.end_date##']      = $knowbase->fields['end_date'];

        $documents = new Document_Item();
        $associateddocuments = $documents->find([
            'items_id' => $knowbase->getID(),
            'itemtype' => 'KnowbaseItem'
        ]);
        $this->data['##knowbaseitem.numberofdocuments##']      = count($associateddocuments);
        foreach ($associateddocuments as $docid) {
            $document = Document::getById($docid['documents_id']);
            $this->data['documents'][] = [
                '##document.downloadurl##'             => $document->getDownloadLink(),
                '##document.url##'                     => $document->getLink(),
                '##document.filename##'                => $document->fields['filename'],
                '##document.weblink##'                 => $document->fields['link'],
                '##document.id##'                      => $document->getID(),
                '##document.heading##'                 => $document->fields['name'],
                '##document.name##'                    => $document->fields['name']
            ];
        }

        //Check all possible types of targets
        $groupsknowbaseitem = new Group_KnowbaseItem();
        $targets = [];
        foreach (
            $groupsknowbaseitem->find([
                'knowbaseitems_id' => $knowbase->getID()
            ]) as $groupid
        ) {
            $targets[] = Group::getById($groupid['groups_id']);
        }
        $usersknowbaseitem = new KnowbaseItem_User();
        foreach (
            $usersknowbaseitem->find([
                'knowbaseitems_id' => $knowbase->getID()
            ]) as $userid
        ) {
            $targets[] = User::getById($userid['users_id']);
        }
        $profiles = new KnowbaseItem_Profile();
        foreach (
            $profiles->find([
                'knowbaseitems_id' => $knowbase->getID()
            ]) as $profileid
        ) {
            $targets[] = Profile::getById($profileid['profiles_id']);
        }
        $entities = new Entity_KnowbaseItem();
        foreach (
            $entities->find([
                'knowbaseitems_id' => $knowbase->getID()
            ]) as $entityid
        ) {
            $targets[] = Entity::getById($entityid['entities_id']);
        }
        foreach ($targets as $target) {
            $this->data['targets'][] = [
                '##target.url##'             => $target->getLink(),
                '##target.name##'            => $target->fields['name'],
                '##target.itemtype##'        => $target->getType()
            ];
        }
    }

    public function getTags()
    {
        $tags = [
            'knowbaseitem.url'           => __('URL'),
            'knowbaseitem.categories' => __('Categories'),
            'knowbaseitem.content'       => __('Content'),
            'knowbaseitem.subject'         => __('Subject'),
            'knowbaseitem.begin_date'   => __('Begin Date'),
            'knowbaseitem.end_date'  => __('End Date'),
            'knowbaseitem.is_faq'        => __('FAQ'),
            'knowbaseitem.numberofdocuments'       => __('Number of documents'),
            'document.name'       => __('Document name'),
            'document.downloadurl'       => __('Document download URL'),
            'document.url'       => __('Document URL'),
            'document.filename'       => __('Document filename'),
            'document.weblink'       => __('Document weblink'),
            'document.id'       => __('Document ID'),
            'document.heading'      => __('Document heading'),
            'target.url'           => __('URL'),
            'target.name'           => __('Name'),
            'target.itemtype'           => __('Type')
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList([
                'tag'   => $tag,
                'label' => $label,
                'value' => true,
                'events'  => ['newknowbase', 'updateknowbase']
            ]);
        }

        $foreachtags = [
            'documents' => __('Documents'),
            'targets' => __('Targets')
        ];
        foreach ($foreachtags as $tag => $label) {
            $this->addTagToList([
                'tag'     => $tag,
                'label'   => $label,
                'value'   => false,
                'foreach' => true,
                'events'  => ['newknowbase', 'updateknowbase']
            ]);
        }

        asort($this->tag_descriptions);
    }
}
