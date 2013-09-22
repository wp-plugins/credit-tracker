<?php
/**
 * Plugin options page.
 *
 * @package   Credit_Tracker
 * @author    Labs64 <info@labs64.com>
 * @license   GPL-2.0+
 * @link      http://www.labs64.com
 * @copyright 2013 Labs64
 */


define('CT_OPTIONS', 'CT_OPTIONS');


if (is_admin()) {
    // Add the options page and menu item.
    add_action('admin_menu', 'add_plugin_page');
    add_action('admin_init', 'page_init');

    // Add an action link pointing to the options page.
    $plugin_basename = plugin_basename(plugin_dir_path(__FILE__) . 'credit-tracker.php');
    add_filter('plugin_action_links_' . $plugin_basename, 'add_action_links');

    // Load admin style sheet and JavaScript.
    add_action('admin_enqueue_scripts', 'enqueue_admin_styles');
    add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');
}

/**
 * Add settings action link to the plugins page.
 */
function add_action_links($links)
{
    return array_merge(
        array(
            'settings' => '<a href="' . admin_url('options-general.php?page=credit-tracker') . '">' . __('Settings', CT_SLUG) . '</a>'
        ),
        $links
    );
}

/**
 * Add options page
 */
function add_plugin_page()
{
    global $plugin_screen_hook_suffix;
    $plugin_screen_hook_suffix = add_options_page(
        __('Credit Tracker', CT_SLUG),
        __('Credit Tracker', CT_SLUG),
        'manage_options',
        CT_SLUG,
        'create_admin_page'
    );
}

/**
 * Register and enqueue admin-specific style sheet.
 *
 * @return    null    Return early if no settings page is registered.
 */
function enqueue_admin_styles()
{
    global $plugin_screen_hook_suffix;

    if (!isset($plugin_screen_hook_suffix)) {
        return;
    }

    $screen = get_current_screen();
    if ($screen->id == $plugin_screen_hook_suffix) {
        wp_enqueue_style(CT_SLUG . '-admin-styles', plugins_url('css/admin.css', __FILE__), array(), CT_VERSION);
    }

}

/**
 * Register and enqueue admin-specific JavaScript.
 *
 * @return    null    Return early if no settings page is registered.
 */
function enqueue_admin_scripts()
{
    global $plugin_screen_hook_suffix;

    if (!isset($plugin_screen_hook_suffix)) {
        return;
    }

    $screen = get_current_screen();
    if ($screen->id == $plugin_screen_hook_suffix) {
        wp_enqueue_script(CT_SLUG . '-admin-script', plugins_url('js/admin.js', __FILE__), array('jquery'), CT_VERSION);
    }

}

/**
 * Options page callback
 */
function create_admin_page()
{
    ?>
    <div class="wrap">
        <a href="http://www.labs64.com" target="_blank" class="icon-labs64 icon32"></a>

        <h2><?php _e('Credit Tracker by Labs64', CT_SLUG); ?></h2>

        <form method="post" action="options.php">
            <?php
            // This prints out all hidden setting fields
            settings_fields('CT_OPTIONS_GROUP');
            do_settings_sections(CT_SLUG);
            submit_button();
            ?>
        </form>

        <h3><?php _e('Feedback', CT_SLUG); ?></h3>

        <p><?php _e('Did you find a bug? Have an idea for a plugin? Please help us improve this plugin', CT_SLUG); ?>
            :</p>
        <ul>
            <li>
                <a href="https://github.com/Labs64/credit-tracker/issues"
                   target="_blank"><?php _e('Report a bug, or suggest an improvement', CT_SLUG); ?></a>
            </li>
            <li><a href="http://www.facebook.com/labs64" target="_blank"><?php _e('Like us on Facebook'); ?></a>
            </li>
            <li><a href="http://www.labs64.com/blog" target="_blank"><?php _e('Read Labs64 Blog'); ?></a></li>
        </ul>
    </div>
<?php
}

/**
 * Register and add settings
 */
function page_init()
{
    register_setting(
        'CT_OPTIONS_GROUP', // Option group
        CT_OPTIONS, // Option name
        'sanitize' // Sanitize
    );

    add_settings_section(
        'CT_COMMON_SETTINGS', // ID
        __('Credit Tracker Settings', CT_SLUG), // Title
        'print_common_section_info', // Callback
        CT_SLUG // Page
    );

    /*
    add_settings_field(
        'ct_id_number', // ID
        __('Number', SLUG), // Title
        'ct_text_field_callback', // Callback
        SLUG, // Page
        'CT_COMMON_SETTINGS',
        array(
            'id' => 'ct_id_number',
            'description' => '',
        )
    );
    */

    add_settings_field(
        'ct_copyright_format',
        __('Copyright format', CT_SLUG),
        'ct_text_field_callback',
        CT_SLUG,
        'CT_COMMON_SETTINGS',
        array(
            'id' => 'ct_copyright_format',
            'description' => __('%ident_nr%, %source%, %author%, %publisher%, %license% can be used as placeholders', CT_SLUG),
        )
    );
}

/**
 * Sanitize each setting field as needed
 *
 * @param array $input Contains all settings fields as array keys
 */
function sanitize($input)
{
    /*
    if (!is_numeric($input['ct_id_number']))
        $input['ct_id_number'] = '';
    */

    if (empty($input['ct_copyright_format'])) {
        if (is_admin()) {
            add_settings_error(CT_OPTIONS, 'empty-copyright-format', 'Please specify copyright format.');
        }
    } else {
        $input['ct_copyright_format'] = sanitize_text_field($input['ct_copyright_format']);
    }

    return $input;
}

/**
 * Print the Section text
 */
function print_common_section_info()
{
    print __('Enter your settings below:', CT_SLUG);
}

/**
 */
function ct_text_field_callback($args)
{
    $id = $args['id'];
    $description = $args['description'];
    $value = get_single_option($id);
    echo "<input type='text' id='$id' name='CT_OPTIONS[$id]' value='$value' class='regular-text' />";
    echo "<p class='description'>$description</p>";
}

/**
 * Returns default options.
 * If you override the options here, be careful to use escape characters!
 */
function get_default_options()
{
    $default_options = array(
        'ct_copyright_format' => '&copy; %author%'
    );
    return $default_options;
}

/**
 * Retrieves (and sanitises) options
 */
function get_options()
{
    $options = get_default_options();
    $stored_options = get_option(CT_OPTIONS);
    if (!empty($stored_options)) {
        sanitize($stored_options);
        $options = wp_parse_args($stored_options, $options);
    }
    update_option(CT_OPTIONS, $options);
    return $options;
}

/**
 * Retrieves single option
 */
function get_single_option($name)
{
    $options = get_options();
    return esc_attr($options[$name]);
}

/**
 * Returns available source names
 */
function ct_get_sources_array()
{
    $sources = Array(
        'Custom' => 'custom',
        'Fotolia' => 'Fotolia'
    );
    return $sources;
}

?>
