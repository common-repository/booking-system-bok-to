<?php

if ( ! defined('ABSPATH')) {
    die;
}

function bokto_add_widget()
{
    global $wpdb;
    $url     = esc_url($wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_site_url';"));
    $new_url = substr($url, 0, strlen($url) - 1);

    wp_enqueue_script('bokto_widget_script', esc_attr($new_url) . "/widget/widget.min.js");

    ?>
    <div id="miniorders-widget-wrapper" style="display: none;">
        <div data-miniorders-widget-url="<?php echo esc_attr($new_url); ?>" onclick="event.preventDefault();
        miniordersStartWidget();" id="miniorders-widget-tab">
            <a id="miniorders-widget-tab-name" href="#"></a>
        </div>
        <iframe id="miniorders-iframe" width="0" height="0"></iframe>
        <div id="miniorders-widget-close" onclick="event.preventDefault(); miniordersStartWidget();">
            <div id="miniorders-widget-close-img"></div>
        </div>
    </div>
    <?php

}

function bokto_view_frame()
{
    global $wpdb;
    $url = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_site_url'");
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        if (isset($_POST['frame_menu_view'])) {
            ?>
            <div style="z-index: 999999; position: fixed; bottom: 0; top: 50px; left: 50px; right: 50px; background: #FFFFFF">
                <div style="width: 100%; height: 100%; background: #ffffff">
                    <div style="position: absolute; right: 15px; top: 15px; ">
                        <form method="post">
                            <button name="menu_but" type="submit" style="opacity: 0.5; background: #ffffff;">✖</button>
                        </form>

                    </div>
                    <div style="width: 100%; height: 100%;">
                        <iframe style="width: 100%; height: 100%;"
                                src="<?php echo esc_attr($url) . '?hideheader=1'; ?>">
                        </iframe>
                    </div>
                </div>
            </div>
            <?php
        } elseif ( ! empty($_POST['menu_but'])) return;
    }
}

function bokto_view_public()
{
    global $wpdb;
    $url = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_site_url'");
    $wm  = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_view_mode'");

    if ( ! empty($wm)) {

        if ($wm === 'widget' && ! empty($url)) {

            wp_deregister_style('bokto_style2');
            add_action('wp_footer', 'bokto_add_widget');

        } elseif ($wm == 'menu' && ! empty($url)) {

            add_action('wp_footer', 'bokto_enqueue_assets_isnt_admin');
            add_action('wp_footer', 'bokto_view_frame');

        }
    }
}

?>