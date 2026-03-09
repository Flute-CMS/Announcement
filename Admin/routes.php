<?php

use Flute\Core\Router\Router;
use Flute\Modules\Announcement\Admin\Screens\AnnouncementScreen;

Router::screen('/admin/announcement', AnnouncementScreen::class);
