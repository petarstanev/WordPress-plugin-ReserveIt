<?php

/**
 * Plugin Name: Reserve it
 * Description: Plugin for making reservations in restaurants. Use shortcode "[reserveit]" when you make new page.
 * Author: Petar Stanev
 * Author URI: petarstanev@outlook.com
 * Version: 0.1
 * License: GPLv2 ot later
 */
/*
  Petar Stanev (email : petarstanev@outlook.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA.
 */

function reserveit_menu() {
    add_menu_page('Reservations', 'Reservations', 'manage_options', 'reserveit/admin-menu/menu-reservations.php', '', '', 100);
    add_submenu_page('reserveit/admin-menu/menu-reservations.php', 'Expand reservations', 'Expand reservations', 'manage_options', 'reserveit/admin-menu/submenu-expand-reservations.php');
    add_submenu_page('reserveit/admin-menu/menu-reservations.php', 'Restaurants and Tables', 'Restaurants and Tables', 'manage_options', 'reserveit/admin-menu/submenu-options.php');
}

function reserveit_database_create() {
    include_once (dirname(__FILE__) . '/databasecreate.php');
    create_reserveit_reservations();
    create_reserveit_tables();
    create_reserveit_restaurants();
}

function reserveit_load_scripts() {
    wp_register_script('reserveit_js', plugins_url('/js/script.js', __FILE__), array('jquery'), '2.5.1');
    wp_enqueue_script('reserveit_js');

    wp_register_script('datetimepicker', plugins_url('/lib/datetimepicker/js/jquery-ui-timepicker-addon.js', __FILE__), array('jquery', 'jquery-ui-core', 'jquery-ui-slider', 'jquery-ui-datepicker'));
    wp_enqueue_script('datetimepicker');

    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

    wp_register_style('style', plugins_url('/css/style.css', __FILE__));
    wp_enqueue_style('style');
}

add_action('wp_enqueue_scripts', 'reserveit_load_scripts');
add_action('admin_enqueue_scripts', 'reserveit_load_scripts');

add_action('admin_menu', 'reserveit_menu');
register_activation_hook(__FILE__, 'reserveit_database_create');

