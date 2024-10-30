<?php

if ( ! defined('ABSPATH')) {
    die;
}

function ordto_products_view()
{
    global $wpdb;

    $api_key = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'ordto_api_key'");

    if ( ! empty($api_key)) {

        $limit = 20;

        ordto_product_data_submit($limit);

        $products_list_store = [];
        ordto_product_list_info($api_key, $products_list_store);

        $page_count      = ceil(count($products_list_store) / $limit);
        $now_page_number = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'ordto_page_number_products'");

        if (empty($_POST['ordto-add_new_product']) || ! empty($_POST['ordto-come_back_to_products_list'])) {
            ?>
            <div>
                <div class="ordto-banner ordto-info-banner">
                    Here you can view information about your products,
                    change their sale statuses and add new ones
                </div>
                <div style="position: absolute; bottom: 37px; left: 300px">
                    <form method="post">
                        <?php echo esc_attr(count($products_list_store)); ?> items
                        <input type="submit" name="the_first_page" value="«">
                        <input type="submit" name="previous_page" value="‹">
                        <input style="text-align: center;" type="text" size="1" name="new_page_value" value="<?php
                        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                            if ($now_page_number == $_POST['new_page_value']) {
                                if ( ! empty($_POST['next_page'])) {
                                    if ($_POST['new_page_value'] < $page_count) {
                                        ordto_save_new_page_products((int)($_POST['new_page_value'] + 1));

                                    } else {
                                        ordto_save_new_page_products($page_count);

                                    }
                                } elseif ( ! empty($_POST['previous_page'])) {
                                    if ($_POST['new_page_value'] > 1) {
                                        ordto_save_new_page_products((int)($_POST['new_page_value'] - 1));

                                    } else {
                                        ordto_save_new_page_products(1);

                                    }
                                } elseif ( ! empty($_POST['the_first_page'])) {
                                    ordto_save_new_page_products(1);

                                } elseif ( ! empty($_POST['the_last_page'])) {
                                    ordto_save_new_page_products($page_count);

                                }
                            } elseif ( ! preg_match('/^[0-9]+$/', $_POST['new_page_value'])) {
                                ordto_save_new_page_products($now_page_number);

                            } elseif ( ! empty($_POST['new_page_value'])) {
                                if ($_POST['new_page_value'] >= 1 && $_POST['new_page_value'] <= $page_count) {
                                    ordto_save_new_page_products((int)$_POST['new_page_value']);

                                } elseif ($_POST['new_page_value'] < 1) {
                                    ordto_save_new_page_products(1);

                                } elseif ($_POST['new_page_value'] > $page_count) {
                                    ordto_save_new_page_products($page_count);

                                }
                            } else {
                                ordto_save_new_page_products($now_page_number);

                            }
                        } else {
                            ordto_save_new_page_products(1);

                        } ?>">
                        <span>of <?php echo esc_attr($page_count); ?></span>
                        <input type="submit" name="next_page" value="›">
                        <input type="submit" name="the_last_page" value="»">
                    </form>
                </div>

                <?php
                $page = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'ordto_page_number_products'");
                $start = ($page - 1) * $limit;
                $res   = [];
                for ($j = $start; $j < $start + $limit; ++$j) {
                    if ( ! empty($products_list_store[ $j ])) {
                        $res += [$j => $products_list_store[ $j ]];
                    }
                }
                ?>

                <h2 class="ordto-h2">Products on your site:</h2>
                <form method="post">
                    <input class="ordto-but ordto-add-but" type="submit" name="ordto-add_new_product"
                           value="Add product">
                </form>
                <br>
                <table class="ordto-table">
                    <tr class="ordto-tr">
                        <th class="ordto-th">ON</th>
                        <th class="ordto-th">Name</th>
                        <th class="ordto-th">Price</th>
                    </tr>
                    <?php
                    for ($i = $start; $i < $start + $limit; ++$i) {
                        if ( ! empty($res[ $i ])) {
                            ?>
                            <tr class="ordto-tr"
                                style="border-bottom: 1px solid #ccc; vertical-align: text-top; transition: .3s linear;">
                                <td class="ordto-td" width="30"><input form="status_change"
                                                                       name="ordto-status_checkbox<?php echo esc_attr($i); ?>"
                                                                       type="checkbox" value="true"
                                        <?php if ( ! empty($res[ $i ]['on'])) {
                                            ?>
                                            checked
                                            <?php
                                        }
                                        ?>
                                    ></td>
                                <td class="ordto-td" width="150"><?php echo esc_attr($res[ $i ]['name']); ?></td>
                                <td class="ordto-td"
                                    width="80"><?php echo esc_attr($res[ $i ]['price'] . " " . $res[ $i ]['currency']); ?></td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </table>
                <br>
                <div style="position: absolute; bottom: 40px;">
                    <form id="status_change" method="post">
                        <input class="ordto-but ordto-save-but" type="submit" name="ordto-save_changes_in_products"
                               value="Save">
                    </form>
                </div>
            </div>
            <?php

        } elseif ( ! empty($_POST['ordto-add_new_product'])) {
            ordto_add_product();
        }
    } else {
        ?>
        <div class="ordto-banner ordto-attention-banner">
            Specify your API key in the Configuration tab!
        </div>
        <?php
    }
}

function ordto_product_list_info($api_key, &$products_list_store)
{
    $json_products_response = wp_remote_get('https://cloud.ord.to/api/v1/product/list?apiKey=' . $api_key);
    $json_products_res      = wp_remote_retrieve_body($json_products_response);

    $products = json_decode($json_products_res, true);

    for ($i = 0; $i < count($products['data']); ++$i) {
        $products_list_store += [
            $i => [
                "id"       => $products['data'][ $i ]['id'],
                "on"       => $products['data'][ $i ]['forSell'],
                "name"     => $products['data'][ $i ]['name'],
                "price"    => $products['data'][ $i ]['price'],
                "currency" => $products['data'][ $i ]['currency']['name']
            ]
        ];
    }
}

function ordto_save_new_page_products($page_num)
{
    global $wpdb;

    $wpdb->update($wpdb->options, ['option_value' => $page_num], ['option_name' => 'ordto_page_number_products']);
    $new_page_number = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'ordto_page_number_products'");
    echo esc_attr($new_page_number);
}

function ordto_add_product()
{
    ?>
    <br>
    <form method="post">
        <input class="ordto-come_back_to" type="submit" name="ordto-come_back_to_products_list"
               value="← Back">
    </form>
    <h2 class="ordto-h2">Add product: </h2>
    <form enctype="multipart/form-data" method="post">
        <label for="prod_name"><h3 class='ordto-h3'>Product name*</h3></label>
        <input class="ordto-input_prod" id="prod_name" type="text" name="ordto-product_name" placeholder="Country Pizza"
               required><br>
        <label for="prod_tagline"><h3 class='ordto-h3'>Tagline</h3></label>
        <input class="ordto-input_prod" id="prod_tagline" type="text" name="ordto-product_tagline"
               placeholder="Product tagline"><br>
        <label for="prod_descrip"><h3 class='ordto-h3'>Short description*</h3></label>
        <input class="ordto-input_prod" id="prod_descrip" type="text" name="ordto-product_description"
               placeholder="Product description" required><br>
        <label for="prod_price"><h3 class='ordto-h3'>Price*</h3></label>
        <input class="ordto-input_prod" id="prod_price" type="text" name="ordto-product_price"
               placeholder="Product price" required><br>
        <label for="product_photo"><h3 class='ordto-h3'>Product photo</h3></label>
        <input class="ordto-input_prod" type="file" name="product_photo[]" multiple accept="image/*"><br><br>

        <input class="ordto-but ordto-reset-but" type='reset' name='res2' value="Reset">
        <input class="ordto-but ordto-add-but" type='submit' name='ordto-sub2' value="Add"><br><br>
    </form>
    <?php
}

function ordto_product_data_submit($limit)
{
    global $wpdb;

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $api_key = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'ordto_api_key';");

        if ( ! empty($_POST['ordto-save_changes_in_products'])) {

            $products_list_store = [];
            ordto_product_list_info($api_key, $products_list_store);

            $page = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'ordto_page_number_products'");
            $start = ($page - 1) * $limit;
            $res   = [];
            for ($j = $start; $j < $start + $limit; ++$j) {
                if ( ! empty($products_list_store[ $j ])) {
                    $res += [$j => $products_list_store[ $j ]];
                }
            }

            for ($i = $start; $i < $start + $limit; ++$i) {
                if ( ! empty($res[ $i ])) {
                    if ($_POST[ 'ordto-status_checkbox' . $i ] === "true") {
                        $new_status = ['forSell' => true];

                    } else $new_status = ['forSell' => null];

                    $json_new_product_status = json_encode($new_status);

                    wp_remote_request('https://cloud.ord.to/api/v1/product/' . $res[ $i ]['id'] . '/status?apiKey=' . $api_key, [
                        'headers'     => ['Content-Type' => 'application/json; charset=utf-8'],
                        'body'        => $json_new_product_status,
                        'method'      => 'PUT',
                        'data_format' => 'body',
                    ]);

                }
            }
        } elseif ( ! empty($_POST['ordto-sub2'])) {
            if (preg_match('/^[0-9\.]+$/', $_POST['ordto-product_price'])) {
                $new_product = [
                    "name"        => sanitize_text_field($_POST['ordto-product_name']),
                    "tagline"     => sanitize_text_field($_POST['ordto-product_tagline']),
                    "description" => sanitize_text_field($_POST['ordto-product_description']),
                    "price"       => sanitize_text_field($_POST['ordto-product_price']),
                    "images"      => []
                ];

                for ($i = 0; $i < count($_FILES['product_photo']['tmp_name']); $i++) {

                    $local_file = $_FILES['product_photo']['tmp_name'][ $i ]; //path to a local file on your server

                    $post_fields = array(
                        'image' => $_FILES['product_photo']['tmp_name'][ $i ],
                    );

                    $file_content = fopen($_FILES['product_photo']['tmp_name'][ $i ], 'r');

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
                            '"; filename="' . $_FILES['product_photo']['tmp_name'][ $i ] . '"' . "\r\n";
                        $payload .= 'Content-Type: ' . $_FILES['product_photo']['type'][ $i ] . "\r\n";
                        $payload .= "\r\n";
                        $payload .= fread($file_content, $_FILES['product_photo']['size'][ $i ]);
                        $payload .= "\r\n";
                    }

                    fclose($file_content);

                    $payload .= '--' . $boundary . '--';

                    $response = wp_remote_post("https://cloud.ord.to/api/v1/product/upload-image?apiKey=$api_key",
                        array(
                            'headers' => $headers,
                            'body'    => $payload,
                        )
                    );

                    $json_new_image_response = wp_remote_retrieve_body($response);
                    $new_image_response      = json_decode($json_new_image_response, true);

                    $new_product['images'] += [$i => [
                        'image' => $new_image_response['data']['file']
                    ]
                    ];

                }

                $json_new_product = json_encode($new_product);

                wp_remote_request('https://cloud.ord.to/api/v1/product/add?apiKey=' . $api_key, [
                    'headers'     => ['Content-Type' => 'application/json; charset=utf-8'],
                    'body'        => $json_new_product,
                    'method'      => 'POST',
                    'data_format' => 'body',
                ]);
            } else echo "<h3 class='ordto-h3'>Invalid product price!</h3>";
        }
    }
}

?>