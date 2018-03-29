<?php
/*
Plugin Name: Huizen Op Flakkee Connector
Plugin URI: http://www.boomstruik.nl/huizenopflakkee
Description: Create WP-JSON output to feed Huizen Op Flakkee
Version: 1.0
Author: Marco Boom
Author URI: http://www.boomstruik.nl

Copyright 2018 Marco Boom
*/

include_once("./src/WP_REST_Aanbod_Controller.php");


add_action('rest_api_init', function () {
    $controller = new WP_REST_Aanbod_Controller();
    $controller->register_routes();
});
