<?php

namespace PSO\Controllers;

use PSO\Helpers\Helper;

class FrontendController extends BaseController
{
    public function addActions(): void
    {
        add_action('wp_head', [$this, 'addPreloads'], 0);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function addPreloads(): void
    {
        if(Helper::getSetting('lazy_load')) {
            ?>
            <style>
                body .lazy:not(.lazy-loaded), body .vc_parallax .vc_parallax-inner:not(.lazy-loaded){background-image: none !important}
            </style>
            <?php
        }


        $preloadImages = Helper::getImagesToPreload();

        if (!empty($preloadImages)) {
            foreach ($preloadImages as $imageUrl) {
                echo '<link rel="preload" href="' . esc_url($imageUrl) . '" as="image" fetchpriority="high">';
            }
        }
    }

    public function enqueueScripts()
    {
        wp_enqueue_script(
            'pso-main-script',
            plugin_dir_url(PSO_PLUGIN_FILE) . 'assets/scripts/main.js',
            [],
            time(),
            true // Load in footer
        );
        wp_localize_script( 'pso-main-script', 'admin_ajax', array( admin_url( 'admin-ajax.php' ) ) );

    }
}
