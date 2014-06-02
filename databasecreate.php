<?php

function create_reserveit_reservations() {
    global $wpdb;
    $table_name = $wpdb->prefix . "reserveit_reservations";
    $sql = "CREATE TABLE $table_name (
                        id int NOT NULL AUTO_INCREMENT,
                        table_id int NOT NULL,
                        persons int NOT NULL,
                        restaurant_id int NOT NULL,
                        user_id int NOT NULL,
                        first_name VARCHAR(55) NOT NULL,
                        sur_name VARCHAR(55) NOT NULL,
                        email VARCHAR(55) NOT NULL,
                        start_datetime DATETIME NOT NULL,
                        slug VARCHAR(55) NOT NULL,
                        confirmed TINYINT(20)NOT NULL,
                        UNIQUE KEY id (id));";

    require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $sql );
}

function create_reserveit_restaurants() {
    global $wpdb;
    $table_name = $wpdb->prefix . "reserveit_restaurants";
    $sql = "CREATE TABLE $table_name (
                        id int NOT NULL AUTO_INCREMENT,
                        address VARCHAR(55) NOT NULL,
                        start_time TIME DEFAULT '08:00' NOT NULL,    
                        end_time TIME DEFAULT '22:00' NOT NULL,
                        UNIQUE KEY id (id)
 			);";

    require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $sql );
}

function create_reserveit_tables() {
    global $wpdb;
    $table_name = $wpdb->prefix . "reserveit_tables";
    $sql = "CREATE TABLE $table_name (
                        id int NOT NULL AUTO_INCREMENT,
                        user_table_id int NOT NULL,
                        chairs int NOT NULL,
                        restaurant_id int NOT NULL,
                        UNIQUE KEY id (id)
 			);";

    require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $sql );
}

function create_reserveit_workingtime() {
    global $wpdb;
    $table_name = $wpdb->prefix . "reserveit_workingtime";
    $sql = "CREATE TABLE $table_name (
                        id  int NOT NULL AUTO_INCREMENT,
                        restaurant_id int ,
                        day_from_week int ,
                        start time DEFAULT '08:00',    
                        end time DEFAULT '22:00',
                        working_day BOOLEAN ,
                        UNIQUE KEY id (id)
                        );";

    require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $sql );
}
