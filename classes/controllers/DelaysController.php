<?php

namespace PSO\Controllers;

use PSO\Helpers\Helper;

class DelaysController extends BaseController
{

    public function addActions(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueDelayedScriptLoader'], PHP_INT_MAX);
        add_action('wp_enqueue_scripts', [$this, 'dequeueByKeyword'], PHP_INT_MAX - 1);
        add_action('wp_footer', [$this, 'printDelayedScripts'], 110);
        add_action('wp_footer', [$this, 'addLazyLoadScript'], 110);


        add_action('wp_enqueue_scripts', [$this, 'delayStyles'], PHP_INT_MAX);
        add_action('wp_footer', [$this, 'printDelayedStyleModificationScript'], PHP_INT_MAX - 100);
    }

    public function addFilters(): void
    {
    }

    public function enqueueDelayedScriptLoader()
    {
        $delayedScriptsConfig = Helper::getScriptsToDelay();
        $defaultDelay = Helper::getSetting('default_delay');

        if (empty($delayedScriptsConfig)) {
            return;
        }

        $scripts_to_delay = [];
        global $wp_scripts;
        foreach ($wp_scripts->queue as $handle) {
            if (isset($wp_scripts->registered[$handle]->src)) {
                $handle_object = $wp_scripts->registered[$handle];
                $script_url = $handle_object->src;
                foreach ($delayedScriptsConfig as $scriptConfig) {
                    if (isset($scriptConfig) && str_contains($script_url, $scriptConfig)) {
                        $scripts_to_delay[] = [
                            'handle' => base64_encode($handle),
                            'src' => base64_encode($script_url),
                            'in_footer' => property_exists($handle_object, 'in_footer') ? $handle_object->in_footer : false,
                            'delay' => intval($defaultDelay),
                        ];
                        break;
                    }
                }
            }
        }
        global $pso_delayed_scripts;
        $pso_delayed_scripts = $scripts_to_delay;
    }

    public function dequeueByKeyword()
    {
        global $pso_delayed_scripts;

        if (!empty($pso_delayed_scripts)) {
            foreach ($pso_delayed_scripts as $script_info) {
                wp_dequeue_script($script_info['handle']);
            }
        }
    }

    public function printDelayedScripts()
    {
        global $pso_delayed_scripts;
        $defaultDelay = Helper::getSetting('default_delay');

        ?>
        <script defer type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                const pso_delayed_scripts = <?php echo json_encode($pso_delayed_scripts); ?>;
                const defaultDelay = <?php echo intval($defaultDelay); ?>;

                if (typeof pso_delayed_scripts !== 'undefined' && Array.isArray(pso_delayed_scripts)) {
                    pso_delayed_scripts.forEach(function(scriptData, index) {
                        setTimeout(function() {
                            var script = document.createElement('script');
                            script.src = atob(scriptData.src);
                            script.async = false;

                            if (scriptData.in_footer) {
                                document.body.appendChild(script);
                            } else {
                                document.head.appendChild(script);
                            }
                        }, defaultDelay * (index + 1));
                    });
                }
            });
        </script>
        <?php
    }

    public function delayStyles()
    {
        $delayedStyles = Helper::getStylesToDelay();
        if (empty($delayedStyles)) {
            return;
        }

        global $wp_styles;
        if (!($wp_styles instanceof \WP_Styles)) {
            return;
        }

        global $pso_delayed_styles_data;
        $pso_delayed_styles_data = [];

        foreach ($wp_styles->queue as $handle) {
            if (isset($wp_styles->registered[$handle]->src)) {
                $styleHandle = $wp_styles->registered[$handle];
                $style_src = $styleHandle->src;

                foreach ($delayedStyles as $handleOrKeyword) {
                    $match = false;
                    if ($handle == $handleOrKeyword) {
                        $match = true;
                    } elseif (str_contains($style_src, $handleOrKeyword)) {
                        $match = true;
                    }

                    if ($match) {
                        $pso_delayed_styles_data[] = [
                            'handle' => $handle,
                            'src' => $style_src,
                            'deps' => $styleHandle->deps,
                            'ver' => $styleHandle->ver,
                            'media' => $styleHandle->media ?? 'all', // Store original media
                        ];
                        wp_dequeue_style($handle);
                        break;
                    }
                }
            }
        }
    }

    public function printDelayedStyleModificationScript()
    {
        global $pso_delayed_styles_data;
        $defaultDelay = Helper::getSetting('default_style_delay');
        if (!empty($pso_delayed_styles_data)) {
            ?>
            <script defer type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {
                    const delayedStylesData = <?php echo json_encode($pso_delayed_styles_data); ?>;
                    setTimeout(() => {
                        delayedStylesData.forEach(function(styleData) {
                            const linkElement = document.createElement('link');
                            linkElement.rel = 'stylesheet';
                            linkElement.href = styleData.src;
                            if (styleData.media) {
                                linkElement.media = styleData.media;
                            }
                            document.head.appendChild(linkElement);
                        });
                    }, <?php echo intval($defaultDelay); ?>);
                });
            </script>
            <?php
        }
    }

    public function addLazyLoadScript()
    {
        if(Helper::getSetting('lazy_load')) {
            $file_path = plugin_dir_path(PSO_PLUGIN_FILE) . 'assets/scripts/lazy.js';
            if(file_exists($file_path)) {
                ?>
                <script id="pso-lazy-load">
                    <?= file_get_contents($file_path)?>
                </script>
                <?php
            }
        }
    }
}