// USER PAGE Start
function reserveit_any_restaurants_or_tables() {
    global $wpdb;
    $all_restaurants = $wpdb->get_var(
            "SELECT COUNT(*) 
             FROM  {$wpdb->prefix}reserveit_tables");

    if ($all_restaurants > 0)
        return TRUE;
}

function reserveit_any_slug() {
    if (!empty($_GET["id"])) {
        $slug = $_GET["id"];
        reserveit_confirm_page($slug);
        return TRUE;
    }
}

function reserveit_confirm_page($slug) {
    global $wpdb;
    $reservations_correct_slug = $wpdb->get_var(
            $wpdb->prepare(
                    "SELECT COUNT(*) 
             FROM  {$wpdb->prefix}reserveit_reservations
             WHERE slug = %s", $slug
            )
    );

    if ($reservations_correct_slug == 1) {
        global $wpdb;
        $is_updated = $wpdb->get_var(
                $wpdb->prepare(
                        "SELECT COUNT(*) 
            FROM  {$wpdb->prefix}reserveit_reservations
            WHERE slug = %s
            AND confirmed = 1", $slug
                )
        );

        if ($is_updated == 0) {
            $wpdb->query(
                    $wpdb->prepare(
                            "UPDATE {$wpdb->prefix}reserveit_reservations
                    SET confirmed = 1
                    WHERE slug=%s", $slug
                    )
            );
            echo "<h2>Your reservation is complete. </h2>";
        }
        //share        
        $get_reservation_info = $wpdb->get_results(
                $wpdb->prepare(
                        "SELECT * 
                FROM  {$wpdb->prefix}reserveit_reservations
                WHERE slug = %s", $slug
                )
        );

        echo "<h2> Reservation information:</h2>";
        echo "<h3>Restaurant - " . reserveit_get_restaurant_address($get_reservation_info[0]->restaurant_id);
        echo "<br>";
        echo 'Persons - ' . $get_reservation_info[0]->persons;
        echo "<br>";
        echo 'Date - ' . date("d-m-Y", strtotime($get_reservation_info[0]->start_datetime));
        echo "<br>";
        echo 'Time - ' . date("H:i", strtotime($get_reservation_info[0]->start_datetime));
        echo "<br>";
        echo 'Reservation is made by ' . $get_reservation_info[0]->first_name . " " . $get_reservation_info[0]->sur_name;
        echo "<br>";
        echo "<b>Share</h3></b>";
        echo "<br>";
        $url = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        ?>

        <a href="https://twitter.com/intent/tweet?url=<?php echo $url; ?>&text=I made reservation here : <?php echo $url; ?>"> <img src="<?php echo plugins_url('/images/twitter.png', __FILE__) ?>"></a>
        <a href="https://facebook.com/sharer.php?u=<?php echo $url; ?>"><img src="<?php echo plugins_url('/images/facebook.png', __FILE__) ?>"></a>
        <a href="https://plus.google.com/share?url=<?php echo $url; ?>"><img src="<?php echo plugins_url('/images/google+.png', __FILE__) ?>"></a>

        <?php
    } else {
        echo "You have an error in page url !";
    }
}

function reserveit_get_restaurant_address($id) {
    global $wpdb;
    $get_reservation_info = $wpdb->get_var(
            "SELECT address 
             FROM  {$wpdb->prefix}reserveit_restaurants
             WHERE id = '$id'"
    );
    return $get_reservation_info;
}

function reserveit_user_form() {
    if (reserveit_any_restaurants_or_tables()) {
        if (!reserveit_any_slug()) {
            if (!is_admin()) {
                ?>

                <form method="post">
                    <p>Time :<input type="text" id="timepicker" name="time_name"/></p>        
                    <p>Date :<input type="text" id="reserveit_user_datepicker" name="date_name"/></p>
                    <p>Persons :<input type="number" id="persons" min="1" max="30" name="persons_name"/></p>
                    <p>Restaurant :
                        <select name="select1">   
                            <?php
                            if (!empty($_POST['reserveit_admin_restaurant_select'])) {
                                $restaurant_id = $_POST ['reserveit_admin_restaurant_select'];
                            }

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
                            ?>
                        </select></p>
                    <p>First name :<input type="text" id="firstname" name="firstname_name"/></p>
                    <p>Surname :<input type="text" id="surname" name="surname_name"/></p>
                    <p>Email :<input type="email" id="email" name="email_name"/></p>

                    <input type="submit" id="make_reservation" class = "button-primary" value="Make reservations" name="make_reservation">
                </form>

                <?php
            } else {
                ?>
                <form method="post">	
                    <p>Time :<input type="text" id="timepicker" name="time_name"/>        
                        Date :<input type="text" id="reserveit_admin_add_datepicker" name="date_name"/>
                        Persons :<input type="number" id="persons" min="1" max="30" name="persons_name"/>
                        Restaurant :
                        <select name="select1">   
                            <?php
                            global $wpdb;
                            $all_restaurants = $wpdb->get_results(
                                    "SELECT *
                 FROM {$wpdb->prefix}reserveit_restaurants
                 ORDER BY {$wpdb->prefix}reserveit_restaurants.id;   
                 ");

                            foreach ($all_restaurants as $restaurant) {
                                echo "<option value= $restaurant->id > $restaurant->address </option>";
                            }
                            ?>
                        </select>
                        First name :<input type="text" id="firstname" name="firstname_name"/>
                        Surname :<input type="text" id="surname" name="surname_name"/>

                        <input type="submit" id="make_reservation" class = "button-primary" value="Make reservations" name="make_reservation"></p>
                </form>

                <?php
            }

//SQL DATETIME format YYYY-MM-DD HH-MM-SS    
//DATE format 18-09-2013 DD-MM-YYYY
//Time format 06:00 HH-MM

            function reserveit_test_input_error($time, $date, $persons, $restaurant_id, $first_name, $surname_name, $email) {
                $validate = TRUE;

                if (!preg_match('/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
                    echo 'You have an error in your Time input.' . "<br>";
                    $validate = FALSE;
                }

                if (!preg_match('/^(((((0[1-9])|(1\d)|(2[0-8]))-((0[1-9])|(1[0-2])))|((31-((0[13578])|(1[02])))|((29|30)-((0[1,3-9])|(1[0-2])))))-((20[0-9][0-9]))|(29-02-20(([02468][048])|([13579][26]))))$/ ', $date)) {
                    echo 'You have an error in your Date input.' . "<br>";
                    $validate = FALSE;
                }

                if (!preg_match('/\d{1,30}/', $persons)) {
                    echo 'You have an error in your Persons input.' . "<br>";
                    $validate = FALSE;
                }

                if (!preg_match('/[a-zA-Z ]{3,15}/', $first_name)) {
                    echo 'You have an error in your First name input.' . "<br>";
                    $validate = FALSE;
                }

                if (!preg_match('/[a-zA-Z ]{3,15}/', $surname_name)) {
                    echo 'You have an error in your Surname input.' . "<br>";
                    $validate = FALSE;
                }

                if (!is_admin()) {
                    if (!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/', $email)) {
                        echo 'You have an error in your email.' . "<br>";
                        $validate = FALSE;
                    }
                    global $wpdb;

                    $new_datetime = date("Y-m-d H-i", strtotime($date . "00:00"));

                    $reservaitons_from_same_email = $wpdb->get_var(
                            "SELECT COUNT({$wpdb->prefix}reserveit_reservations.id)
                FROM {$wpdb->prefix}reserveit_reservations
                WHERE {$wpdb->prefix}reserveit_reservations.email = '$email'
                AND {$wpdb->prefix}reserveit_reservations.start_datetime BETWEEN '$new_datetime' - INTERVAL 12 HOUR
                AND   '$new_datetime' + INTERVAL 12 HOUR
                ");

                    if ($reservaitons_from_same_email > 0) {
                        echo "There is already reservation made from the same email in 24 hour period.";
                        exit;
                    }
                }

                return $validate;
            }

            if (isset($_POST['make_reservation']) && !empty($_POST ['date_name']) && !empty($_POST ['time_name']) && !empty($_POST ['persons_name']) && !empty($_POST ['select1'])) {
                if (!is_admin()) {
                    $email = $_POST ['email_name'];
                } else {
                    $email = "admin@admin.com";
                }


                $time = $_POST['time_name'];

                $date = $_POST['date_name'];

                $persons = $_POST['persons_name'];

                $restaurant_id = $_POST['select1'];

                $first_name = $_POST['firstname_name'];

                $surname_name = $_POST['surname_name'];

                //check input
                if (!reserveit_test_input_error($time, $date, $persons, $restaurant_id, $first_name, $surname_name, $email)) {
                    exit;
                }

                $old_datetime = $date . $time;
                $new_datetime = date("Y-m-d H-i", strtotime($old_datetime));

                $old_datetime = date("Y-m-d H:i", strtotime($old_datetime));
                $now = date('Y-m-d H:i');
                //for comparing we need  Y-m-d H:i instade of Y-m-d H-i

                if (strtotime($old_datetime) > strtotime($now)) {
                    if (reserveit_working_time_test($restaurant_id, $time)) {
                        $free_table_id = reserveit_free_table_check($new_datetime, $persons, $restaurant_id, $time);

                        if ($free_table_id > 0) {
                            //insert
                            if (!is_admin()) {
                                $confirmed = 0;
                            } else {
                                $confirmed = 1;
                            }

                            $slug = wp_generate_password(12, FALSE, FALSE);

                            $now_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

                            $pos = strpos($now_link, "?");

                            if ($pos !== false) {
                                // found $
                                $full_link = $now_link . "&id=" . $slug;
                            } else {
                                // not found pretty links
                                //http://localhost/wordpress4/reserve-it-test-page/?id=7SDkKolYVE8F

                                $full_link = $now_link . "?id=" . $slug;
                            }

                            $user_id = get_current_user_id();

                            if (!is_admin()) {
                                global $wpdb;
                                $wpdb->insert($wpdb->prefix . 'reserveit_reservations', array(
                                    'table_id' => $free_table_id,
                                    'persons' => $persons,
                                    'restaurant_id' => $restaurant_id,
                                    'user_id' => $user_id,
                                    'first_name' => $first_name,
                                    'sur_name' => $surname_name,
                                    'email' => $email,
                                    'start_datetime' => $new_datetime,
                                    'slug' => $slug,
                                    'confirmed' => $confirmed)
                                );

                                echo '<br>';
                                echo 'Your reservation is created. Please confirmed it by checking link send to your email ';
                                echo '<br>';
                                $to = $_POST ['email_name'];
                                $subject = "Confirm your reservation No reply";
                                $message = "Please confirm your reservation by going to this link" . $full_link;

                                wp_mail($to, $subject, $message);
                            } else {
                                global $wpdb;
                                $wpdb->insert($wpdb->prefix . 'reserveit_reservations', array(
                                    'table_id' => $free_table_id,
                                    'persons' => $persons,
                                    'restaurant_id' => $restaurant_id,
                                    'user_id' => $user_id,
                                    'first_name' => $first_name,
                                    'sur_name' => $surname_name,
                                    'email' => "admin",
                                    'start_datetime' => $new_datetime,
                                    'slug' => $slug,
                                    'confirmed' => $confirmed)
                                );
                                echo "Reservation is added";
                            }
                        } else {
                            echo "No available tables in this period of time for this restaurant";
                        }
                    } else {
                        echo "You can't make reservations 1 hour before closing the restaurant !";
                    }
                } else {
                    echo "The chosen time and date already past.";
                }
            }
        }
    } else {
        echo "No existing restaurants or tables. Please add them from Restaurants and Tables menu in dashboard";
    }
}

function reserveit_free_table_check($new_datetime, $persons, $restaurant_id) {
    global $wpdb;

    $free_tables_time = $wpdb->get_results(
            "SELECT {$wpdb->prefix}reserveit_tables.id
                FROM {$wpdb->prefix}reserveit_tables
                WHERE {$wpdb->prefix}reserveit_tables.id
                    NOT IN (SELECT table_id 
                            FROM {$wpdb->prefix}reserveit_reservations
                            WHERE {$wpdb->prefix}reserveit_reservations.restaurant_id ='$restaurant_id'
                            AND {$wpdb->prefix}reserveit_reservations.start_datetime - INTERVAL 2 HOUR <= '$new_datetime'
                            AND {$wpdb->prefix}reserveit_reservations.start_datetime + INTERVAL 2 HOUR >= '$new_datetime')             
                 AND {$wpdb->prefix}reserveit_tables.restaurant_id ='$restaurant_id'
                 AND {$wpdb->prefix}reserveit_tables.chairs >='$persons'    
                 ORDER BY {$wpdb->prefix}reserveit_tables.chairs ");

    if (isset($free_tables_time[0]->id))
        return $free_tables_time[0]->id;

    return 0;
}

function reserveit_working_time_test($restaurant_id, $time) {
    global $wpdb;
    $free_working_time = $wpdb->get_var(
            "SELECT COUNT(*)
            FROM {$wpdb->prefix}reserveit_restaurants
            WHERE {$wpdb->prefix}reserveit_restaurants.id = '$restaurant_id'
            AND {$wpdb->prefix}reserveit_restaurants.start_time <= '$time'
            AND {$wpdb->prefix}reserveit_restaurants.end_time  >= ADDTIME('$time','01:00' )"
    );

    if ($free_working_time == 1)
        return TRUE;
}

// UESR PAGE END

add_shortcode('reserveit', 'reserveit_user_form');
