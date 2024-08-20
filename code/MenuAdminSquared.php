<?php

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\ORM\DataExtension;

class MenuAdminSquared extends DataExtension
{
    public function updateEditForm(&$form)
    {
        $fields = $form->Fields();
        $MenuSet = $fields->dataFieldByName('MenuSet');

        if ($MenuSet instanceof GridField) {
            $MenuSetConfig = $MenuSet->getConfig();
            $MenuSetConfig->removeComponentsByType(GridFieldAddNewButton::class);
        }
    }
}
