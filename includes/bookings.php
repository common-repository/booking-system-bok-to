<?php

if ( ! defined('ABSPATH')) {
    die;
}

function bokto_bookings_view()
{
    global $wpdb;

    $api_key = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_api_key';");

    $as_status = [
        1 => "New",
        4 => "Rejected",
        5 => "Canceled",
        6 => "To accept",
        7 => "Preparing",
        8 => "Scheduled",
        9 => "Approved"
    ];

    $as_payment_status = [
        1  => 'Waiting',
        2  => "Transfer",
        3  => "PayPal",
        4  => "Stripe",
        5  => "DotPay",
        6  => "Cash on visit",
        7  => "PayLane",
        8  => "Card on visit",
        9  => "P24",
        10 => "Status square"
    ];

    $appointment_info = [];

    if ( ! empty($api_key)) {

        bokto_save_booking_status();

        $json_bookings_list_response = wp_remote_get('https://cloud.bok.to/api/v1/orders?apiKey=' . $api_key . '&page=1');
        $json_bookings_list          = wp_remote_retrieve_body($json_bookings_list_response);

        $bookings_list = json_decode($json_bookings_list, true);

        if(!empty($bookings_list['count'])) {
            $bookings_list_page_count = ceil($bookings_list['count'] / $bookings_list['limit']);
        }else $bookings_list_page_count = 1;

        $now_page_number = (int)$wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_page_number_bookings'");


        if (empty($_POST['bokto-appointment_number']) || ! empty($_POST['bokto-come_back_to_bookings'])) {
            ?>

            <div>
                <div class="bokto-banner bokto-info-banner">
                    Here you can view information about your existing bookings,
                    see details and change booking status
                </div>
                <div style="position: absolute; bottom: 37px; left: 150px">
                    <form method="post">
                        <?php echo esc_attr($bookings_list['count']) ?> items
                        <input type="submit" name="the_first_page" value="«">
                        <input type="submit" name="previous_page" value="‹">
                        <input style="text-align: center;" type="text" size="1" name="new_page_value" value="<?php
                        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                            if ($now_page_number == (int)$_POST['new_page_value']) {
                                if ( ! empty($_POST['next_page'])) {
                                    if ($_POST['new_page_value'] < $bookings_list_page_count) {
                                        bokto_save_new_page_bookings((int)($_POST['new_page_value'] + 1));

                                    } else {
                                        bokto_save_new_page_bookings($bookings_list_page_count);

                                    }
                                } elseif ( ! empty($_POST['previous_page'])) {
                                    if ($_POST['new_page_value'] > 1) {
                                        bokto_save_new_page_bookings((int)($_POST['new_page_value'] - 1));

                                    } else {
                                        bokto_save_new_page_bookings(1);

                                    }
                                } elseif ( ! empty($_POST['the_first_page'])) {
                                    bokto_save_new_page_bookings(1);

                                } elseif ( ! empty($_POST['the_last_page'])) {
                                    bokto_save_new_page_bookings($bookings_list_page_count);

                                }

                                // new_page_value type check
                            } elseif ( ! preg_match('/^[0-9]+$/', $_POST['new_page_value'])) {
                                bokto_save_new_page_bookings($now_page_number);

                            } elseif ( ! empty($_POST['new_page_value'])) {
                                if ($_POST['new_page_value'] >= 1 && $_POST['new_page_value'] <= $bookings_list_page_count) {
                                    bokto_save_new_page_bookings((int)$_POST['new_page_value']);

                                } elseif ($_POST['new_page_value'] < 1) {
                                    bokto_save_new_page_bookings(1);

                                } elseif ($_POST['new_page_value'] > $bookings_list_page_count) {
                                    bokto_save_new_page_bookings($bookings_list_page_count);

                                }
                            } else {
                                bokto_save_new_page_bookings($now_page_number);

                            }
                        } else {
                            bokto_save_new_page_bookings(1);

                        } ?>">
                        <span>of <?php echo esc_attr($bookings_list_page_count); ?></span>
                        <input type="submit" name="next_page" value="›">
                        <input type="submit" name="the_last_page" value="»">
                    </form>
                </div>

                <h2 class="bokto-h2">Bookings on your site:</h2>
                <table class="bokto-table">
                    <tr class="bokto-tr">
                        <th class="bokto-th">No</th>
                        <th class="bokto-th">Appointment date</th>
                        <th class="bokto-th">Value</th>
                        <th class="bokto-th">Status</th>
                        <th class="bokto-th">Payment status</th>
                    </tr>
                    <?php

                    bokto_appontments_array($appointment_info, $as_status, $as_payment_status);

                    for ($i = 0; $i < count($appointment_info); ++$i) {
                        ?>
                        <div>
                            <tr class="bokto-tr"
                                style="border-bottom: 1px solid #ccc; vertical-align: text-top; transition: .3s linear;">
                                <td class="bokto-td" width="80">
                                    <form method="post"><input class="bokto-booking_number" type="submit"
                                                               name="bokto-appointment_number"
                                                               value="<?php echo "#" . esc_attr($appointment_info[$i]['number']); ?>">
                                    </form>
                                </td>
                                <td class="bokto-td"
                                    width="230"><?php $date = $appointment_info[ $i ]['date'];
                                    echo esc_attr(date("F j, Y, g:i a", strtotime($date))); ?></td>
                                <td class="bokto-td"
                                    width="100">
                                    <?php
                                    if ( ! empty($appointment_info[ $i ]['price'])) {
                                        echo esc_attr($appointment_info[ $i ]['price'] . " " . $appointment_info[ $i ]['currency']['name']);
                                    } else echo "Free";
                                    ?>
                                </td>
                                <td class="bokto-td" width="100"><select name="bokto-sel<?php echo esc_attr($i); ?>"
                                                                         form="booking_status_change">
                                        <?php
                                        for ($b = 1; $b <= 9; $b++) {
                                            if ( ! empty($as_status[ $b ])) {
                                                echo "<option value='$b'";
                                                if ($as_status[ $b ] == $appointment_info[ $i ]['status']) {
                                                    echo " selected";
                                                }
                                                echo ">" . esc_attr($as_status[ $b ]) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select></td>
                                <td class="bokto-td"
                                    width="200"><?php echo esc_attr($appointment_info[$i]['payment_status']); ?></td>
                            </tr>
                        </div>
                        <?php
                    }
                    ?>
                </table>
                <br>
                <div style="position: absolute; bottom: 40px;">
                    <form id="booking_status_change" method="post">
                        <input class="bokto-but bokto-save-but" type="submit" name="bokto-save_booking_status"
                               value="Save">
                    </form>
                </div>
            </div>
            <?php
        } elseif ( ! empty($_POST['bokto-appointment_number'])) {

            bokto_appontments_array($appointment_info, $as_status, $as_payment_status);
            bokto_appointment_info($appointment_info, "come_back_to_bookings");

        }
    } else {
        ?>
        <div class="bokto-banner bokto-attention-banner">
            Specify your API key in the Configuration tab!
        </div>
        <?php
    }
}

function bokto_save_new_page_bookings($page_num)
{
    global $wpdb;

    $wpdb->update($wpdb->options, ['option_value' => $page_num], ['option_name' => 'bokto_page_number_bookings']);
    $new_page_number = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_page_number_bookings'");
    echo esc_attr($new_page_number);
}

function bokto_save_booking_status()
{
    global $wpdb;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if ( ! empty($_POST['bokto-save_booking_status'])) {

            $api_key = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_api_key';");

            $page = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_page_number_bookings'");

            $json_bookings_list_response = wp_remote_get('https://cloud.bok.to/api/v1/orders?apiKey=' . $api_key . '&page=' . $page);
            $json_bookings_list          = wp_remote_retrieve_body($json_bookings_list_response);

            $bookings_list = json_decode($json_bookings_list, true);

            for ($i = 0; $i <= count($bookings_list['data']) - 1; ++$i) {
                $new_booking_status = ['orderStatus' => sanitize_text_field($_POST[ 'bokto-sel' . $i ])];

                $json_new_booking_status = json_encode($new_booking_status);

                wp_remote_request('https://cloud.bok.to/api/v1/order/' . $bookings_list['data'][ $i ]['id'] . '/status?apiKey=' . $api_key, [
                    'headers'     => ['Content-Type' => 'application/json; charset=utf-8'],
                    'body'        => $json_new_booking_status,
                    'method'      => 'PUT',
                    'data_format' => 'body',
                ]);
            }
        }
    }
}

