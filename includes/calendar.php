<?php

function bokto_weekday($year, $month, $day)
{
    if ($year % 4 == 0) {
        if ($month == 10) $month_code = 0;
        elseif ($month == 2 || $month == 8) $month_code = 2;
        elseif ($month == 3 || $month == 11) $month_code = 3;
        else $month_code = 6;

    } else {
        if ($month == 1 || $month == 10) $month_code = 0;
        elseif ($month == 8) $month_code = 2;
        elseif ($month == 2 || $month == 3 || $month == 11) $month_code = 3;
        else $month_code = 6;
    }

    if ($month == 5) $month_code = 1;
    elseif ($month == 6) $month_code = 4;
    elseif ($month == 9 || $month == 12) $month_code = 5;

    $year_code = 6 + ($year % 100) + ($year % 100) / 4;

    if (($day + $month_code + $year_code) % 7 == 0) return 7;
    else return ($day + $month_code + $year_code) % 7;
}

function bokto_change_appointments_info($appointment_info, $month){
    global $wpdb;
    if($month === date('n')){

        $next_app = [];
        $app_count = 0;
        foreach ($appointment_info as $app) {

            static $i = 0;
            if (date('Y-m-d H:i:s', strtotime($app['date'])) > date('Y-m-d H:i:s')) {
                $next_app += [$i => date('F j, Y, g:i a', strtotime($app['date']))];
            }
            $i++;

            if (date('Y-m-d', strtotime($app['date'])) === date('Y-m-d')) {
                $app_count++;
            }
        }

        if ( ! empty($next_app)) {
            sort($next_app);
            $wpdb->update($wpdb->options, ['option_value' => $next_app[0]], ['option_name' => 'bokto_calendar_next_appointment']);
        } else {
            $wpdb->update($wpdb->options, ['option_value' => "-"], ['option_name' => 'bokto_calendar_next_appointment']);
        }

        $wpdb->update($wpdb->options, ['option_value' => $app_count], ['option_name' => 'bokto_calendar_appointment_today']);
    }
}

