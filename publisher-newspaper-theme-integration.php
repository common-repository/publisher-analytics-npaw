<?php

/*
Plugin Name: Publisher Analytics Integration
Plugin URI: https://publisheranalytics.ai/
Description: Integrates Publisher Analytics into your WordPress.
Version: 1.1.0
Author: NPAW - Publisher Analytics
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Prevent direct file access

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Add settings menu

add_action('admin_menu', 'publisher_analytics_add_menu');

function publisher_analytics_add_menu() {
    add_options_page('Publisher Analytics Settings', 'Publisher Analytics', 'manage_options', 'publisher_analytics', 'publisher_analytics_options_page');
}

// Display settings page

function publisher_analytics_options_page() {
    ?>
    <div class="wrap">
        <h2>Publisher Analytics</h2>
        <form action="options.php" method="post">
            <?php settings_fields('publisher_analytics_options'); ?>
            <?php do_settings_sections('publisher_analytics'); ?>
            <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
        </form>
    </div>
    <?php
}

// Initialize settings

add_action('admin_init', 'publisher_analytics_admin_init');

function publisher_analytics_admin_init(){
    register_setting('publisher_analytics_options', 'publisher_analytics_options', 'publisher_analytics_options_validate');
    add_settings_section('publisher_analytics_main', 'Main Settings', 'publisher_analytics_section_text', 'publisher_analytics');
    add_settings_field('publisher_analytics_template', 'Select Template', 'publisher_analytics_setting_template', 'publisher_analytics', 'publisher_analytics_main');
    add_settings_field('publisher_analytics_account_code', 'Account Code', 'publisher_analytics_setting_account_code', 'publisher_analytics', 'publisher_analytics_main');
    add_settings_field('publisher_analytics_user_id', 'User ID', 'publisher_analytics_setting_user_id', 'publisher_analytics', 'publisher_analytics_main');
    add_settings_field('publisher_analytics_custom_selectors', 'Custom Selectors', 'publisher_analytics_setting_custom_selectors', 'publisher_analytics', 'publisher_analytics_main');
}

function publisher_analytics_section_text() {
    echo '<p>Enter your settings here.</p>';
}

function publisher_analytics_setting_template() {
    $options = get_option('publisher_analytics_options');
    $template = isset($options['template']) ? $options['template'] : '';
    ?>
    <select id='publisher_analytics_template' name='publisher_analytics_options[template]' onchange='showCustomFields(this.value)'>
        <option value='newspaper' <?php selected($template, 'newspaper'); ?>>Newspaper by tagDiv</option>
        <option value='custom' <?php selected($template, 'custom'); ?>>Custom</option>
    </select>

    <script>
        function showCustomFields(value) {
            var display = (value === 'custom') ? 'block' : 'none';
            document.getElementById('customSelectors').style.display = display;
        }
        document.addEventListener('DOMContentLoaded', function() {
            showCustomFields(document.getElementById('publisher_analytics_template').value);
        });
    </script>
    <?php
}

function publisher_analytics_setting_account_code() {
    $options = get_option('publisher_analytics_options');
    echo "<input id='publisher_analytics_account_code' name='publisher_analytics_options[account_code]' size='40' type='text' value='" . esc_attr($options['account_code']) . "' />";
}

function publisher_analytics_setting_user_id() {
    $options = get_option('publisher_analytics_options');
    echo "<input id='publisher_analytics_user_id' name='publisher_analytics_options[user_id]' size='40' type='text' value='" . esc_attr($options['user_id']) . "' />";
}

function publisher_analytics_setting_custom_selectors() {
    $options = get_option('publisher_analytics_options');
    $selectors = isset($options['custom_selectors']) ? $options['custom_selectors'] : [];
    echo "<div id='customSelectors' style='display:none;'>";
    echo "<p>Article Container: <input type='text' name='publisher_analytics_options[custom_selectors][article]' value='" . esc_attr($selectors['article']) . "' /></p>";
    echo "<p>Link: <input type='text' name='publisher_analytics_options[custom_selectors][link]' value='" . esc_attr($selectors['link']) . "' /></p>";
    echo "<p>Title: <input type='text' name='publisher_analytics_options[custom_selectors][title]' value='" . esc_attr($selectors['title']) . "' /></p>";
    echo "<p>Description: <input type='text' name='publisher_analytics_options[custom_selectors][description]' value='" . esc_attr($selectors['description']) . "' /></p>";
    echo "<p>Image: <input type='text' name='publisher_analytics_options[custom_selectors][image]' value='" . esc_attr($selectors['image']) . "' /></p>";
    echo "<p>Author: <input type='text' name='publisher_analytics_options[custom_selectors][author]' value='" . esc_attr($selectors['author']) . "' /></p>";
    echo "<p>Category: <input type='text' name='publisher_analytics_options[custom_selectors][category]' value='" . esc_attr($selectors['category']) . "' /></p>";
    echo "<p>Tag: <input type='text' name='publisher_analytics_options[custom_selectors][tag]' value='" . esc_attr($selectors['tag']) . "' /></p>";
    echo "</div>";
}

function publisher_analytics_options_validate($input) {
    $newinput['template'] = sanitize_text_field($input['template']);
    $newinput['account_code'] = sanitize_text_field($input['account_code']);
    $newinput['user_id'] = sanitize_text_field($input['user_id']);
    $newinput['custom_selectors'] = isset($input['custom_selectors']) ? array_map('sanitize_text_field', $input['custom_selectors']) : [];
    return $newinput;
}

// Enqueue scripts

add_action('wp_enqueue_scripts', 'publisher_analytics_enqueue_scripts');

function publisher_analytics_enqueue_scripts() {
    wp_enqueue_script('publisher_analytics_sdk', 'https://publisheranalytics.ai/publisher-sdk', [], null, true);
    wp_add_inline_script('publisher_analytics_sdk', publisher_analytics_get_inline_script());
}

function publisher_analytics_get_inline_script() {
    $options = get_option('publisher_analytics_options');
    $template = isset($options['template']) ? $options['template'] : '';
    $selectors = [
        'newspaper' => [
            'article' => '.td-module-container, .td_module_wrap',
            'link' => "a[rel='bookmark']",
            'title' => 'h3.entry-title, .tdb_module_title',
            'description' => 'div.td-excerpt',
            'image' => 'span.entry-thumb',
            'author' => 'span.td-post-author-name a, div.tdb_module_author_name a',
            'category' => 'a.td-post-category, a.tdb-module-term',
            'tag' => 'a.td-post-tag'
        ],
        'custom' => isset($options['custom_selectors']) ? $options['custom_selectors'] : []
    ];
    $currentSelectors = isset($selectors[$template]) ? $selectors[$template] : [];

    ob_start();
    ?>
    <!-- Publisher Analytics WordPress-Plugin Version 1.1.0 -->
    document.addEventListener('DOMContentLoaded', function() {
        const articles = document.querySelectorAll('<?php echo esc_js($currentSelectors['article']); ?>');
        articles.forEach(article => {
            const titleElement = article.querySelector('<?php echo esc_js($currentSelectors['title']); ?>');
            if (titleElement) {
                article.setAttribute('data-npaw-article', '');
                article.classList.add('data-npaw-article');
                article.querySelectorAll('<?php echo esc_js($currentSelectors['link']); ?>').forEach(link => {
                    link.setAttribute('data-npaw-article-url', '');
                    link.classList.add('data-npaw-article-url');
                });
                article.querySelectorAll('<?php echo esc_js($currentSelectors['title']); ?>').forEach(title => {
                    title.setAttribute('data-npaw-article-title', '');
                    title.classList.add('data-npaw-article-title');
                });
                article.querySelectorAll('<?php echo esc_js($currentSelectors['description']); ?>').forEach(description => {
                    description.setAttribute('data-npaw-article-description', '');
                    description.classList.add('data-npaw-article-description');
                });
                article.querySelectorAll('<?php echo esc_js($currentSelectors['image']); ?>').forEach(image => {
                    image.setAttribute('data-npaw-article-image', '');
                    image.classList.add('data-npaw-article-image');
                });
                article.querySelectorAll('<?php echo esc_js($currentSelectors['author']); ?>').forEach(author => {
                    author.setAttribute('data-npaw-article-author', '');
                    author.classList.add('data-npaw-article-author');
                });
                article.querySelectorAll('<?php echo esc_js($currentSelectors['category']); ?>').forEach(category => {
                    category.setAttribute('data-npaw-article-category', '');
                    category.classList.add('data-npaw-article-category');
                });
                article.querySelectorAll('<?php echo esc_js($currentSelectors['tag']); ?>').forEach(tag => {
                    tag.setAttribute('data-npaw-article-tag', '');
                    tag.classList.add('data-npaw-article-tag');
                });
            }
        });
        document.dispatchEvent(new CustomEvent('npawTagsAdded'));
    });

    function initializePublisherAnalytics() {
        var accountCode = '<?php echo esc_js($options['account_code']); ?>';
        var userId = '<?php echo esc_js($options['user_id']); ?>';
        var sdk = new PublisherAnalyticsSDK(accountCode, userId);
        sdk.setupExperiments();
    }

    document.addEventListener('npawTagsAdded', function() {
        initializePublisherAnalytics();
    });

    <?php
    return ob_get_clean();
}

?>