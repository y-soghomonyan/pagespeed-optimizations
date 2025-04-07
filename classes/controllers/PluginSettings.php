<?php

namespace PSO\Controllers;

class PluginSettings extends BaseController
{
    public function addActions(): void
    {
        add_action('admin_menu', [$this, 'addSettingsPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_notices', [$this,'pso_display_redis_connection_error']);

    }

    public function addFilters(): void
    {
    }

    public function addSettingsPage(): void
    {
        add_options_page(
            'PageSpeed Settings',
            'PageSpeed Settings',
            'manage_options',
            'pso-settings',
            [$this, 'renderSettingsPage']
        );
    }

    public function renderSettingsPage(): void
    {
        ?>
        <div class="wrap">
            <h2>PageSpeed Settings</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('pso_settings_group');
                do_settings_sections('pso-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function pso_display_redis_connection_error()
    {
        $error_message = get_transient('pso_redis_connection_error');
        if ($error_message) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo wp_kses_post($error_message); ?></p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text"><?php _e('Dismiss this notice.', 'pagespeed-optimizations'); ?></span>
                </button>
            </div>
            <?php
        }
    }

    public function registerSettings(): void
    {
        register_setting('pso_settings_group', 'pso_lazy_load', 'intval');
        register_setting('pso_settings_group', 'pso_redis_cache', 'intval');
        register_setting('pso_settings_group', 'pso_redis_host', 'sanitize_text_field');
        register_setting('pso_settings_group', 'pso_redis_port', 'intval');
        register_setting('pso_settings_group', 'pso_preload_images', 'sanitize_textarea_field');
        register_setting('pso_settings_group', 'pso_default_delay', 'intval');
        register_setting('pso_settings_group', 'pso_delayed_scripts', [$this, 'sanitizeDelayedScripts']);

        register_setting('pso_settings_group', 'pso_default_style_delay', 'intval');
        register_setting('pso_settings_group', 'pso_delayed_styles', [$this, 'sanitizeDelayedScripts']);

        add_settings_section(
            'pso_general_settings',
            'General Settings',
            [$this, 'renderGeneralSettingsSection'],
            'pso-settings'
        );

        add_settings_section(
            'pso_delayed_scripts_settings',
            'Delayed Scripts Settings',
            [$this, 'renderDelayedScriptsSection'],
            'pso-settings'
        );

        add_settings_section(
            'pso_delayed_style_settings',
            'Delayed Styles Settings',
            [$this, 'renderDelayedStyleSettingsSection'],
            'pso-settings'
        );

        add_settings_field(
            'pso_lazy_load',
            'Enable Lazy Loading of Images',
            [$this, 'renderLazyLoadField'],
            'pso-settings',
            'pso_general_settings'
        );

        add_settings_field(
            'pso_redis_cache',
            'Enable Redis Cache',
            [$this, 'renderRedisCacheField'],
            'pso-settings',
            'pso_general_settings'
        );

        add_settings_field(
            'pso_redis_host',
            'Redis Host',
            [$this, 'renderRedisHostField'],
            'pso-settings',
            'pso_general_settings'
        );

        add_settings_field(
            'pso_redis_port',
            'Redis Port',
            [$this, 'renderRedisPortField'],
            'pso-settings',
            'pso_general_settings'
        );

        add_settings_field(
            'pso_preload_images',
            'Sitewide Images to Preload',
            [$this, 'renderPreloadImagesField'],
            'pso-settings',
            'pso_general_settings'
        );

        add_settings_field(
            'pso_default_delay',
            'Default Delay for Scripts (milliseconds)',
            [$this, 'renderDefaultDelayField'],
            'pso-settings',
            'pso_delayed_scripts_settings'
        );

        add_settings_field(
            'pso_delayed_scripts',
            'Delayed Scripts',
            [$this, 'renderDelayedScriptsField'],
            'pso-settings',
            'pso_delayed_scripts_settings'
        );

        add_settings_field(
            'pso_default_style_delay',
            'Default Delay for Styles (milliseconds)',
            [$this, 'renderDefaultDelayStyleField'],
            'pso-settings',
            'pso_delayed_style_settings'
        );

        add_settings_field(
            'pso_delayed_styles',
            'Styles to Delay (one handle or keyword/URL per line)',
            [$this, 'renderDelayedStyleField'],
            'pso-settings',
            'pso_delayed_style_settings'
        );
    }

    public function renderGeneralSettingsSection(): void
    {
        echo '<p>Configure general settings for PageSpeed optimizations.</p>';
    }

    public function renderDelayedScriptsSection(): void
    {
        echo '<p>Configure scripts to be loaded with a delay to improve initial page load time.</p>';
        echo '<p>Enter script keywords/URLs and the delay in milliseconds, one per line, separated by a comma (e.g., <code>typeform.com, 3000</code>).</p>';
    }

    public function renderDelayedStyleSettingsSection(): void
    {
        echo '<p>Configure styles to be loaded with a delay and initially with <code>media="print"</code>.</p>';
        echo '<p>Enter the handles or keywords/URLs of the CSS files you want to delay, one per line. After the specified delay, the <code>media</code> attribute will be removed.</p>';
    }


    public function renderLazyLoadField(): void
    {
        $value = get_option('pso_lazy_load', 0);
        ?>
        <input type="checkbox" name="pso_lazy_load" value="1" <?php checked(1, $value); ?> />
        <?php
    }

    public function renderRedisCacheField(): void
    {
        $value = get_option('pso_redis_cache', 0);
        ?>
        <input type="checkbox" name="pso_redis_cache" value="1" <?php checked(1, $value); ?> />
        <?php
    }

    public function renderRedisHostField(): void
    {
        $value = get_option('pso_redis_host', '127.0.0.1');
        ?>
        <input type="text" name="pso_redis_host" value="<?php echo esc_attr($value); ?>" />
        <?php
    }

    public function renderRedisPortField(): void
    {
        $value = get_option('pso_redis_port', 6379);
        ?>
        <input type="number" name="pso_redis_port" value="<?php echo esc_attr($value); ?>" />
        <?php
    }

    public function renderPreloadImagesField(): void
    {
        $value = get_option('pso_preload_images', '');
        ?>
        <textarea name="pso_preload_images" rows="5" cols="50"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">Enter image URLs, one per line.</p>
        <?php
    }

    public function renderDefaultDelayField(): void
    {
        $value = get_option('pso_default_delay', 3000);
        ?>
        <input type="number" name="pso_default_delay" value="<?php echo esc_attr($value); ?>" />
        <?php
    }

    public function renderDefaultDelayStyleField(): void
    {
        $value = get_option('pso_default_style_delay', 3000);
        ?>
        <input type="number" name="pso_default_style_delay" value="<?php echo esc_attr($value); ?>" />
        <?php
    }

    public function renderDelayedStyleField(): void
    {
        $delayed_styles_array = get_option('pso_delayed_styles', []);
        $textarea_value = implode("\n", $delayed_styles_array);
        ?>
        <textarea name="pso_delayed_styles" rows="10" cols="70"><?php echo esc_textarea($textarea_value); ?></textarea>
        <?php
    }

    public function renderDelayedScriptsField(): void
    {
        $delayed_scripts_array = get_option('pso_delayed_scripts', []);
        $textarea_value = implode("\n", $delayed_scripts_array);
        ?>
        <textarea name="pso_delayed_scripts" rows="10" cols="70"><?php echo esc_textarea($textarea_value); ?></textarea>
        <?php
    }

    public function sanitizeDelayedScripts($input): array
    {
        if (is_array($input)) {
            return $input;
        }
        $output = [];
        $lines = explode("\n", str_replace("\r", '', $input));

        foreach ($lines as $line) {
            $keyword = trim($line);
            if (!empty($keyword)) {
                $output[] = $keyword;
            }
        }
        return $output;

    }


}