<?php

echo "<div class='wrap'>";
function reserveit_select_all_restaurants() {
    global $wpdb;
    return $all_restaurants = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}reserveit_restaurants" );
}

function reserveit_select_all_tables() {
    global $wpdb;
    $all_tables = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}reserveit_tables ORDER BY restaurant_id" );
    return $all_tables;
}

reserveit_all_restaurants();

echo "</div>";

//RESTAURANTS START

function reserveit_all_restaurants() {

    function reserveit_unique_restaurant_address( $restaurant_address ) {
        global $wpdb;
        $restaurant_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}reserveit_restaurants WHERE address='$restaurant_address'" );

        if ( $restaurant_count == 0 ) {
            return TRUE;
        } else {
            echo "<h2> Restaurants with same address exist ! </h2>";
            return FALSE;
        }
    }

    function reserveit_insert_restaurant( $restaurant_address ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'reserveit_restaurants', array(
            'address' => $restaurant_address
        ) );
    }

    function reserveit_print_restaurants_form() {
        $all_restaurants = reserveit_select_all_restaurants();

        if ( !empty( $all_restaurants ) ) {
            //add new form for add ONLY !

            echo "<form method='post' name='restaurant_form_name' action=''>";
            echo "<b><h2>Restaurants</h2></b>";
            echo "<table>";
            echo "<thead>";
            echo "<tr>";
            echo "<td>Address</td>
        <td>Start working </td>
        <td>End working </td>
        <td>Remove</td>                      
        </tr>
        </thead>
        <tbody>";


            $all_restaurants = reserveit_select_all_restaurants();
            $i = 0;
            foreach ( $all_restaurants as $restaurant ) {
                $id = $restaurant->id;
                $address = $restaurant->address;
                $start_time = $restaurant->start_time;
                $end_time = $restaurant->end_time;


                if ( $i % 2 != 0 ) {
                    echo "<tr class='firstcolor'>";
                } else {
                    echo "<tr class='secondcolor'>";
                }
                $i++;
                echo "<td><input type='text' value='" . $address . "' name='address" . $id . "' autocomplete='off'></td>";
                echo "<td><input type='time' value='" . $start_time . "' name='start_time" . $id . "'autocomplete='off'></td>";
                echo "<td><input type='time' value='" . $end_time . "' name='end_time" . $id . "'autocomplete='off'></td>";
                echo "<td><input type='checkbox' name='remove" . $id . "'></td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            echo "<input type='submit' name='restaurants_update' value='Restaurants Update' onClick='window.location.reload(true)' >";
            echo "</form>";
        }
        echo"  <form method='post'>	
        <h3>Add new restaurant</h3>
        Address - <input id='address' name='address_name'/>
            <input type='submit' name='add_restaurant' value='Add'  onClick='window.location.reload(true)' ></p>
    </form>";

        //add new restaurant
        $new_all_restaurants = reserveit_select_all_restaurants();

        if ( !empty( $new_all_restaurants ) ) {
            reserveit_all_tables();
        }
    }

    if ( isset( $_POST['restaurants_update'] ) ) {

        $all_restaurants = reserveit_select_all_restaurants();

        foreach ( $all_restaurants as $restaurant ) {

            if ( isset( $_POST['remove' . $restaurant->id] ) ) {
                global $wpdb;
                $wpdb->query( "DELETE
        FROM {$wpdb->prefix}reserveit_restaurants                  
        WHERE id='$restaurant->id'" );

                global $wpdb;
                $wpdb->query( "DELETE
        FROM {$wpdb->prefix}reserveit_tables                  
        WHERE restaurant_id='$restaurant->id'" );
            } else {

                $address = $_POST['address' . $restaurant->id];
                $start_new_time = $_POST['start_time' . $restaurant->id];
                $end_new_time = $_POST['end_time' . $restaurant->id];

                if ( strtotime( $start_new_time ) < strtotime( $end_new_time ) ) {
                    global $wpdb;
                    $wpdb->query( "UPDATE {$wpdb->prefix}reserveit_restaurants 
        SET address='$address',
        start_time='$start_new_time',
        end_time='$end_new_time'   
        WHERE id='$restaurant->id';" );
                } else {
                    "<h3>Your Start working time must be smaller than your End working time !";
                }
            }
        }

        reserveit_print_restaurants_form();
        echo "Changes complete";
    } else if ( isset( $_POST ['add_restaurant'] ) && !empty( $_POST ['address_name'] ) ) {
        //add new from form
        $restaurant_address = $_POST ['address_name'];

        if ( reserveit_unique_restaurant_address( $restaurant_address ) ) {
            reserveit_insert_restaurant( $restaurant_address );
            reserveit_print_restaurants_form();
        } else {
            reserveit_print_restaurants_form();
        }
    } else {
        reserveit_print_restaurants_form();
    }
}

///////NEW TABLES START 
//TABLES START
function reserveit_all_tables() {
    function reserveit_print_tables_form() {
        $all_tables = reserveit_select_all_tables();
        echo "<b><h2>Tables</h2></b>";
        echo "<form method='post' name='tables_form_name'> ";
        echo "<table>";
        echo "<thead>";
        echo "<tr>";
        echo "<td>Restaurant</td>";
        echo"<td>ID</td>";
        echo"<td>Chairs</td>";
        echo"<td>Remove</td ";
        echo"</tr>";
        echo"</thead>";
        echo"<tbody>";

        $i = 0;
        foreach ( $all_tables as $table ) {

            $id = $table->id;
            $restaurant_id = $table->restaurant_id;
            global $wpdb;
            $restaurant_address = $wpdb->get_var( "SELECT address 
                                                               FROM {$wpdb->prefix}reserveit_restaurants
                                                               WHERE {$wpdb->prefix}reserveit_restaurants.id = '$restaurant_id'" );

            $user_table_id = $table->user_table_id;
            $chairs = $table->chairs;
            if ( $i % 2 != 0 ) {
                echo "<tr class='firstcolor'>";
            } else {
                echo "<tr class='secondcolor'>";
            }
            $i++;
            echo "<input type='hidden' value='$restaurant_id' name='restaurant_id_hidden" . $restaurant_id . "'>";
            echo "<td> $restaurant_address</td>";
            echo "<td><input type='number' min='0' value='$user_table_id'  name='user_table_id" . $id . "'></td>";
            echo "<td><input type='number' min='1' value='$chairs' name='chairs" . $id . "'></td>";
            echo "<td><input type='checkbox' name='remove" . $id . "'></td>";
            echo "</tr>";
        }
        echo"</tbody>";
        echo "</table>";
        echo "<input type='submit' name='tables_update' value='Tables Update'  >";
        echo "</form>";

        //add new restaurant
        reserveit_add_new_table_form();
    }

    function reserveit_add_new_table_form() {
        echo "<h3> Add new table</h3>";
        echo "<form method='post'>";
        echo "            <p>Restaurant :\n";

        if ( !empty( $_POST['reserveit_admin_restaurant_select'] ) ) {
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
                                 " );

        foreach ( $all_restaurants as $restaurant ) {
            if ( $restaurant->id == $restaurant_id ) {
                echo "<option value= $restaurant->id selected='selected'> $restaurant->address </option>";
            } else {
                echo "<option value= $restaurant->id > $restaurant->address </option>";
            }
        }
        echo "</select>";
        echo "Table ID - <input type='number' min='0' id='tableid' name='add_table_user_table_id'/>";
        echo "Chairs - <input type='number' min='1' id='tablechairs' name='add_table_chairs'/>";
        echo "<input type='submit' value='Add'  name='add_table' onClick='window.location.reload(true)' ></p>";
        echo "</form>";
    }

    function reserveit_tables_print_right_form() {
        $all_tables = reserveit_select_all_tables();

        if ( empty( $all_tables ) ) {
            //add new form for add ONLY !
            reserveit_add_new_table_form();
        } else {
            //no change
            reserveit_print_tables_form();
        }
    }

    if ( isset( $_POST['tables_update'] ) ) {

        $all_tables = reserveit_select_all_tables();
        $new_id = TRUE;
        foreach ( $all_tables as $table ) {
            if ( isset( $_POST['remove' . $table->id] ) ) {
                global $wpdb;
                $wpdb->query( "DELETE
        FROM {$wpdb->prefix}reserveit_tables                  
        WHERE id='$table->id'" );
            } else {
                //update
                $user_table_id = $_POST['user_table_id' . $table->id];
                $chairs = $_POST['chairs' . $table->id];
                $restaurant_id = $_POST ['restaurant_id_hidden' . $table->restaurant_id];
                $all_tables_check = reserveit_select_all_tables();
                $same_table_id_count = 0;

                foreach ( $all_tables as $table_check ) {
                    if ( $user_table_id == $_POST ['user_table_id' . $table_check->id] && $restaurant_id == $_POST ['restaurant_id_hidden' . $table_check->restaurant_id] ) {
                        $same_table_id_count++;
                    }
                }
                //If check is ok there will be only 1 input with same information when is updating !

                if ( $same_table_id_count == 1 ) {
                    global $wpdb;
                    $wpdb->query( "UPDATE {$wpdb->prefix}reserveit_tables
                                                SET user_table_id='$user_table_id',
                                                chairs='$chairs'
                                                WHERE id='$table->id'" );
                } else {

                    if ( $new_id ) {
                        echo "<h2>Table with same table id and same restaurant exist ! </h2>";
                        $new_id = FALSE;
                    }
                }
            }
        }

        reserveit_tables_print_right_form();
        echo "Changes complete";
    } else if ( isset( $_POST ['add_table_user_table_id'] ) && !empty( $_POST ['add_table_chairs'] ) ) {
        //add new from form
        $restaurant_id = $_POST ['reserveit_admin_restaurant_select'];
        $user_table_id = $_POST ['add_table_user_table_id'];
        $table_chairs = $_POST ['add_table_chairs'];

        reserveit_add_table( $user_table_id, $table_chairs, $restaurant_id );
        reserveit_tables_print_right_form();
    } else {
        reserveit_tables_print_right_form();
    }
}

function reserveit_add_table( $user_table_id, $table_chairs, $restaurant_id ) {
    global $wpdb;
    $same_table_id_count = $wpdb->get_var( "SELECT COUNT(*)
            FROM {$wpdb->prefix}reserveit_tables
            WHERE {$wpdb->prefix}reserveit_tables.user_table_id='$user_table_id'
            AND {$wpdb->prefix}reserveit_tables.restaurant_id='$restaurant_id'" );

    if ( $same_table_id_count == 0 ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'reserveit_tables', array(
            'user_table_id' => $user_table_id,
            'chairs' => $table_chairs,
            'restaurant_id' => $restaurant_id
        ) );
    } else {
        echo "<h2>Table with same id and same restaurant exist ! </h2>";
    }
}
