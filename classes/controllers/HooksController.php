<?php

namespace PSO\Controllers;

use PSO\Helpers\Helper;

class HooksController extends BaseController
{
    public function addActions(): void
    {
        add_action('admin_bar_menu', [$this, 'addFlushButton'], 100);
        add_action('wp_ajax_send_request_flush_redis', [$this,'flushRedisCacheAdmin']);
        add_action('woocommerce_order_status_processing', [$this, 'flushRedisCache'], 999);
        add_action('save_post', [$this, 'flushRedisCache'], 999);
    }

    public function addFilters(): void
    {
        add_filter('pre_update_option_active_plugins', [$this, 'sortActivePlugins']);
    }
    public function flushRedisCacheAdmin(): void
    {
        if(!Helper::redisEnabled() && wp_doing_ajax()) {
            wp_send_json([
                'success' => false,
                'message' => esc_html('Redis cache is disabled', 'pso'),
            ]);
            exit;
        }

        Helper::flushRedis();

        if(wp_doing_ajax()) {
            wp_send_json([
                'success' => true,
                'message' => esc_html('All caches are cleared!', 'pso'),
            ]);
            exit;
        }
    }

    public function flushRedisCache(): void
    {
        Helper::flushRedis();
    }

    public function sortActivePlugins($plugins): array
    {
        $data = [];

        if (in_array('pagespeed-optimizations/pagespeed-optimizations.php', $plugins)) {
            $data[] = 'pagespeed-optimizations/pagespeed-optimizations.php';
        }

        $data = array_unique([...$data, ...$plugins]);

        $first_plugin = array_keys($plugins)[0];

        array_splice($plugins, $first_plugin, 0, $data);

        return array_unique($plugins);
    }

    public function addFlushButton($wp_admin_bar):void
    {
        if(Helper::redisEnabled()) {
            $args = array(
                'title' => '<span class="ab-icon dashicons-before dashicons-trash"></span><span class="ab-label">Clear Object Caches</span><span class="screen-reader-text">Clear Object Caches action</span>',
                'href'  => '#',
                'meta'  => array(
                    'class' => 'flush-redis-button',
                ),
            );
            $wp_admin_bar->add_menu($args);
        }
    }
}