<?php

if ( ! defined('WP_UNINSTALL_PLUGIN')) exit;

$settingOptions = array('bokto_calendar_next_appointment','bokto_calendar_appointment_today','bokto_calendar_date', 'bokto_api_key', 'bokto_site_url', 'bokto_view_mode', 'bokto_page_number_products', 'bokto_page_number_orders');

foreach ($settingOptions as $settingName) {
    delete_option($settingName);
}
