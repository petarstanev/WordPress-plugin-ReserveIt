<?php

global $wpdb;
$reservations_any_tables_any_restaurant = $wpdb->get_var(
        "SELECT COUNT(*)
  FROM {$wpdb->prefix}reserveit_restaurants"
);
echo "<div class='wrap'>";
if ($reservations_any_tables_any_restaurant == 0) {
    echo "<h2> No restaurants in database please add new in 'Restaurants and Tables' menu .</h2>";
    exit;
}

echo "<h2>Reservations</h2>";
echo "<form id='reserveit_admin_form' method='post'>";
echo "<p>Restaurant :";
if (!empty($_POST['reserveit_admin_restaurant_select'])) {
    $restaurant_id = $_POST ['reserveit_admin_restaurant_select'];
} else {
    $restaurant_id = 0;
}

echo "<select name=reserveit_admin_restaurant_select  id =reserveit_admin_restaurant_select>";
global $wpdb;
$all_restaurants = $wpdb->get_results(
        "SELECT *
                                 FROM {$wpdb->prefix}reserveit_restaurants
                                 ORDER BY {$wpdb->prefix}reserveit_restaurants.id;   
                                 ");

foreach ($all_restaurants as $restaurant) {
    if ($restaurant->id == $restaurant_id) {
        echo "<option value= $restaurant->id selected='selected'> $restaurant->address </option>";
    } else {
        echo "<option value= $restaurant->id > $restaurant->address </option>";
    }
}
echo "</select>";
if (!empty($_POST['reserveit_admin_datepicker'])) {
    echo "Date :<input type='text' id='datepicker' value=" . $_POST['reserveit_admin_datepicker'] . " name='reserveit_admin_datepicker'/>";
} else {
    echo "Date :<input type='text' id='datepicker' value=" . date("d-m-Y") . " name='reserveit_admin_datepicker'/>";
}

echo "<input type='submit'  class = 'button-primary' name='reserveit_admin_submit' id='reserveit_admin_submit'></p>";
echo "</form>";

function reserveit_restaurant_workingtime($restaurant_id) {
    global $wpdb;
    $all_restaurants = $wpdb->get_results(
            "SELECT start_time, end_time
                                 FROM {$wpdb->prefix}reserveit_restaurants
                                 WHERE {$wpdb->prefix}reserveit_restaurants.id = '$restaurant_id'");


    return $all_restaurants;
}

if (isset($_POST ['reserveit_admin_submit']) && !empty($_POST ['reserveit_admin_datepicker'])) {
    $input_date = $_POST ['reserveit_admin_datepicker'];
    $restaurant_id = $_POST ['reserveit_admin_restaurant_select'];

    reserveit_print_start_of_table($input_date, $restaurant_id);
} else {
    $input_date = date('d-m-Y');

    global $wpdb;
    $restaurant_id = $wpdb->get_var(
            "SELECT id
            FROM {$wpdb->prefix}reserveit_restaurants
            ORDER BY {$wpdb->prefix}reserveit_restaurants.id
            LIMIT 1"
    );

    reserveit_print_start_of_table($input_date, $restaurant_id);
}

function reserveit_print_start_of_table($input_date, $restaurant_id) {
    global $wpdb;
    $reservations_any_tables = $wpdb->get_var(
            "SELECT COUNT(*)
  FROM {$wpdb->prefix}reserveit_tables
  WHERE {$wpdb->prefix}reserveit_tables.restaurant_id='$restaurant_id'"
    );

    if ($reservations_any_tables == 0) {
        echo "<h2> No tables for this restaurant.</h2>";
        exit;
    }

    global $wpdb;
    $restaurant_address = $wpdb->get_var("SELECT address
            FROM {$wpdb->prefix}reserveit_restaurants
            WHERE id = '$restaurant_id'"
    );

    $working_time = reserveit_restaurant_workingtime($restaurant_id);

    //   echo "<h2>Reservations for   $input_date in  $restaurant_address </h2>";
    echo "<h4>Working time from  " . date("H:i", strtotime($working_time[0]->start_time)) . "  to " . date("H:i", strtotime($working_time[0]->end_time)) . "</h4>";
    echo "<table>";
    echo "<thead>";
    echo "<tr>";
    echo "<td>Time</td>";
    echo "<td>Free</td>";
    echo "<td>Busy</td>";
    echo "<td>Prepare</td>";
    echo "</tr>";
    echo "</thead>";
    reserveit_all_reservations_now($input_date, $restaurant_id);
}

function reserveit_all_reservations_now($datetime, $restaurant_id) {

    function reserveit_free_tables($datetime, $restaurant_id) {
        global $wpdb;
        $reservations_free_tables = $wpdb->get_results(
                "SELECT {$wpdb->prefix}reserveit_tables.user_table_id
  FROM {$wpdb->prefix}reserveit_tables
  WHERE {$wpdb->prefix}reserveit_tables.id
  NOT IN (SELECT table_id
                FROM {$wpdb->prefix}reserveit_reservations
                WHERE {$wpdb->prefix}reserveit_reservations.restaurant_id ='$restaurant_id'
                AND {$wpdb->prefix}reserveit_reservations.start_datetime - INTERVAL 30 MINUTE <= '$datetime' 
                AND {$wpdb->prefix}reserveit_reservations.confirmed = 1
                AND {$wpdb->prefix}reserveit_reservations.start_datetime + INTERVAL 2 HOUR >= '$datetime')
  AND {$wpdb->prefix}reserveit_tables.restaurant_id ='$restaurant_id'
  ORDER BY {$wpdb->prefix}reserveit_tables.id"
        );

        return $reservations_free_tables;
    }

    function reserveit_busy_tables($datetime, $restaurant_id) {
        global $wpdb;

        $reservations_used_tables = $wpdb->get_results(
                "SELECT {$wpdb->prefix}reserveit_tables.user_table_id
  FROM {$wpdb->prefix}reserveit_tables
  WHERE {$wpdb->prefix}reserveit_tables.id
 IN (SELECT table_id
                    FROM {$wpdb->prefix}reserveit_reservations
                    WHERE {$wpdb->prefix}reserveit_reservations.restaurant_id ='$restaurant_id'
                    AND {$wpdb->prefix}reserveit_reservations.start_datetime <= '$datetime'
                    AND {$wpdb->prefix}reserveit_reservations.start_datetime + INTERVAL 2 HOUR >= '$datetime'
                    AND {$wpdb->prefix}reserveit_reservations.confirmed = 1 )
  AND {$wpdb->prefix}reserveit_tables.restaurant_id ='$restaurant_id'
  ORDER BY {$wpdb->prefix}reserveit_tables.id"
        );

        return $reservations_used_tables;
    }

    function reserveit_prepare_tables($datetime, $restaurant_id) {
        global $wpdb;

        $reservations_prepare_tables = $wpdb->get_results(
                "SELECT {$wpdb->prefix}reserveit_tables.user_table_id
                    FROM {$wpdb->prefix}reserveit_tables
                    WHERE {$wpdb->prefix}reserveit_tables.id
                    IN (SELECT table_id
                        FROM {$wpdb->prefix}reserveit_reservations
                        WHERE {$wpdb->prefix}reserveit_reservations.restaurant_id ='$restaurant_id'
                        AND {$wpdb->prefix}reserveit_reservations.start_datetime  = '$datetime' + INTERVAL 30 MINUTE
                         AND {$wpdb->prefix}reserveit_reservations.confirmed = 1)
                    AND {$wpdb->prefix}reserveit_tables.restaurant_id ='$restaurant_id'
                    ORDER BY {$wpdb->prefix}reserveit_tables.id"
        );
        //  var_dump($reservations_prepare_tables);
        return $reservations_prepare_tables;
    }

    function reserveit_print($reservations_tables) {
        $first = true;
        echo "<td>";
        //   var_dump($reservations_tables);
        foreach ($reservations_tables as $reservation_table) {

            //       var_dump($reservation_table);
            if ($first == false) {
                echo ",";
            }
            $first = false;

            echo $reservation_table->user_table_id;
        }
        echo "</td>";
    }

    function reserveit_get_working_hours($restaurant_id) {
        global $wpdb;

        $working_time = $wpdb->get_results(
                "SELECT {$wpdb->prefix}reserveit_restaurants.start_time, {$wpdb->prefix}reserveit_restaurants.end_time
  FROM {$wpdb->prefix}reserveit_restaurants
  WHERE {$wpdb->prefix}reserveit_restaurants.id= '$restaurant_id' "
        );

        foreach ($working_time as $working_time_restaurant) {
            $sql_start_working = $working_time_restaurant->start_time;
            $sql_end_working = $working_time_restaurant->end_time;
            
            
            //removing secounds
            $start_working = date("H:i", strtotime($sql_start_working));
            $end_working = date("H:i", strtotime($sql_end_working));

            $all_working_hours = array();
            while (date("H:i", strtotime($start_working)) != $end_working) {
                //var_dump($start_working);
                $all_working_hours[] = $start_working;
                $start_working = date("H:i", strtotime($start_working . '+30 minutes'));
            }
            
            return $all_working_hours;
        }
    }

    if (!empty($_POST ['reserveit_admin_datepicker'])) {
        $input_date = $_POST ['reserveit_admin_datepicker'];
    } else {
        $input_date = date('d-m-Y');
    }
    $all_working_hours = reserveit_get_working_hours($restaurant_id);
    $minutes_now = (date('i') > 30) ? '30' : '00';
    $hours_now = date('H');
    $time_now = $hours_now . ":" . $minutes_now;

    $i = 0;
    foreach ($all_working_hours as $hour) {

        $datetime = date("Y-m-d H-i", strtotime($input_date . $hour));
        if ($time_now === $hour && $input_date == date('d-m-Y')) {
            echo "<tr class='timenow'>";
        } else if ($i % 2 != 0) {
            echo "<tr class='firstcolor'>";
        } else {
            echo "<tr class='secondcolor'>";
        }
        $i++;

        echo "<td>" . $hour . "</td>";

        reserveit_print(reserveit_free_tables($datetime, $restaurant_id));
        reserveit_print(reserveit_busy_tables($datetime, $restaurant_id));
        reserveit_print(reserveit_prepare_tables($datetime, $restaurant_id));

        echo "</tr>";
    }
    echo "</table>";
}
