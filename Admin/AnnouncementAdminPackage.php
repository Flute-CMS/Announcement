<?php

namespace Flute\Modules\Announcement\Admin;

use Flute\Admin\Support\AbstractAdminPackage;

class AnnouncementAdminPackage extends AbstractAdminPackage
{
    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');

        $this->loadTranslations('Resources/lang');

        $this->registerScss('Resources/assets/sass/announcement.scss');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return ['admin', 'admin.announcement'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems(): array
    {
        return [
            [
                'title' => __('admin-announcement.title'),
                'icon' => 'ph.bold.megaphone-bold',
                'url' => url('/admin/announcement'),
            ],
        ];
    }

    public function getPriority(): int
    {
        return 15;
    }
}
