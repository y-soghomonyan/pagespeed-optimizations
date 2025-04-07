<?php

namespace PSO;

use PSO\Controllers\FrontendController;
use PSO\Controllers\HooksController;
use PSO\Controllers\ImageLazyLoadController;
use PSO\Controllers\MetaboxesController;
use PSO\Controllers\PluginSettings;
use PSO\Controllers\DelaysController;
use PSO\Helpers\Helper;
use PSO\Models\RDDB;

class Plugin
{
    public function __construct()
    {
        $this->init();
    }

    public function init():void
    {
        PluginSettings::init();
        FrontendController::init();
        MetaboxesController::init();
        ImageLazyLoadController::init();
        HooksController::init();
        DelaysController::init();
        if (Helper::redisEnabled(true)) {
            new RDDB();
            wp_set_wpdb_vars();
        }
    }
}