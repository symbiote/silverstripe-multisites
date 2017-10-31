<?php

use Symbiote\Multisites\Admin\MultisitesCMSPageAddController;
use SilverStripe\Admin\CMSMenu;

CMSMenu::remove_menu_class(MultisitesCMSPageAddController::class);
