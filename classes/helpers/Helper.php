<?php

namespace PSO\Helpers;

class Helper
{
    public static function disableRedis()
    {
        update_option('pso_redis_cache', '0');
    }
    /**
     * Retrieves a plugin setting by name.
     *
     * @param string $settingName The name of the setting to retrieve.
     */
    public static function getSetting(string $settingName)
    {
        $optionName = 'pso_' . $settingName; // Prepend 'pso_' to the setting name
        switch ($settingName) {
            case 'lazy_load':
            case 'redis_cache':
                return (bool) get_option($optionName, false); // Return boolean for checkboxes
            case 'preload_images':
                $images = get_option($optionName, '');
                return array_filter(array_map('trim', explode("\n", $images))); // Return array of trimmed image URLs
            case 'redis_host':
                return sanitize_text_field(get_option($optionName, '127.0.0.1'));
            case 'redis_port':
                return (int) get_option($optionName, 6379);
            case 'default_style_delay':
            case 'default_delay':
                return (int) get_option($optionName, 3000);
            case 'delayed_styles':
            case 'delayed_scripts':
                return get_option($optionName, []);
            default:
                return get_option($optionName);
        }
    }

    public static function redisEnabled(bool $only_settings = false): bool
    {
        $is_redis_enabled = (class_exists(\Redis::class) && self::getSetting( "redis_cache"));
        if($only_settings) return $is_redis_enabled;

        if(self::is_rest_request()) {
            // disable redis for REST API
            return false;
        }

        return $is_redis_enabled;
    }

    public static function flushRedis(): void
    {
        global $wpdb;
        if(method_exists($wpdb, 'flushAll')) {
            $wpdb->flushAll();
        } else {
            global $lcdb;
            if(method_exists($lcdb, 'flushAll')) {
                $lcdb->flushAll();
            }
        }
    }

    public static function maybeBypassRedis(): bool
    {
        $request_uri = $_SERVER['REQUEST_URI'];
        // exlclude uris from cache
        $uris_to_exclude = [
            '/wp-admin/',
            '/wp-login.php',
            '/my-account/',
            '?wc-ajax=checkout'
        ];
        return self::str_contains_multiple($request_uri, $uris_to_exclude);
    }

    public static function str_contains_multiple(string $str, array $needles): bool
    {
        foreach ($needles as $needle) {
            if(str_contains($str, $needle)) {
                return true;
            }
        }
        return false;
    }

    public static function is_admin_page(): bool
    {
        $request_uri = $_SERVER['REQUEST_URI'];
        return str_contains($request_uri, '/wp-admin/');
    }

    public static function is_rest_request(): bool
    {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }

        $rest_prefix = rest_get_url_prefix();
        $request_uri = $_SERVER['REQUEST_URI'];
        return str_contains($request_uri, '/' . $rest_prefix . '/');
    }

    public static function getImagesToPreload()
    {
        $preloadImages = Helper::getSetting('preload_images');
        $pageSpecificImages = get_post_meta(get_the_ID(), 'pso_page_preload_images', true);

        $allPreloadImages = [];

        if (!empty($preloadImages)) {
            $allPreloadImages = array_merge($allPreloadImages, $preloadImages);
        }

        if (!empty($pageSpecificImages)) {
            $pageSpecificImagesArray = array_filter(array_map('trim', explode("\n", $pageSpecificImages)));
            $allPreloadImages = array_merge($allPreloadImages, $pageSpecificImagesArray);
        }

        return array_unique(array_filter(array_map('trim', $allPreloadImages)));
    }

    public static function getStylesToDelay()
    {
        $globalDelayedStyles = self::getSetting('delayed_styles') ?? [];
        $pageSpecificDelayedStyles = get_post_meta(get_the_ID(), 'pso_page_delayed_styles', true);

        $allDelayedStyles = [];

        if (!empty($globalDelayedStyles)) {
            $allDelayedStyles = array_merge($allDelayedStyles, $globalDelayedStyles);
        }

        if (!empty($pageSpecificDelayedStyles)) {
            $pageSpecificDelayedStyles = !is_array($pageSpecificDelayedStyles) ? explode("\n", $pageSpecificDelayedStyles) : $pageSpecificDelayedStyles;
            $pageSpecificDelayedStylesArray = array_filter(array_map('trim', $pageSpecificDelayedStyles));
            $allDelayedStyles = array_merge($allDelayedStyles, $pageSpecificDelayedStylesArray);
        }

        return array_unique(array_filter(array_map('trim', $allDelayedStyles)));
    }

    public static function getScriptsToDelay()
    {
        $globalDelayedScripts = self::getSetting('delayed_scripts') ?? [];
        $pageSpecificDelayedScripts = get_post_meta(get_the_ID(), 'pso_page_delayed_scripts', true);

        $allDelayedScripts = [];

        if (!empty($globalDelayedScripts)) {
            $allDelayedScripts = array_merge($allDelayedScripts, $globalDelayedScripts);
        }

        if (!empty($pageSpecificDelayedScripts)) {
            $pageSpecificDelayedScripts = !is_array($pageSpecificDelayedScripts) ? explode("\n", $pageSpecificDelayedScripts) : $pageSpecificDelayedScripts;
            $pageSpecificDelayedScriptsArray = array_filter(array_map('trim', $pageSpecificDelayedScripts));
            $allDelayedScripts = array_merge($allDelayedScripts, $pageSpecificDelayedScriptsArray);
        }

        return array_unique(array_filter(array_map('trim', $allDelayedScripts)));
    }
}