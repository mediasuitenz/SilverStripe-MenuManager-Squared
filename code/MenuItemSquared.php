<?php

use Heyday\MenuManager\MenuItem;
use Heyday\MenuManager\MenuSet;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\LabelField;
use SilverStripe\ORM\DataExtension;
use Symbiote\GridFieldExtensions\GridFieldAddNewMultiClass;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class MenuItemSquared extends DataExtension
{
    private static $db = [
        'Name' => 'Varchar(255)',
    ];

    private static $has_one = [
        'Image' => Image::class,
        'ParentItem' => MenuItem::class,
    ];

    private static $has_many = [
        'ChildItems' => MenuItem::class,
    ];

    public function updateCMSFields(FieldList $fields)
    {
        if (!$this->owner->config()->disable_image) {
            $fields->push(new UploadField('Image', 'Image'));
        }

        if (!$this->owner->config()->disable_hierarchy) {
            if ($this->owner->ID != null) {
                $AllParentItems = $this->owner->getAllParentItems();
                $TopMenuSet = $this->owner->TopMenuSet();
                $depth = 1;

                if (
                    is_array(MenuSet::config()->{$TopMenuSet->Name}) &&
                    isset(MenuSet::config()->{$TopMenuSet->Name}['depth']) &&
                    is_numeric(MenuSet::config()->{$TopMenuSet->Name}['depth']) &&
                    MenuSet::config()->{$TopMenuSet->Name}['depth'] >= 0
                ) {
                    $depth = MenuSet::config()->{$TopMenuSet->Name}['depth'];
                }

                if (!empty($AllParentItems) && count($AllParentItems) >= $depth) {
                    $fields->push(new LabelField('MenuItems', 'Max Sub Menu Depth Limit'));
                } else {
                    $fields->push(
                        new GridField(
                            'MenuItems',
                            'Sub Menu Items',
                            $this->owner->ChildItems(),
                            $config = GridFieldConfig_RecordEditor::create()
                        )
                    );
                    $config->addComponent(new GridFieldOrderableRows('Sort'));
                    $config->removeComponentsByType('GridFieldAddNewButton');
                    $multiClass = new GridFieldAddNewMultiClass();
                    $classes = ClassInfo::subclassesFor('MenuItem');
                    $multiClass->setClasses($classes);
                    $config->addComponent($multiClass);
                }
            } else {
                $fields->push(new LabelField('MenuItems', 'Save This Menu Item Before Adding Sub Menu Items'));
            }
        }
    }

    public function TopMenuSet()
    {
        $AllParentItems = $this->owner->getAllParentItems();
        if (!empty($AllParentItems)) {
            return end($AllParentItems)->MenuSet();
        }
        return $this->owner->MenuSet();
    }

    public function getAllParentItems()
    {
        $WorkingItem = $this->owner;
        $ParentItems = [];

        while ($WorkingItem->ParentItemID && $WorkingItem->ParentItem() && $WorkingItem->ParentItem()->ID && !isset($ParentItems[$WorkingItem->ParentItem()->ID])) {
            $ParentItems[$WorkingItem->ID] = $WorkingItem->ParentItem();
            $WorkingItem = $ParentItems[$WorkingItem->ID];
        }
        return $ParentItems;
    }

    public function onBeforeWrite()
    {
        if (!$this->owner->Sort) {
            $this->owner->Sort = MenuItem::get()->max('Sort') + 1;
        }
        if ($this->owner->MenuTitle) {
            $this->owner->Name = $this->owner->MenuTitle;
        }
        parent::onBeforeWrite();
    }

    public function onBeforeDelete()
    {
        foreach ($this->owner->ChildItems() as $childItem) {
            $childItem->delete();
        }
        parent::onBeforeDelete();
    }

    public static function get_user_friendly_name()
    {
        $title = Config::inst()->get(get_called_class(), 'user_friendly_title');
        return $title ?: FormField::name_to_label(get_called_class());
    }
}
