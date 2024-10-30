<?php
/*
 * Plugin Name:       Booking System - bok.to
 * Description:       Integrate menu to your website with bok.to!
 * Version:           1.0.1
 * Requires at least: 5.6.1
 * Requires PHP:      7.1
 * Author:            Getreve Ltd
 * Author URI:        https://getreve.com/
 * Text Domain:       bokto
 * Domain Path:       /lang
 * License:     GPLv2+

Booking System - bok.to is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Booking System - bok.to is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Booking System - bok.to. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
 */

if ( ! defined('ABSPATH')) {
    die;
}

require_once __DIR__ . '/includes/services.php';
require_once __DIR__ . '/includes/items_view.php';
require_once __DIR__ . '/includes/bookings.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/calendar.php';
require_once __DIR__ . '/includes/menu_or_widget.php';

function bokto_register_assets_is_admin()
{
    wp_register_style('bokto_style', plugins_url('admin/css/style.css', __FILE__), false, time());
    wp_register_script('bokto_script2', plugins_url('admin/js/service-type.js', __FILE__), false, time());
}

function bokto_register_assets_isnt_admin()
{
    wp_register_style('bokto_style2', plugins_url('admin/css/public.css', __FILE__), false, time());
    wp_register_script('bokto_script', plugins_url('admin/js/find_button_place.js', __FILE__), false, time());
}

function bokto_public_styles(){
}

function bokto_enqueue_assets_is_admin($hook)
{
    if ($hook != 'toplevel_page_bokto-config') {
        if ($hook != 'bok-to_page_services') {
            if ($hook != 'bok-to_page_bookings') {
                if ($hook != 'bok-to_page_calendar') {
                    wp_deregister_style('bokto_style');

                    return;
                }
            }
        }
    }
    if($hook == 'bok-to_page_services') {
        wp_enqueue_script('bokto_script2');
    }

    wp_enqueue_style('bokto_style');
}

function bokto_enqueue_assets_isnt_admin()
{
    wp_enqueue_style('bokto_style2');
    wp_enqueue_script('bokto_script');
}

function bokto_show_new_items()
{
    $title = 'Booking system configuration';
    if (current_user_can('manage_options')) {
        add_menu_page(
            esc_html__($title),
            esc_html__('Bok.to'),
            'manage_options',
            'bokto-config',
            'bokto_add_config',
            'dashicons-clipboard',
            3
        );

        add_submenu_page(
            'bokto-config',
            esc_html__($title),
            esc_html__('Configuration', 'bokto'),
            'manage_options',
            'bokto-config',
            'bokto_add_config'
        );

        add_submenu_page(
            'bokto-config',
            esc_html__('Services'),
            esc_html__('Services', 'bokto'),
            'manage_options',
            'services',
            'bokto_view_services'
        );

        add_submenu_page(
            'bokto-config',
            esc_html__('Bookings'),
            esc_html__('Bookings', 'bokto'),
            'manage_options',
            'bookings',
            'bokto_view_bookings'
        );

        add_submenu_page(
            'bokto-config',
            esc_html__('Calendar'),
            esc_html__('Calendar', 'bokto'),
            'manage_options',
            'calendar',
            'bokto_view_calendar'
        );
    }
}

if (is_admin()) {
    add_action('admin_enqueue_scripts', 'bokto_register_assets_is_admin');
    add_action('admin_enqueue_scripts', 'bokto_enqueue_assets_is_admin');
    add_action('admin_menu', 'bokto_show_new_items');
}

if ( ! is_admin()) {
    add_action('wp_enqueue_scripts', 'bokto_register_assets_isnt_admin');
    bokto_view_public();
}
