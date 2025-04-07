<?php

namespace PSO\Controllers;

class MetaboxesController extends BaseController
{
    public function addActions(): void
    {
        add_action('add_meta_boxes', [$this, 'addPageSpeedMetaBoxes']);
        add_action('save_post', [$this, 'savePageSpeedMetaBoxes']);
    }

    public function addFilters(): void
    {
    }

    public function addPageSpeedMetaBoxes(): void
    {
        add_meta_box(
            'pso_preload_meta_box',
            'Page Specific Preload Images',
            [$this, 'renderPreloadMetaBox'],
            ['post', 'page', 'product'],
            'normal',
            'default'
        );
        add_meta_box(
            'pso_delayed_styles_meta_box',
            'Page Specific Delayed Styles',
            [$this, 'renderDelayedStylesMetaBox'],
            ['post', 'page', 'product'],
            'normal',
            'default'
        );
        add_meta_box(
            'pso_delayed_scripts_meta_box',
            'Page Specific Delayed Scripts',
            [$this, 'renderDelayedScriptsMetaBox'],
            ['post', 'page', 'product'],
            'normal',
            'default'
        );
    }

    public function renderPreloadMetaBox($post): void
    {
        wp_nonce_field('pso_preload_meta_box', 'pso_preload_meta_box_nonce');
        $value = get_post_meta($post->ID, 'pso_page_preload_images', true);
        ?>
        <textarea name="pso_page_preload_images" rows="5" style="width: 100%"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">Enter image URLs, one per line.</p>
        <?php
    }

    public function renderDelayedStylesMetaBox($post): void
    {
        wp_nonce_field('pso_delayed_styles_meta_box', 'pso_delayed_styles_meta_box_nonce');
        $value = get_post_meta($post->ID, 'pso_page_delayed_styles', true);
        ?>
        <textarea name="pso_page_delayed_styles" rows="10" style="width: 100%"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">Enter CSS handles or keywords/URLs to delay (one per line).</p>
        <?php
    }

    public function renderDelayedScriptsMetaBox($post): void
    {
        wp_nonce_field('pso_delayed_scripts_meta_box', 'pso_delayed_scripts_meta_box_nonce');
        $value = get_post_meta($post->ID, 'pso_page_delayed_scripts', true);
        ?>
        <textarea name="pso_page_delayed_scripts" rows="10" style="width: 100%"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">Enter script keywords/URLs to delay (one per line).</p>
        <?php
    }

    public function savePageSpeedMetaBoxes($post_id): void
    {
        $this->savePreloadMetaBoxData($post_id);
        $this->saveDelayedStylesMetaBoxData($post_id);
        $this->saveDelayedScriptsMetaBoxData($post_id);
    }

    private function savePreloadMetaBoxData($post_id): void
    {
        if (!isset($_POST['pso_preload_meta_box_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['pso_preload_meta_box_nonce'], 'pso_preload_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['pso_page_preload_images'])) {
            $data = sanitize_textarea_field($_POST['pso_page_preload_images']);
            update_post_meta($post_id, 'pso_page_preload_images', $data);
        }
    }

    private function saveDelayedStylesMetaBoxData($post_id): void
    {
        if (!isset($_POST['pso_delayed_styles_meta_box_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['pso_delayed_styles_meta_box_nonce'], 'pso_delayed_styles_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['pso_page_delayed_styles'])) {
            $data = sanitize_textarea_field($_POST['pso_page_delayed_styles']);
            update_post_meta($post_id, 'pso_page_delayed_styles', $data);
        }
    }

    private function saveDelayedScriptsMetaBoxData($post_id): void
    {
        if (!isset($_POST['pso_delayed_scripts_meta_box_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['pso_delayed_scripts_meta_box_nonce'], 'pso_delayed_scripts_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['pso_page_delayed_scripts'])) {
            $data = sanitize_textarea_field($_POST['pso_page_delayed_scripts']);
            update_post_meta($post_id, 'pso_page_delayed_scripts', $data);
        }
    }
}