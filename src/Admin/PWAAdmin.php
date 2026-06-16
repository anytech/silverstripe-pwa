<?php

namespace SilverStripePWA\Admin;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripePWA\Models\PWASettings;
use SilverStripePWA\Models\PushAnnouncement;
use SilverStripePWA\Models\Subscriber;

class PWAAdmin extends ModelAdmin {
    private static $menu_title = 'PWA';
    private static $url_segment = 'pwa';
    private static $menu_icon_class = 'font-icon-mobile';

    private static $managed_models = [
        PWASettings::class => ['title' => 'Settings'],
        PushAnnouncement::class => ['title' => 'Announcements'],
        Subscriber::class => ['title' => 'Subscribers'],
    ];

    private static $allowed_actions = [
        'doSaveSettings',
    ];

    public function getEditForm($id = null, $fields = null) {
        // Render the singleton settings record as a direct form, not a one-row grid.
        if ($this->getModelClass() === PWASettings::class) {
            $settings = PWASettings::current();
            $form = Form::create(
                $this,
                'EditForm',
                $settings->getCMSFields(),
                FieldList::create(
                    FormAction::create('doSaveSettings', 'Save')->addExtraClass('btn-primary font-icon-save')
                )
            )->setHTMLID('Form_EditForm');
            $form->loadDataFrom($settings);
            $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
            return $form;
        }

        return parent::getEditForm($id, $fields);
    }

    public function doSaveSettings($data, $form) {
        $settings = PWASettings::current();
        $form->saveInto($settings);
        $settings->write();
        $form->sessionMessage('Settings saved', 'good');
        return $this->redirectBack();
    }

    public function getList() {
        $list = parent::getList();
        if (in_array($this->getModelClass(), [PushAnnouncement::class, Subscriber::class], true)) {
            $list = $list->sort('Created', 'DESC');
        }
        return $list;
    }

    public function getExportFields() {
        if ($this->getModelClass() === Subscriber::class) {
            return [
                'ID' => 'ID',
                'Type' => 'Type',
                'Platform' => 'Platform',
                'Member.Email' => 'Member Email',
                'Member.FirstName' => 'First Name',
                'Member.Surname' => 'Surname',
                'endpoint' => 'Endpoint / Token',
                'Created' => 'Subscribed Date',
            ];
        }
        return parent::getExportFields();
    }
}
