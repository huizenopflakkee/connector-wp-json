<?php

class WP_REST_Aanbod_Controller
{
 
    // Here initialize our namespace and resource name.
    public function __construct()
    {
        $this->namespace     = '/hof/v1';
        $this->resource_name = 'aanbod';
    }
 
    // Register our routes.
    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->resource_name, array(
            // Here we register the readable endpoint for collections.
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
            ),
            // Register our schema callback.
            'schema' => array( $this, 'get_item_schema' ),
        ));
    }
 
    /**
     * Check permissions for the posts.
     *
     * @param WP_REST_Request $request Current request.
     */

    public function get_items_permissions_check($request)
    {
        return true;
    }
 
    /**
     * Grabs the five most recent posts and outputs them as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_items($request)
    {
        $args = array(
            'post_per_page' => -1,
            'post_type' => $this->resource_name,
            'post_status' => 'publish',
            'nopaging' => true
            
        );
        $posts = get_posts($args);
 
        $data = array();
 
        if (empty($posts)) {
            return rest_ensure_response($data);
        }
 
        foreach ($posts as $post) {
            $response = $this->prepare_item_for_response($post, $request);
            $data[] = $this->prepare_response_for_collection($response);
        }
 
        // Return all of our comment response data.
        return rest_ensure_response($data);
    }
 
 
 
    /**
     * Matches the post data to the schema we want.
     *
     * @param WP_Post $post The comment object whose response is being prepared.
     */
    public function prepare_item_for_response($post, $request)
    {
        $post_data = array();
 
        $schema = $this->get_item_schema($request);
 
 
        // We are also renaming the fields to more understandable names.
        if (isset($schema['properties']['straat'])) {
            $post_data['straat'] = apply_filters('the_title', $post->post_title, $post);
        }

        if (isset($schema['properties']['status'])) {
            $statusses = wp_get_post_terms($post->ID, 'status', array("fields" => "names"));
            $statusses = wp_list_filter($statusses, array('slug'=>'nieuw'), 'NOT');

            $post_data['status'] = reset($statusses);
            $post_data['soort'] = stripos($post_data['status'], "huur") !== false ? 'huur' : 'koop';
        }


        if (isset($schema['properties']['plaats'])) {
            $cities = wp_get_post_terms($post->ID, 'plaats', array("fields" => "names"));

            $post_data['plaats'] = reset($cities);
        }

        if (isset($schema['properties']['link'])) {
            $post_data['link'] = get_permalink($post);
        }

       
        if (isset($schema['properties']['prijs'])) {
            $prijs = explode(" ", get_field('prijs', $post->ID));
            $post_data['prijs'] = (int)preg_replace("/[^\d]/", "", $prijs[0]);
            $post_data['prijs_conditie'] = isset($prijs[1]) ? trim($prijs[1]) : null;
        }

        if (isset($schema['properties']['type'])) {
            $types = wp_get_post_terms($post->ID, 'woning_soort', array("fields" => "names"));

            $post_data['type'] = reset($types);
        }

        if (isset($schema['properties']['aantal_slaapkamers'])) {
            $post_data['aantal_slaapkamers'] = (int)get_field('aantal_slaapkamers', $post->ID);
        }

        if (isset($schema['properties']['perceel_oppervlakte'])) {
            $post_data['perceel_oppervlakte'] = (int)get_field('perceeloppervlakte', $post->ID);
        }

        if (isset($schema['properties']['woon_oppervlakte'])) {
            $post_data['woon_oppervlakte'] = (int)get_field('woonoppervlakte', $post->ID);
        }

        if (isset($schema['properties']['inhoud'])) {
            $post_data['inhoud'] = (int)get_field('inhoud', $post->ID);
        }

        if (isset($schema['properties']['bouwjaar'])) {
            $post_data['bouwjaar'] = (int)get_field('bouwjaar', $post->ID);
        }

        if (isset($schema['properties']['oplevering'])) {
            $post_data['oplevering'] = get_field('aanvaarding', $post->ID);
        }

        if (isset($schema['properties']['omschrijving'])) {
            $post_data['omschrijving'] = get_field('korte_omschrijving', $post->ID);
        }
 
        $photos = get_field('aanbod_fotos', $post->ID); // get all the rows

        if ($photos) {
            if (isset($schema['properties']['hoofdfoto'])) {
                $main_photo = reset($photos);
                $post_data['hoofdfoto'] = $main_photo['aanbod_foto'];
            }

            if (isset($schema['properties']['fotos'])) {
                $post_data['fotos'] = [];
                foreach ($photos as $photo) {
                    $post_data['fotos'][] = $photo['aanbod_foto'];
                }
            }
        }


        return rest_ensure_response($post_data);
    }
 
    /**
     * Prepare a response for inserting into a collection of responses.
     *
     * This is copied from WP_REST_Controller class in the WP REST API v2 plugin.
     *
     * @param WP_REST_Response $response Response object.
     * @return array Response data, ready for insertion into collection data.
     */
    public function prepare_response_for_collection($response)
    {
        if (! ($response instanceof WP_REST_Response)) {
            return $response;
        }
 
        $data = (array) $response->get_data();
        $server = rest_get_server();
 
        if (method_exists($server, 'get_compact_response_links')) {
            $links = call_user_func(array( $server, 'get_compact_response_links' ), $response);
        } else {
            $links = call_user_func(array( $server, 'get_response_links' ), $response);
        }
 
        if (! empty($links)) {
            $data['_links'] = $links;
        }
 
        return $data;
    }
 
    /**
     * Get our sample schema for a post.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item_schema($request)
    {
        $schema = array(
            // This tells the spec of JSON Schema we are using which is draft 4.
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            // The title property marks the identity of the resource.
            'title'                => 'post',
            'type'                 => 'object',
            // In JSON Schema you can specify object properties in the properties attribute.
            'properties'           => array(
                'straat' => array(
                    'description'  => esc_html__('The address of the object.', 'my-textdomain'),
                    'type'         => 'string',
                ),
                'plaats' => array(
                    'description'  => esc_html__('The city of the object.', 'my-textdomain'),
                    'type'         => 'string',
                ),
                'link' => array(
                    'description'  => esc_html__('The link of the object.', 'my-textdomain'),
                    'type'         => 'string',
                ),
                'prijs' => array(
                    'description'  => esc_html__('The price of the object.', 'my-textdomain'),
                    'type'         => 'string',
                ),
                'status' => array(
                    'description'  => esc_html__('The price of the object.', 'my-textdomain'),
                    'type'         => 'string',
                ),
                'soort' => array(
                    'description'  => esc_html__('The kind of the object.', 'my-textdomain'),
                    'type'         => 'string',
                ),
                'omschrijving' => array(
                    'description'  => esc_html__('The content of the object.', 'my-textdomain'),
                    'type'         => 'string',
                ),
                'hoofdfoto' => array(
                    'description'  => esc_html__('The content of the object.', 'my-textdomain'),
                    'type'         => 'string',
                ),
                'fotos' => array(
                    'description'  => esc_html__('The content of the object.', 'my-textdomain'),
                    'type'         => 'array',
                ),
                'bouwjaar' => array(
                    'description'  => esc_html__('The content of the object.', 'my-textdomain'),
                    'type'         => 'int',
                ),
                'woon_oppervlakte' => array(
                    'description'  => esc_html__('The content of the object.', 'my-textdomain'),
                    'type'         => 'int',
                ),
                'perceel_oppervlakte' => array(
                    'description'  => esc_html__('The content of the object.', 'my-textdomain'),
                    'type'         => 'int',
                ),
                'inhoud' => array(
                    'description'  => esc_html__('The content of the object.', 'my-textdomain'),
                    'type'         => 'int',
                ),
                'type' => array(
                    'description'  => esc_html__('The content of the object.', 'my-textdomain'),
                    'type'         => 'string',
                ),
                'prijs_conditie' => array(
                    'description'  => esc_html__('The content of the object.', 'my-textdomain'),
                    'type'         => 'string',
                ),
                'aantal_slaapkamers' => array(
                    'description'  => esc_html__('The content of the object.', 'my-textdomain'),
                    'type'         => 'int',
                ),
                'oplevering' => array(
                    'description'  => esc_html__('The content of the object.', 'my-textdomain'),
                    'type'         => 'string',
                ),
            ),
        );
 
        return $schema;
    }
 
    // Sets up the proper HTTP status code for authorization.
    public function authorization_status_code()
    {
        $status = 401;
 
        if (is_user_logged_in()) {
            $status = 403;
        }
 
        return $status;
    }
}
