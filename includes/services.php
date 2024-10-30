<?php

if ( ! defined('ABSPATH')) {
    die;
}

function bokto_services_view()
{
    global $wpdb;

    $api_key = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_api_key'");

    if ( ! empty($api_key)) {

        $limit = 20;

        bokto_service_data_submit($limit);

        $services_list_store = [];
        bokto_service_list_info($api_key, $services_list_store);

        $page_count      = ceil(count($services_list_store) / $limit);
        $now_page_number = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_page_number_services'");

        if (empty($_POST['bokto-add_new_service']) || ! empty($_POST['bokto-come_back_to_services_list'])) {
            ?>
            <div>
                <div class="bokto-banner bokto-info-banner">
                    Here you can view information about your services,
                    change their sale statuses and add new ones
                </div>
                <div style="position: absolute; bottom: 37px; left: 300px">
                    <form method="post">
                        <?php echo esc_attr(count($services_list_store)); ?> items
                        <input type="submit" name="the_first_page" value="«">
                        <input type="submit" name="previous_page" value="‹">
                        <input style="text-align: center;" type="text" size="1" name="new_page_value" value="<?php
                        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                            if ($now_page_number == $_POST['new_page_value']) {
                                if ( ! empty($_POST['next_page'])) {
                                    if ($_POST['new_page_value'] < $page_count) {
                                        bokto_save_new_page_services((int)($_POST['new_page_value'] + 1));

                                    } else {
                                        bokto_save_new_page_services($page_count);

                                    }
                                } elseif ( ! empty($_POST['previous_page'])) {
                                    if ($_POST['new_page_value'] > 1) {
                                        bokto_save_new_page_services((int)($_POST['new_page_value'] - 1));

                                    } else {
                                        bokto_save_new_page_services(1);

                                    }
                                } elseif ( ! empty($_POST['the_first_page'])) {
                                    bokto_save_new_page_services(1);

                                } elseif ( ! empty($_POST['the_last_page'])) {
                                    bokto_save_new_page_services($page_count);

                                }
                            } elseif ( ! preg_match('/^[0-9]+$/', $_POST['new_page_value'])) {
                                bokto_save_new_page_services($now_page_number);

                            } elseif ( ! empty($_POST['new_page_value'])) {
                                if ($_POST['new_page_value'] >= 1 && $_POST['new_page_value'] <= $page_count) {
                                    bokto_save_new_page_services((int)$_POST['new_page_value']);

                                } elseif ($_POST['new_page_value'] < 1) {
                                    bokto_save_new_page_services(1);

                                } elseif ($_POST['new_page_value'] > $page_count) {
                                    bokto_save_new_page_services($page_count);

                                }
                            } else {
                                bokto_save_new_page_services($now_page_number);

                            }
                        } else {
                            bokto_save_new_page_services(1);

                        } ?>">
                        <span>of <?php echo esc_attr($page_count); ?></span>
                        <input type="submit" name="next_page" value="›">
                        <input type="submit" name="the_last_page" value="»">
                    </form>
                </div>

                <?php
                $page  = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_page_number_services'");
                $start = ($page - 1) * $limit;
                $res   = [];
                for ($j = $start; $j < $start + $limit; ++$j) {
                    if ( ! empty($services_list_store[ $j ])) {
                        $res += [$j => $services_list_store[ $j ]];
                    }
                }
                ?>

                <h2 class="bokto-h2">Services on your site:</h2>
                <form method="post">
                    <input class="bokto-but bokto-add-but" type="submit" name="bokto-add_new_service"
                           value="Add service">
                </form>
                <br>
                <table class="bokto-table">
                    <tr class="bokto-tr">
                        <th class="bokto-th">ON</th>
                        <th class="bokto-th">Name</th>
                        <th class="bokto-th">Price</th>
                    </tr>
                    <?php
                    for ($i = $start; $i < $start + $limit; ++$i) {
                        if ( ! empty($res[ $i ])) {
                            ?>
                            <tr class="bokto-tr"
                                style="border-bottom: 1px solid #ccc; vertical-align: text-top; transition: .3s linear;">
                                <td class="bokto-td" width="30"><input form="status_change"
                                                                       name="bokto-status_checkbox<?php echo esc_attr($i); ?>"
                                                                       type="checkbox" value="true"
                                        <?php if ( ! empty($res[ $i ]['on'])) {
                                            ?>
                                            checked
                                            <?php
                                        }
                                        ?>
                                    ></td>
                                <td class="bokto-td" width="150">
                                    <?php echo esc_attr($res[ $i ]['name']); ?>
                                </td>
                                <td class="bokto-td" width="80">
                                    <?php
                                    if ( ! empty($res[ $i ]['price'])) {
                                        echo esc_attr($res[ $i ]['price'] . " " . $res[ $i ]['currency']);
                                    } else echo "Free";
                                    ?>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </table>
                <br>
                <div style="position: absolute; bottom: 40px;">
                    <form id="status_change" method="post">
                        <input class="bokto-but bokto-save-but" type="submit" name="bokto-save_changes_in_services"
                               value="Save">
                    </form>
                </div>
            </div>
            <?php

        } elseif ( ! empty($_POST['bokto-add_new_service'])) {
            bokto_add_service();
        }
    } else {
        ?>
        <div class="bokto-banner bokto-attention-banner">
            Specify your API key in the Configuration tab!
        </div>
        <?php
    }
}

function bokto_service_list_info($api_key, &$services_list_store)
{
    $json_services_response = wp_remote_get('https://cloud.bok.to/api/v1/product/list?apiKey=' . $api_key);
    $json_services_res      = wp_remote_retrieve_body($json_services_response);

    $services = json_decode($json_services_res, true);

    for ($i = 0; $i < count($services['data']); ++$i) {
        $services_list_store += [
            $i => [
                "id"       => $services['data'][ $i ]['id'],
                "on"       => $services['data'][ $i ]['forSell'],
                "name"     => $services['data'][ $i ]['name'],
                "price"    => $services['data'][ $i ]['price'],
                "currency" => $services['data'][ $i ]['currency']['name']
            ]
        ];
    }
}

function bokto_save_new_page_services($page_num)
{
    global $wpdb;

    $wpdb->update($wpdb->options, ['option_value' => $page_num], ['option_name' => 'bokto_page_number_services']);
    $new_page_number = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_page_number_services'");
    echo esc_attr($new_page_number);
}

function bokto_add_service()
{
    $service_length = [15, 30, 45, 60, 90, 120];

    ?>
    <br>
    <form method="post">
        <input class="bokto-come_back_to" type="submit" name="bokto-come_back_to_services_list" value="← Back">
    </form>
    <h2 class="bokto-h2">Add service: </h2>
    <form enctype="multipart/form-data" method="post" id="add_service_form">
        <label for="service_type"><h3 class="bokto-h3">Service type*</h3></label>
        <select id="service_type" name="free-paid-service" onchange="view_price(this.value)">
            <option id="paid-type-service" value="paid">Paid</option>
            <option id="free-type-service" value="free">Free</option>
        </select>
        <label for="service_name"><h3 class='bokto-h3'>Service name*</h3></label>
        <input class="bokto-input_service" id="service_name" type="text" name="bokto-service_name"
               placeholder="Visit appointment" required><br>
        <label for="service_length"><h3 class="bokto-h3">Service length*</h3></label>
        <select id="service_length" name="bokto-service_length">
            <?php
            foreach ($service_length as $time) {
                echo "<option value='$time'>$time minutes</option>";
            }
            ?>
        </select>
        <label for="service_descrip"><h3 class='bokto-h3'>Short description*</h3></label>
        <input class="bokto-input_service" id="service_descrip" type="text" name="bokto-service_description"
               placeholder="Service description" required><br>
        <div id="bokto-service-price">
            <label for="service_price"><h3 class='bokto-h3'>Price*</h3></label>
            <input class="bokto-input_service" id="service_price" type="text" name="bokto-service_price"
                   placeholder="Service price" required><br>
        </div>
        <label for="service_photo"><h3 class='bokto-h3'>Service photo</h3></label>
        <input class="bokto-input_service" type="file" name="service_photo[]" multiple accept="image/*"><br><br>

        <input class="bokto-but bokto-reset-but" type='reset' name='res2' value="Reset">
        <input class="bokto-but bokto-add-but" type='submit' name='bokto-sub2' value="Add"><br><br>
    </form>
    <?php

}

function bokto_service_data_submit($limit)
{
    global $wpdb;

    $api_key = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_api_key';");

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if ( ! empty($_POST['bokto-save_changes_in_services'])) {

            $services_list_store = [];
            bokto_service_list_info($api_key, $services_list_store);

            $page  = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_page_number_services'");
            $start = ($page - 1) * $limit;
            $res   = [];
            for ($j = $start; $j < $start + $limit; ++$j) {
                if ( ! empty($services_list_store[ $j ])) {
                    $res += [$j => $services_list_store[ $j ]];
                }
            }

            for ($i = $start; $i < $start + $limit; ++$i) {
                if ( ! empty($res[ $i ])) {
                    if ($_POST[ 'bokto-status_checkbox' . $i ] === "true") {
                        $new_status = ['forSell' => true];

                    } else $new_status = ['forSell' => null];

                    $json_new_service_status = json_encode($new_status);

                    wp_remote_request('https://cloud.bok.to/api/v1/product/' . $res[ $i ]['id'] . '/status?apiKey=' . $api_key, [
                        'headers'     => ['Content-Type' => 'application/json; charset=utf-8'],
                        'body'        => $json_new_service_status,
                        'method'      => 'PUT',
                        'data_format' => 'body',
                    ]);

                }
            }
        } elseif ( ! empty($_POST['bokto-sub2'])) {
            $new_service = [
                "name"          => sanitize_text_field($_POST['bokto-service_name']),
                "serviceLength" => sanitize_text_field($_POST['bokto-service_length']),
                "description"   => sanitize_text_field($_POST['bokto-service_description']),
                "images"        => []
            ];

            if ($_POST['free-paid-service'] === "free") {
                $new_service['price'] = 0;
            } elseif ($_POST['free-paid-service'] === "paid") {
                if (preg_match('/^[0-9\.]+$/', $_POST['bokto-service_price'])) {
                    $new_service['price'] = sanitize_text_field($_POST['bokto-service_price']);
                } else {
                    echo "<h3 class='bokto-h3'>Invalid service price!</h3>";

                    return;
                }
            }

            for ($i = 0; $i < count($_FILES['service_photo']['tmp_name']); $i++) {

                $local_file = $_FILES['service_photo']['tmp_name'][ $i ]; //path to a local file on your server

                $post_fields = array(
                    'image' => $_FILES['service_photo']['tmp_name'][ $i ],
                );

                $file_content = fopen($_FILES['service_photo']['tmp_name'][ $i ], 'r');

                $boundary = wp_generate_password(24);

                $headers = array(
                    'content-type' => 'multipart/form-data; boundary=' . $boundary,
                );

                $payload = '';

                foreach ($post_fields as $name => $value) {
                    $payload .= '--' . $boundary;
                    $payload .= "\r\n";
                    $payload .= 'Content-Disposition: form-data; name="' . $name .
                        '"' . "\r\n\r\n";
                    $payload .= $value;
                    $payload .= "\r\n";
                }

                if ($local_file) {
                    $payload .= '--' . $boundary;
                    $payload .= "\r\n";
                    $payload .= 'Content-Disposition: form-data; name="' . 'image' .
                        '"; filename="' . $_FILES['service_photo']['tmp_name'][ $i ] . '"' . "\r\n";
                    $payload .= 'Content-Type: ' . $_FILES['service_photo']['type'][ $i ] . "\r\n";
                    $payload .= "\r\n";
                    $payload .= fread($file_content, $_FILES['service_photo']['size'][ $i ]);
                    $payload .= "\r\n";
                }

                fclose($file_content);

                $payload .= '--' . $boundary . '--';

                $response = wp_remote_post("https://cloud.bok.to/api/v1/product/upload-image?apiKey=$api_key",
                    array(
                        'headers' => $headers,
                        'body'    => $payload,
                    )
                );

                $json_new_image_response = wp_remote_retrieve_body($response);
                $new_image_response      = json_decode($json_new_image_response, true);

                $new_service['images'] += [$i => [
                    'image' => $new_image_response['data']['file']
                ]
                ];
            }

            $json_new_service = json_encode($new_service);

            wp_remote_request('https://cloud.bok.to/api/v1/product/add?apiKey=' . $api_key, [
                'headers'     => ['Content-Type' => 'application/json; charset=utf-8'],
                'body'        => $json_new_service,
                'method'      => 'POST',
                'data_format' => 'body',
            ]);
        }
    }
}

?>