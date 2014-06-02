<?php

echo "<div class='wrap'>";
global $wpdb;
$reservations_any_tables_any_restaurant = $wpdb->get_var(
        "SELECT COUNT(*)
  FROM {$wpdb->prefix}reserveit_restaurants"
);

if ( $reservations_any_tables_any_restaurant == 0 ) {
    echo "<h2> No restaurants in database please add new in 'Restaurants and Tables' menu .</h2>";
    exit;
}
reserveit_expand_reservations();
reserveit_user_form();

echo "</div>";

function reserveit_expand_reservations() {

    function reserveit_expand_form() {
        echo "<form method='post' name='expand_reservations' action=''>";

        if ( !empty( $_POST['reserveit_expand_datepicker'] ) ) {
            $input_date = $_POST ['reserveit_expand_datepicker'];
        } else {
            $input_date = date( 'd-m-Y' );
        }

        if ( !empty( $_POST['reserveit_admin_restaurant_select'] ) ) {
            $restaurant_id = $_POST ['reserveit_admin_restaurant_select'];
            global $wpdb;
            $restaurant_address = $wpdb->get_var(
                    "SELECT {$wpdb->prefix}reserveit_restaurants.address
                      FROM {$wpdb->prefix}reserveit_restaurants
                      WHERE {$wpdb->prefix}reserveit_restaurants.id = '$restaurant_id'" );
        } else {
            global $wpdb;
            $first_restaurant = $wpdb->get_results(
                    "SELECT *
                        FROM {$wpdb->prefix}reserveit_restaurants
                        ORDER BY {$wpdb->prefix}reserveit_restaurants.id" );
            $restaurant_id = $first_restaurant[0]->id;
            $restaurant_address = $first_restaurant[0]->address;
        }


        echo "   <p>Date :<input type='text' id='reserveit_expand_datepicker' value=" . $input_date . " name='reserveit_expand_datepicker'>";
        echo "<select name=reserveit_admin_restaurant_select id =reserveit_admin_restaurant_select>";
        global $wpdb;
        $all_restaurants = $wpdb->get_results(
                "SELECT *
  FROM {$wpdb->prefix}reserveit_restaurants
  ORDER BY {$wpdb->prefix}reserveit_restaurants.id;
  " );

        foreach ( $all_restaurants as $restaurant ) {
            if ( $restaurant->id == $restaurant_id ) {
                echo "<option value= $restaurant->id selected='selected'> $restaurant->address </option>";
            } else {
                echo "<option value= $restaurant->id > $restaurant->address </option>";
            }
        }
        echo "<input type='submit'   value='Show' name='reserveit_admin_submit' id='reserveit_admin_submit'></p>";

        function reserveit_get_user_table_id( $table_id ) {
            global $wpdb;
            $user_table_id = $wpdb->get_var( "SELECT user_table_id
            FROM {$wpdb->prefix}reserveit_tables
            WHERE id = '$table_id'"
            );

            return $user_table_id;
        }

        $sql_date = $new_datetime = date( "Y-m-d", strtotime( $input_date ) );


        global $wpdb;
        $reservations_any_tables = $wpdb->get_var(
            "SELECT COUNT(*)
            FROM {$wpdb->prefix}reserveit_tables
            WHERE {$wpdb->prefix}reserveit_tables.restaurant_id='$restaurant_id'"
        );

        global $wpdb;
        $all_reservations = $wpdb->get_results(
                "SELECT *
                FROM {$wpdb->prefix}reserveit_reservations
                WHERE {$wpdb->prefix}reserveit_reservations.restaurant_id= $restaurant_id
                AND DATE({$wpdb->prefix}reserveit_reservations.start_datetime) = '$sql_date'
                ORDER BY {$wpdb->prefix}reserveit_reservations.start_datetime"
        );
                
        if ( $reservations_any_tables >= 1 ) {

            if ( !empty( $all_reservations ) ) {

                echo "<table>";
                echo "<thead >";
                echo "<tr>";
                echo "<td>Time</td>";
                echo "<td>Table</td>";
                echo "<td>People</td>";
                echo "<td>First name</td>";
                echo "<td>Sur name</td>";
                echo "<td>Email</td>";
                echo "<td>Remove</td>";
                echo "</tr>";
                echo "</thead>";

                $i = 0;
                foreach ( $all_reservations as $reservation ) {
                    if ( $i % 2 != 0 ) {
                        echo "<tr class='firstcolor'>";
                    } else {
                        echo "<tr class='secondcolor'>";
                    }
                     $i++;
                    echo "<td id='time'>" . date( "H:i", strtotime( $reservation->start_datetime ) ) . "</td>";
                    echo "<td>" . reserveit_get_user_table_id( $reservation->table_id ) . "</td>";
                    echo "<td>$reservation->persons </td>";
                    echo "<td> $reservation->first_name </td>";
                    echo "<td> $reservation->sur_name </td>";
                    echo "<td> $reservation->email </td>";
                    echo "<td  id='remove'><input type='checkbox' name='remove_reservation" . $reservation->id . "'></td>";
                    echo "</tr>";
                }
                echo "</table>";

                echo "<input type='submit' name='reservations_update' value='Update'></p>";
                echo "</form>";
            } else {
                echo "<h2>No reservations for " . $input_date . " in " . $restaurant_address . "</h2>";
            }
        } else {
            echo "<h2> No tables for this restaurant.</h2>";
        }
    }

    function reserveit_expand_update( $restaurant_id, $sql_date ) {
        global $wpdb;
        $all_reservations = $wpdb->get_results(
                "SELECT *
                FROM {$wpdb->prefix}reserveit_reservations
                WHERE {$wpdb->prefix}reserveit_reservations.restaurant_id= $restaurant_id
                AND DATE({$wpdb->prefix}reserveit_reservations.start_datetime) = '$sql_date'
                ORDER BY {$wpdb->prefix}reserveit_reservations.start_datetime"
        );

        foreach ( $all_reservations as $reservation ) {
            if ( isset( $_POST['remove_reservation' . $reservation->id] ) ) {
                global $wpdb;
                $wpdb->query( "DELETE
                FROM {$wpdb->prefix}reserveit_reservations
                WHERE id='$reservation->id'" );
            }
        }
    }

    function reserveit_get_date() {
        if ( !empty( $_POST['reserveit_expand_datepicker'] ) ) {
            $input_date = $_POST ['reserveit_expand_datepicker'];
        } else {
            $input_date = date( 'd-m-Y' );
        }
        $sql_date = $new_datetime = date( "Y-m-d", strtotime( $input_date ) );
        return $sql_date;
    }

    function reserveit_get_restaurant_id() {
        if ( !empty( $_POST['reserveit_admin_restaurant_select'] ) ) {
            $restaurant_id = $_POST ['reserveit_admin_restaurant_select'];
            /*  global $wpdb;
              $restaurant_address = $wpdb->get_var(
              "SELECT {$wpdb->prefix}reserveit_restaurants.address
              FROM {$wpdb->prefix}reserveit_restaurants
              WHERE {$wpdb->prefix}reserveit_restaurants.id = '$restaurant_id'" ); */
        } else {
            global $wpdb;
            $first_restaurant = $wpdb->get_results(
                    "SELECT *
                        FROM {$wpdb->prefix}reserveit_restaurants
                        ORDER BY {$wpdb->prefix}reserveit_restaurants.id" );
            $restaurant_id = $first_restaurant[0]->id;
        }

        return $restaurant_id;
    }

    if ( isset( $_POST ['reserveit_admin_submit'] ) ) {
        reserveit_expand_form();
    } else if ( isset( $_POST ['reservations_update'] ) ) {
        $sql_date = reserveit_get_date();
        $restaurant_id = reserveit_get_restaurant_id();
        reserveit_expand_update( $restaurant_id, $sql_date );
        reserveit_expand_form();
    } else {
        reserveit_expand_form();
    }
}
