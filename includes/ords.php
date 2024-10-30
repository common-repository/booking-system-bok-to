<?php

if ( ! defined('ABSPATH')) {
    die;
}

function ordto_orders_view()
{
    global $wpdb;

    $api_key = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'ordto_api_key';");

    if ( ! empty($api_key)) {

        ordto_save_order_status();

        $json_orders_list_response = wp_remote_get('https://cloud.ord.to/api/v1/orders?apiKey=' . $api_key . '&page=1');
        $json_orders_list          = wp_remote_retrieve_body($json_orders_list_response);

        $orders_list = json_decode($json_orders_list, true);

        $orders_list_page_count = ceil($orders_list['count'] / $orders_list['limit']);

        $json_order_type_response = wp_remote_get('https://cloud.ord.to/api/v1/order/type?apiKey=' . $api_key);
        $json_order_type          = wp_remote_retrieve_body($json_order_type_response);

        $order_type = json_decode($json_order_type, true);

        $as_status = [
            1 => "New",
            4 => "Rejected",
            5 => "Returned",
            6 => "To accept",
            7 => "Preparing",
            8 => "In delivery",
            9 => "Delivered"
        ];

        $as_payment_status = [
            1  => "Waiting",
            2  => "Transfer",
            3  => "PayPal",
            4  => "Credit Card",
            5  => "DotPay",
            6  => "Cash on delivery",
            7  => "PayLane",
            8  => "Card on delivery",
            9  => "P24",
            10 => "Square"
        ];

        $now_page_number = (int)$wpdb->get_var("select option_value from $wpdb->options where option_name = 'ordto_page_number_orders'");


        if (empty($_POST['ordto-order_number']) || ! empty($_POST['ordto-come_back_to_orders'])) {
            ?>

            <div>
                <div class="ordto-banner ordto-info-banner">
                    Here you can view information about your existing orders,
                    see details and change order status
                </div>
                <div style="position: absolute; bottom: 37px; left: 150px">
                    <form method="post">
                        <?php echo esc_attr($orders_list['count']) ?> items
                        <input type="submit" name="the_first_page" value="«">
                        <input type="submit" name="previous_page" value="‹">
                        <input style="text-align: center;" type="text" size="1" name="new_page_value" value="<?php
                        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                            if ($now_page_number == (int)$_POST['new_page_value']) {
                                if ( ! empty($_POST['next_page'])) {
                                    if ($_POST['new_page_value'] < $orders_list_page_count) {
                                        ordto_save_new_page_orders((int)($_POST['new_page_value'] + 1));

                                    } else {
                                        ordto_save_new_page_orders($orders_list_page_count);

                                    }
                                } elseif ( ! empty($_POST['previous_page'])) {
                                    if ($_POST['new_page_value'] > 1) {
                                        ordto_save_new_page_orders((int)($_POST['new_page_value'] - 1));

                                    } else {
                                        ordto_save_new_page_orders(1);

                                    }
                                } elseif ( ! empty($_POST['the_first_page'])) {
                                    ordto_save_new_page_orders(1);

                                } elseif ( ! empty($_POST['the_last_page'])) {
                                    ordto_save_new_page_orders($orders_list_page_count);

                                }

                                // new_page_value type check
                            } elseif ( ! preg_match('/^[0-9]+$/', $_POST['new_page_value'])) {
                                ordto_save_new_page_orders($now_page_number);

                            } elseif ( ! empty($_POST['new_page_value'])) {
                                if ($_POST['new_page_value'] >= 1 && $_POST['new_page_value'] <= $orders_list_page_count) {
                                    ordto_save_new_page_orders((int)$_POST['new_page_value']);

                                } elseif ($_POST['new_page_value'] < 1) {
                                    ordto_save_new_page_orders(1);

                                } elseif ($_POST['new_page_value'] > $orders_list_page_count) {
                                    ordto_save_new_page_orders($orders_list_page_count);

                                }
                            } else {
                                ordto_save_new_page_orders($now_page_number);

                            }
                        } else {
                            ordto_save_new_page_orders(1);

                        } ?>">
                        <span>of <?php echo esc_attr($orders_list_page_count); ?></span>
                        <input type="submit" name="next_page" value="›">
                        <input type="submit" name="the_last_page" value="»">
                    </form>
                </div>

                <h2 class="ordto-h2">Orders on your site:</h2>
                <table class="ordto-table">
                    <tr class="ordto-tr">
                        <th class="ordto-th">Type</th>
                        <th class="ordto-th">No</th>
                        <th class="ordto-th">Delivery date</th>
                        <th class="ordto-th">Value</th>
                        <th class="ordto-th">Status</th>
                        <th class="ordto-th">Payment status</th>
                    </tr>
                    <?php
                    $page = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'ordto_page_number_orders'");

                    $json_orders_list_response = wp_remote_get('https://cloud.ord.to/api/v1/orders?apiKey=' . $api_key . '&page=' . $page);
                    $json_orders_list          = wp_remote_retrieve_body($json_orders_list_response);

                    $orders_list = json_decode($json_orders_list, true);

                    for ($i = 0; $i <= count($orders_list['data']) - 1; ++$i) {

                        $json_order_info_response = wp_remote_get('https://cloud.ord.to/api/v1/order/' . $orders_list['data'][ $i ]['id'] . '?apiKey=' . $api_key);
                        $json_order_info          = wp_remote_retrieve_body($json_order_info_response);

                        $order_info = json_decode($json_order_info, true);
                        ?>
                        <div>
                            <tr class="ordto-tr"
                                style="border-bottom: 1px solid #ccc; vertical-align: text-top; transition: .3s linear;">
                                <td class="ordto-td" width="150">
                                    <div style="font-size: 10px;
                                            text-align: center;
                                            border-radius: 3px;
                                            color: #FFFFFF;
                                    <?php if ($as_status[ $orders_list['data'][ $i ]['status'] ] == "Delivered" || $as_status[ $orders_list['data'][ $i ]['status'] ] == "Rejected" || $as_status[ $orders_list['data'][ $i ]['status'] ] == "Returned") {
                                        echo "background-color: #1ab394;";
                                    } else echo "background-color: #ED5565;"; ?>
                                            ">
                                        <?php for ($j = 0; $j <= count($order_type['data']); $j++) {
                                            if ($order_type['data'][ $j ]['id'] == $order_info['data']['type']) {
                                                echo esc_attr($order_type['data'][ $j ]['name']);
                                            }
                                        } ?>
                                    </div>
                                </td>
                                <td class="ordto-td" width="80">
                                    <form method="post"><input class="ordto-order_number" type="submit"
                                                               name="ordto-order_number"
                                                               value="<?php echo "#" . esc_attr($order_info['data']['number']); ?>">
                                    </form>
                                </td>
                                <td class="ordto-td" width="300"><?php $date = $orders_list['data'][ $i ]['order_date'];
                                    echo esc_attr(date("F j, Y, g:i a", strtotime($date))); ?></td>
                                <td class="ordto-td"
                                    width="100"> <?php echo esc_attr($orders_list['data'][ $i ]['price'] . " " . $orders_list['data'][ $i ]['currency']['name']); ?></td>
                                <td class="ordto-td" width="100">
                                    <select name="ordto-sel<?php echo esc_attr($i); ?>"
                                            form="order_status_change">
                                        <?php
                                        for ($o = 1; $o <= 9; $o++) {
                                            if ( ! empty($as_status[ $o ])) {
                                                echo "<option value='$o'";
                                                if ($as_status[ $o ] == $as_status[ $orders_list['data'][ $i ]['status'] ]) {
                                                    echo " selected";
                                                }
                                                echo ">" . esc_attr($as_status[ $o ]) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td class="ordto-td"
                                    width="200">
                                    <select name="ordto-payment-status<?php echo esc_attr($i); ?>" form="order_status_change">
                                        <?php
                                        for ($ps = 1; $ps <= 10; $ps++) {
                                            echo "<option value='$ps'";
                                            if ($as_payment_status[ $ps ] == $as_payment_status[ $order_info['data']['payment_status'] ]) {
                                                echo " selected";
                                            }
                                            echo ">" . esc_attr($as_payment_status[ $ps ]) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                        </div>
                        <?php
                    }
                    ?>
                </table>
                <br>
                <div style="position: absolute; bottom: 40px;">
                    <form id="order_status_change" method="post">
                        <input class="ordto-but ordto-save-but" type="submit" name="ordto-save_order_status"
                               value="Save">
                    </form>
                </div>
            </div>
            <?php
        } elseif ( ! empty($_POST['ordto-order_number'])) {

            $page = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'ordto_page_number_orders'");

            $json_orders_list_response = wp_remote_get('https://cloud.ord.to/api/v1/orders?apiKey=' . $api_key . '&page=' . $page);
            $json_orders_list          = wp_remote_retrieve_body($json_orders_list_response);

            $orders_list = json_decode($json_orders_list, true);

            for ($i = 0; $i < count($orders_list['data']); ++$i) {

                $json_order_info_response = wp_remote_get('https://cloud.ord.to/api/v1/order/' . $orders_list['data'][ $i ]['id'] . '?apiKey=' . $api_key);
                $json_order_info          = wp_remote_retrieve_body($json_order_info_response);

                $order_info = json_decode($json_order_info, true);

                if ("#{$order_info['data']['number']}" == $_POST['ordto-order_number']) {
                    ?>
                    <br>
                    <form method="post">
                        <input class="ordto-come_back_to" type="submit" name="ordto-come_back_to_orders"
                               value="← Back">
                    </form>
                    <h2 class="ordto-h2">Order <?php echo esc_attr($_POST['ordto-order_number']); ?></h2>
                    <div>
                        <div style="display: inline-block;
                            float: left;
                            width: 300px;
                            background-color: #ffffff;
                            color: inherit;
                            padding: 15px 20px 20px 20px;
                            border-color: #e7eaec;
                            border-image: none;
                            border-style: solid solid none;
                            border-width: 1px 0;">
                            <span style="color: #66669d; font-size: 24px; font-weight: 700;">Client</span>
                            <?php
                            echo "<p>" . esc_attr($order_info['data']['email']) . "</p>";
                            echo esc_attr($order_info['data']['first_name']);
                            if ( ! empty($order_info['data']['last_name'])) {
                                echo " " . esc_attr($order_info['data']['last_name']);
                            }
                            if ($order_info['data']['type'] == 3) {
                                echo "<br>" . esc_attr($order_info['data']['table_number']);
                            }
                            if ($order_info['data']['type'] == 1) {
                                echo "<br>" . esc_attr($order_info['data']['shipment_city'] . ", " . $order_info['data']['shipment_street'] . ", " . $order_info['data']['shipment_hn']);
                            }
                            echo "<br>" . esc_attr($order_info['data']['phone']);
                            ?>
                        </div>
                        <div class="ordto-client-info">
                            <span style="color: #66669d; font-size: 24px; font-weight: 700;">Order</span><br><br>
                            <table class="ordto-order-info">
                                <?php
                                for ($k = 0; $k < count($order_info['data']['products']); $k++) {
                                    $prod_price = $order_info['data']['products'][ $k ]['price'];
                                    for ($j = 0; $j < count($order_info['data']['products'][ $k ]['additions']); $j++) {
                                        $prod_price -= $order_info['data']['products'][ $k ]['additions'][ $j ]['price'];
                                    }
                                    ?>
                                    <tr class="ordto-tr" style="border-bottom: 1px solid #ccc; vertical-align: text-top;
                                                  transition: .3s linear;">
                                        <td class="ordto-td" width="300" style="font-size: 14px;">
                                            <b><?php echo esc_attr($order_info['data']['products'][ $k ]['name']) . "</b>";
                                                if ( ! empty($order_info['data']['products'][ $k ]['additions'])){
                                                echo esc_attr(" (" . $prod_price . " " . $order_info['data']['products'][ $k ]['currency']['name'] . ")");
                                                if ( ! empty($order_info['data']['products'][ $k ]['additions'])) {
                                                ?>
                                                <br><span style="font-size: 14px; color: #8f908d">Addons:
                                            <?php
                                            for ($j = 0; $j < count($order_info['data']['products'][ $k ]['additions']); $j++) {
                                                if ($j > 0) {
                                                    echo ", ";
                                                }
                                                echo esc_attr($order_info['data']['products'][ $k ]['additions'][ $j ]['name'] . " (+");
                                                echo esc_attr($order_info['data']['products'][ $k ]['additions'][ $j ]['price'] . " ");
                                                echo esc_attr($order_info['data']['products'][ $k ]['currency']['name'] . ")");
                                            }
                                            echo "</span>";
                                            }
                                            }
                                            ?></td>
                                        <td class="ordto-td"
                                            style="width: 50px;"><?php echo esc_attr($order_info['data']['products'][ $k ]['quantity']) ?></td>
                                        <td class="ordto-td"
                                            style="width: 200px;"><?php echo esc_attr($order_info['data']['products'][ $k ]['price'] . " " . $order_info['data']['currency']['name']); ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                <tr class="ordto-tr" style="border-bottom: 1px solid #ccc;
                                    transition: .3s linear;">
                                    <td class="ordto-td">
                                        <?php
                                        if ($order_info['data']['type'] == 1) {
                                            echo "Delivery price:";
                                        } else {
                                            echo "Packaging cost:";
                                        }
                                        ?>
                                    </td>
                                    <td class="ordto-td"></td>
                                    <td class="ordto-td"><?php echo esc_attr($order_info['data']['shippment_price'] . " " . $order_info['data']['currency']['name']); ?></td>
                                </tr>
                                <tr class="ordto-tr">
                                    <td class="ordto-td">Total price:</td>
                                    <td class="ordto-td"></td>
                                    <td class="ordto-td"><?php echo esc_attr($order_info['data']['price'] . " " . $order_info['data']['currency']['name']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <?php
                }
            }
        }
    } else {
        ?>
        <div class="ordto-banner ordto-attention-banner">
            Specify your API key in the Configuration tab!
        </div>
        <?php
    }
}

function ordto_save_new_page_orders($page_num)
{
    global $wpdb;

    $wpdb->update($wpdb->options, ['option_value' => $page_num], ['option_name' => 'ordto_page_number_orders']);
    $new_page_number = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'ordto_page_number_orders'");
    echo esc_attr($new_page_number);
}

function ordto_save_order_status()
{
    global $wpdb;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if ( ! empty($_POST['ordto-save_order_status'])) {

            $api_key = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'ordto_api_key';");

            $page = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'ordto_page_number_orders'");

            $json_orders_list_response = wp_remote_get('https://cloud.ord.to/api/v1/orders?apiKey=' . $api_key . '&page=' . $page);
            $json_orders_list          = wp_remote_retrieve_body($json_orders_list_response);

            $orders_list = json_decode($json_orders_list, true);

            for ($i = 0; $i < count($orders_list['data']); ++$i) {

                $new_order_status = ['orderStatus' => sanitize_text_field($_POST[ 'ordto-sel' . $i ])];

                $json_new_order_status = json_encode($new_order_status);

                $new_order_payment_status = ['paymentStatus' => sanitize_text_field($_POST[ 'ordto-payment-status' . $i ])];

                $json_new_order_payment_status = json_encode($new_order_payment_status);

                wp_remote_request('https://cloud.ord.to/api/v1/order/' . $orders_list['data'][ $i ]['id'] . '/status?apiKey=' . $api_key, [
                    'headers'     => ['Content-Type' => 'application/json; charset=utf-8'],
                    'body'        => $json_new_order_status,
                    'method'      => 'PUT',
                    'data_format' => 'body',
                ]);

                wp_remote_request('https://cloud.ord.to/api/v1/order/' . $orders_list['data'][ $i ]['id'] . '/payment-status?apiKey=' . $api_key, [
                    'headers'     => ['Content-Type' => 'application/json; charset=utf-8'],
                    'body'        => $json_new_order_payment_status,
                    'method'      => 'PUT',
                    'data_format' => 'body',
                ]);
            }
        }
    }
}

?>