function bokto_view_calendar()
{
    global $wpdb;

    $api_key = $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_api_key';");

    if ( ! empty($api_key)) {

        $months = [
            1  => "January",
            2  => "February",
            3  => "March",
            4  => "April",
            5  => "May",
            6  => "June",
            7  => "July",
            8  => "August",
            9  => "September",
            10 => "October",
            11 => "November",
            12 => "December"
        ];

        $month = date('n', strtotime($wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_calendar_date'")));

        $year = date('Y', strtotime($wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_calendar_date'")));

        bokto_change_calendar_month($year, $month);

        if ($month > 1 && $month <= 12) {
            $prev_month = $month - 1;
            $prev_year  = $year;
        } else {
            $prev_month = 12;
            $prev_year  = $year - 1;
        }
        if ($month == 12) {
            $next_month = 1;
            $next_year  = $year + 1;
        } else {
            $next_month = $month + 1;
            $next_year  = $year;
        }

        $day = 1;

        if ($month == 1 || $month == 3 || $month == 5 || $month == 7 || $month == 8 || $month == 10 || $month == 12) {
            $last_day_of_month = 31;

        } elseif ($month == 4 || $month == 6 || $month == 9 || $month == 11) {
            $last_day_of_month = 30;

        } else {
            if ($year % 4 == 0) {
                $last_day_of_month = 29;

            } else {
                $last_day_of_month = 28;

            }
        }

        $next_days = 1;

        $firstday = 1;

        $json_bookings_list_response = wp_remote_get('https://cloud.bok.to/api/v1/orders?apiKey=' . $api_key . '&page=1');
        $json_bookings_list          = wp_remote_retrieve_body($json_bookings_list_response);

        $bookings_list = json_decode($json_bookings_list, true);

        if(!empty($bookings_list['count'])) {
            $bookings_list_page_count = ceil($bookings_list['count'] / $bookings_list['limit']);

        }else $bookings_list_page_count = 1;

        $appointment_info = [];

        for ($page = 1; $page <= $bookings_list_page_count; $page++) {

            $json_bookings_list_response = wp_remote_get('https://cloud.bok.to/api/v1/orders?apiKey=' . $api_key . '&page=' . $page);
            $json_bookings_list          = wp_remote_retrieve_body($json_bookings_list_response);

            $bookings_list = json_decode($json_bookings_list, true);


            for ($appointment = 0; $appointment < $bookings_list['limit']; $appointment++) {
                if ( ! empty($bookings_list['data'][ $appointment ])) {
                    if ((date('n', strtotime($bookings_list['data'][ $appointment ]['order_date'])) == $month && date('Y', strtotime($bookings_list['data'][ $appointment ]['order_date'])) == $year) ||
                        (date('n', strtotime($bookings_list['data'][ $appointment ]['order_date'])) == $prev_month && date('Y', strtotime($bookings_list['data'][ $appointment ]['order_date'])) == $year) ||
                        (date('n', strtotime($bookings_list['data'][ $appointment ]['order_date'])) == $next_month && date('Y', strtotime($bookings_list['data'][ $appointment ]['order_date'])) == $year) ||
                        (date('n', strtotime($bookings_list['data'][ $appointment ]['order_date'])) == $prev_month && $prev_month == 12 && date('Y', strtotime($bookings_list['data'][ $appointment ]['order_date'])) == $prev_year) ||
                        (date('n', strtotime($bookings_list['data'][ $appointment ]['order_date'])) == $next_month && $next_month == 1 && date('Y', strtotime($bookings_list['data'][ $appointment ]['order_date'])) == $next_year)) {

                        $appointment_info += [
                            $appointment => [
                                'number'     => $bookings_list['data'][ $appointment ]['number'],
                                'email'      => $bookings_list['data'][ $appointment ]['email'],
                                'first_name' => $bookings_list['data'][ $appointment ]['first_name'],
                                'last_name'  => $bookings_list['data'][ $appointment ]['last_name'],
                                'phone'      => $bookings_list['data'][ $appointment ]['phone'],
                                'price'      => $bookings_list['data'][ $appointment ]['price'],
                                'currency'   => $bookings_list['data'][ $appointment ]['currency'],
                                'date'       => date('Y-m-d H:i:s', strtotime($bookings_list['data'][ $appointment ]['order_date'])),
                                'product'    => [
                                    'name'     => $bookings_list['data'][ $appointment ]['products'][0]['name'],
                                    'price'    => $bookings_list['data'][ $appointment ]['products'][0]['price'],
                                    'quantity' => $bookings_list['data'][ $appointment ]['products'][0]['quantity'],
                                    'currency' => $bookings_list['data'][ $appointment ]['products'][0]['currency']
                                ]
                            ]
                        ];
                    }
                }
            }
        }

        if (empty($_POST['bokto-appointment_number']) || ! empty($_POST['bokto-come_back_to_calendar'])) {
            ?>

            <div class="bokto-banner bokto-info-banner">
                Here you will find a Calendar that contain all your bookings.
                You can view information about your existing bookings and see details
            </div>

            <div class="bokto-calendar-appointments-info">
                <span>Appointments today</span>
                <p><?php
                    bokto_change_appointments_info($appointment_info, $month);
                    echo $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_calendar_appointment_today'")
                    ?>
            </div>

            <div class="bokto-calendar-appointments-info">
                <span>Next appointment</span>
                <p><?php
                    bokto_change_appointments_info($appointment_info, $month);
                    echo $wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_calendar_next_appointment'")
                    ?></p>
            </div>

            <div class="bokto-calendar">
                <div class="bokto-month">
                    <div class="bokto-date">
                        <span class="bokto-now-date"><b><?php echo esc_attr($months[ $month ] . " " . $year); ?></b></span>
                    </div>
                    <div class="bokto-calendar-navigation">
                        <form method="post">
                            <input class="bokto-button1" type="submit" value="<" name="bokto-prev-month">
                            <input class="bokto-button2" type="submit" value=">" name="bokto-next-month">
                            <input type="submit" name="bokto-present-month" value="Today">
                        </form>
                    </div>
                </div>
                <table class="bokto-calendar-table">
                    <tr class="bokto-weekdays">
                        <th>Mon</th>
                        <th>Tue</th>
                        <th>Wed</th>
                        <th>Thu</th>
                        <th>Fri</th>
                        <th>Sat</th>
                        <th>Sun</th>
                    </tr>
                    <div class="bokto-days">
                        <tr class="bokto-days">
                            <?php
                            $days_count = 1;

                            while ($days_count <= 7) {
                                if (bokto_weekday($year, $month, 1) == 1 || $firstday == bokto_weekday($year, $month, 1)) {
                                    if ($month == date('n') && $year == date('Y') && $day == date('j')) {
                                        echo "<td class='bokto-today'>" . esc_attr($day);
                                        bokto_view_apoointments($appointment_info, $month, $day);
                                        echo "</td>";
                                        $day++;

                                    } else {
                                        echo "<td>" . esc_attr($day);
                                        bokto_view_apoointments($appointment_info, $month, $day);
                                        echo "</td>";
                                        $day++;

                                    }

                                    ++$days_count;

                                } else {
                                    if ($prev_month == 1 || $prev_month == 3 || $prev_month == 5 || $prev_month == 7 || $prev_month == 8 || $prev_month == 10 || $prev_month == 12) {
                                        $days_prev_month = 31 - (bokto_weekday($year, $month, 1) - 2);

                                    } elseif ($prev_month == 4 || $prev_month == 6 || $prev_month == 9 || $prev_month == 11) {
                                        $days_prev_month = 30 - (bokto_weekday($year, $month, 1) - 2);

                                    } else {
                                        if ($year % 4 == 0) {
                                            $days_prev_month = 29 - (bokto_weekday($year, $month, 1) - 2);

                                        } else {
                                            $days_prev_month = 28 - (bokto_weekday($year, $month, 1) - 2);

                                        }
                                    }
                                    for ($last_days = 1; $last_days < bokto_weekday($year, $month, 1); ++$last_days) {
                                        echo "<td class='bokto-last-days'>" . esc_attr($days_prev_month);
                                        bokto_view_apoointments($appointment_info, $prev_month, $days_prev_month);
                                        echo "</td>";
                                        ++$days_prev_month;
                                        ++$firstday;
                                        ++$days_count;
                                    }
                                }
                            }
                            ?>
                        </tr>
                        <?php
                        for ($row = 0; $row < 5; $row++) {
                            ?>
                            <tr class="bokto-days">
                                <?php
                                bokto_calendar_general_rows($year, $month, $next_month, $day, $last_day_of_month, $next_days, $appointment_info);
                                ?>
                            </tr>
                            <?php
                        }
                        ?>
                    </div>
                </table>
            </div>
            <?php
        } elseif ( ! empty($_POST['bokto-appointment_number'])) {
            bokto_appointment_info($appointment_info, "come_back_to_calendar");

        }
    } else {
        ?>
        <div class="bokto-banner bokto-attention-banner">
            Specify your API key in the Configuration tab!
        </div>
        <?php
    }
}

function bokto_calendar_general_rows($year, $month, $next_month, &$day, $last_day_of_month, &$next_days, $appointment_info)
{
    $days_count = 1;
    while ($days_count <= 7) {
        if ($day <= $last_day_of_month) {
            if ($month == date('n') && $year == date('Y') && $day == date('j')) {
                echo "<td class='bokto-today'>" . esc_attr($day);
                bokto_view_apoointments($appointment_info, $month, $day);
                echo "</td>";
                $day++;

            } else {
                echo "<td>" . esc_attr($day);
                bokto_view_apoointments($appointment_info, $month, $day);
                echo "</td>";
                $day++;
            }
            ++$days_count;
        } else {
            echo "<td class='bokto-next-days'>" . esc_attr($next_days);
            bokto_view_apoointments($appointment_info, $next_month, $next_days);
            echo "</td>";
            $next_days++;
            ++$days_count;
        }
    }
}

function bokto_change_calendar_month(&$year, &$month)
{
    global $wpdb;

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        if ( ! empty($_POST['bokto-prev-month'])) {

            if ($month == 1) {
                $prev_month = 12;
                $prev_year  = $year - 1;

            } else {
                $prev_month = $month - 1;
                $prev_year  = $year;
            }

            $new_date = date('Y-n', strtotime((string)$prev_year . "-" . (string)$prev_month));

            $wpdb->update($wpdb->options, ['option_value' => $new_date], ['option_name' => 'bokto_calendar_date']);

        } elseif ( ! empty($_POST['bokto-next-month'])) {

            if ($month == 12) {
                $next_month = 1;
                $next_year  = $year + 1;

            } else {
                $next_month = $month + 1;
                $next_year  = $year;
            }

            $new_date = date('Y-n', strtotime((string)$next_year . "-" . (string)$next_month));

            $wpdb->update($wpdb->options, ['option_value' => $new_date], ['option_name' => 'bokto_calendar_date']);

        } elseif ( ! empty($_POST['bokto-present-month'])) {

            $wpdb->update($wpdb->options, ['option_value' => date('Y-n')], ['option_name' => 'bokto_calendar_date']);

        }

        $month = date('n', strtotime($wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_calendar_date'")));

        $year = date('Y', strtotime($wpdb->get_var("select option_value from $wpdb->options where option_name = 'bokto_calendar_date'")));
    }
}


function bokto_view_apoointments($appointments_array, $month, $day)
{
    foreach (array_reverse($appointments_array) as $app) {
        if (date('n', strtotime($app['date'])) == $month && date('j', strtotime($app['date'])) == $day) {
            ?>
            <form class="bokto-appointment" method="post">
                <button class="bokto-appointment" type="submit" name="bokto-appointment_number"
                        value="<?php echo "#" . esc_attr($app['number']); ?>"><?php echo esc_attr(date('H', strtotime($app['date'])) . " " . $app['product']['name']); ?></button>
            </form>
            <?php
        }
    }
}

function bokto_appointment_info($appointment_info, $back_to)
{
    foreach ($appointment_info as $app) {
        if ("#$app[number]" == $_POST['bokto-appointment_number']) {
            ?>
            <br>
            <form method="post">
                <input class="bokto-come_back_to" type="submit" name="bokto-<?php echo esc_attr($back_to); ?>"
                       value="← Back">
            </form>
            <h2 class="bokto-h2">Appointment <?php echo esc_attr($_POST['bokto-appointment_number']); ?></h2>
            <div>
                <div class="bokto-appointment-info">
                    <span class="bokto-info-header">Booking time</span>
                    <p>
                        <?php $date = $app['date'];
                        echo esc_attr(date("F j, Y, g:i a", strtotime($date))); ?>
                    </p>
                </div>
                <div class="bokto-appointment-info">
                    <span class="bokto-info-header">Service</span><br><br>
                    <table class="bokto-booking-info">
                        <tr class="bokto-tr">
                            <td class="bokto-td" width="100">
                                <b>
                                    <?php
                                    echo esc_attr($app['product']['name']);
                                    ?>
                                </b>
                            </td>
                            <td class="bokto-td">
                                <?php
                                if ( ! empty($app['product']['price'])) {
                                    echo esc_attr($app['product']['price'] . " " . $app['product']['currency']['name']);
                                } else echo "Free";
                                ?>
                            </td>
                        </tr>
                        <?php
                        if ($app['product']['price'] * $app['product']['quantity'] != $app['price']) {
                            echo "<tr class='bokto-tr'>";
                            echo "<td class='bokto-td'>Discount</td>";
                            echo "<td class='bokto-td'>";
                            echo esc_attr($app['product']['price'] * $app['product']['quantity'] - $app['price']) . " " . esc_attr($app['currency']['name']);
                            echo "</td></tr>";
                        }
                        ?>
                        <tr class="bokto-tr">
                            <td class="bokto-td">Total price:</td>
                            <td class="bokto-td">
                                <?php
                                if ( ! empty($app['price'])) {
                                    echo esc_attr($app['price'] . " " . $app['currency']['name']);
                                } else echo "Free";
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
                <br>
                <div class="bokto-client-info">
                    <span class="bokto-info-header">Client</span>
                    <?php
                    echo "<p>" . esc_attr($app['email']) . "</p>";
                    echo esc_attr($app['first_name']);
                    if ( ! empty($app['last_name'])) {
                        echo " " . esc_attr($app['last_name']);
                    }
                    echo "<br>" . esc_attr($app['phone']);
                    ?>
                </div>
            </div>
            <?php
        }
    }
}

?>