function bokto_appontments_array(&$appointment_info, $as_status, $as_payment_status)
{
    global $wpdb;

    $api_key = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_api_key';");

    $page = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_page_number_bookings'");

    $json_bookings_list_response = wp_remote_get('https://cloud.bok.to/api/v1/orders?apiKey=' . $api_key . '&page=' . $page);
    $json_bookings_list          = wp_remote_retrieve_body($json_bookings_list_response);

    $bookings_list = json_decode($json_bookings_list, true);

//    echo "<pre>";
//    print_r($bookings_list);
//    echo "</pre>";

    $appointment_info = [];

    for ($appointment = 0; $appointment < $bookings_list['limit']; $appointment++) {
        if ( ! empty($bookings_list['data'][ $appointment ])) {

            $appointment_info += [
                $appointment => [
                    'number'         => $bookings_list['data'][ $appointment ]['number'],
                    'status'         => $as_status[ $bookings_list['data'][ $appointment ]['status'] ],
                    'payment_status' => $as_payment_status[ $bookings_list['data'][ $appointment ]['payment_status'] ],
                    'email'          => $bookings_list['data'][ $appointment ]['email'],
                    'first_name'     => $bookings_list['data'][ $appointment ]['first_name'],
                    'last_name'      => $bookings_list['data'][ $appointment ]['last_name'],
                    'phone'          => $bookings_list['data'][ $appointment ]['phone'],
                    'price'          => $bookings_list['data'][ $appointment ]['price'],
                    'currency'       => $bookings_list['data'][ $appointment ]['currency'],
                    'date'           => date('Y-m-d H:i:s', strtotime($bookings_list['data'][ $appointment ]['order_date'])),
                    'product'        => [
                        'name'     => $bookings_list['data'][ $appointment ]['products'][0]['name'],
                        'price'    => $bookings_list['data'][ $appointment ]['products'][0]['price'],
                        'quantity' => $bookings_list['data'][ $appointment ]['products'][0]['quantity'],
                        'currency' => $bookings_list['data'][ $appointment ]['products'][0]['currency']
                    ]
                ]
            ];
        }
    }

    static $bokto_calendar_appointment_count = 0;

    foreach($appointment_info as $app){
        if(date('Y-m-d',strtotime($app['date'])) === date('Y-m-d')){
            $bokto_calendar_appointment_count++;
        }
    }
}

?>