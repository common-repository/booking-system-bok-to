<?php

if ( ! defined('ABSPATH')) {
    die;
}

function bokto_config_view()
{
    $api_key   = "";
    $site_url  = "";
    $view_mode = "";

    bokto_post_config();
    bokto_config_values($api_key, $site_url, $view_mode);

    if (empty($api_key)) {
        ?>
        <div class="bokto-banner bokto-attention-banner">
            If you do not have an account at bok.to,
            then you can create one
            <a href="https://cloud.bok.to/register" target="_blank">here</a>
        </div>
        <?php
    }
    ?>
    <div class="bokto-banner bokto-info-banner">
        Here you can change all your configuration settings at one time,
        change your API key only,
        change your site URL and view mode only
    </div>

    <form method='post'>
        <label for="bokto-inp_api">
            <p> Input your API from bok.to -> Integrations -> API -> API key for this company:</p>
        </label>
        <input type="text" id="bokto-inp_api" name='bokto-api_key' size="40"
            <?php if (!empty($api_key)){
            ?> value="<?php echo esc_attr($api_key) ?>" <?php
               }else { ?>placeholder="API key" <?php } ?>autofocus>
        <br>
        <label for="bokto-url_site">
            <p> Input your site URL from bok.to -> Go to your page:</p>
        </label>
        <input type="text" id="bokto-url_site" name='bokto-site_url' size="40"
            <?php if (!empty($site_url)) {
                ?> value="<?php echo esc_attr($site_url) ?>" <?php
            } else { ?>placeholder="Site URL"<?php } ?> >
        <br>
        <p> Select a view mode:</p>
        <input id="bokto-menu" type="radio" name="bokto-menu/widget" value="menu"
            <?php if (!empty($view_mode)) {
                if ($view_mode == 'menu') {
                    ?> checked <?php
                }
            } ?> > <label for="bokto-menu">Book button</label>
        <br>
        <input id="bokto-widget" type="radio" name="bokto-menu/widget" value="widget"
            <?php if (!empty($view_mode)) {
                if ($view_mode == 'widget') {
                    ?> checked <?php
                }
            } ?> > <label for="bokto-widget"> Widget</label>
        <br>
        <br>
        <input class="bokto-but bokto-reset-but" type='reset' name='res1' value="Reset">
        <input class='bokto-but bokto-save-but' type='submit' name='sub1' value="Save">
    </form>
    <?php
}

function bokto_post_config()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        global $wpdb;

        $wpdb->insert($wpdb->options, ['option_name' => 'bokto_api_key']);
        $wpdb->insert($wpdb->options, ['option_name' => 'bokto_site_url']);
        $wpdb->insert($wpdb->options, ['option_name' => 'bokto_view_mode']);
        $wpdb->insert($wpdb->options, ['option_name' => 'bokto_page_number_services']);
        $wpdb->insert($wpdb->options, ['option_name' => 'bokto_page_number_bookings']);

        $wpdb->insert($wpdb->options, ['option_name' => 'bokto_calendar_date']);
        $wpdb->update($wpdb->options, ['option_value' => date('Y-m')], ['option_name' => 'bokto_calendar_date']);

        $wpdb->insert($wpdb->options, ['option_name' => 'bokto_calendar_appointment_today']);
        $wpdb->insert($wpdb->options, ['option_name' => 'bokto_calendar_next_appointment']);


        if ( ! empty($_POST['bokto-api_key']) && ! empty($_POST['bokto-site_url']) && ! empty($_POST['bokto-menu/widget'])) {
            if(preg_match('/^[a-z0-9]{32}+$/', $_POST['bokto-api_key']) && preg_match('%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?(/)$%i',$_POST['bokto-site_url'])){
                $wpdb->update($wpdb->options, ['option_value' => sanitize_key($_POST['bokto-api_key'])], ['option_name' => 'bokto_api_key']);
                $wpdb->update($wpdb->options, ['option_value' => esc_url_raw($_POST['bokto-site_url'])], ['option_name' => 'bokto_site_url']);
                $wpdb->update($wpdb->options, ['option_value' => sanitize_text_field($_POST['bokto-menu/widget'])], ['option_name' => 'bokto_view_mode']);

                echo "<h3 class='bokto-h3'>All configuration added successfully!</h3>";

            }elseif(!preg_match('/^[a-z0-9]{32}$/', $_POST['bokto-api_key']) && !preg_match('%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?(/)$%i',$_POST['bokto-site_url'])){
                echo "<h3 class='bokto-h3'>Invalid configuration!</h3>";

            }elseif(!preg_match('%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?(/)$%i',$_POST['bokto-site_url'])){
                echo "<h3 class='bokto-h3'>Invalid site URL!</h3>";

            }elseif (!preg_match('/^[a-z0-9]{32}$/', $_POST['bokto-api_key'])){
                echo "<h3 class='bokto-h3'>Invalid API key!</h3>";
            }

        } elseif (empty($_POST['bokto-api_key']) && ! empty($_POST['bokto-site_url']) && ! empty($_POST['bokto-menu/widget'])) {
            if(preg_match('%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?(/)$%i',$_POST['bokto-site_url'])){
                $wpdb->update($wpdb->options, ['option_value' => esc_url_raw($_POST['bokto-site_url'])], ['option_name' => 'bokto_site_url']);
                $wpdb->update($wpdb->options, ['option_value' => sanitize_text_field($_POST['bokto-menu/widget'])], ['option_name' => 'bokto_view_mode']);

                echo "<h3 class='bokto-h3'>Site configuration added successfully!</h3>";

            }elseif(!preg_match('%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?(/)$%i',$_POST['bokto-site_url'])) {
                echo "<h3 class='bokto-h3'>Invalid site URL!</h3>";
            }

        } elseif ( ! empty($_POST['bokto-api_key']) && empty($_POST['bokto-site_url']) && empty($_POST['bokto-menu/widget'])) {
            if(preg_match('/^[a-z0-9]{32}$/', $_POST['bokto-api_key'])){
                $wpdb->update($wpdb->options, ['option_value' => sanitize_key($_POST['bokto-api_key'])], ['option_name' => 'bokto_api_key']);
                echo "<h3 class='bokto-h3'>API key added successfully!</h3>";

            }elseif (!preg_match('/^[a-z0-9]{32}$/', $_POST['bokto-api_key'])){
                echo "<h3 class='bokto-h3'>Invalid API key!</h3>";
            }
        } else {

            return;
        }
    }
}

function bokto_config_values(&$api, &$url, &$wm)
{
    global $wpdb;

    $api = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_api_key'");
    $url = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_site_url'");
    $wm  = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_view_mode'");
}

?>
