<?php
/*
Plugin Name: Indy Ambassadors Email Templater
Description: A custom plugin for generating the weekly newsletter by Indy Ambassadors. Requires The Events Calendar Plugin.
Version: 1.9
Requires at least: 6.0
Requires PHP: 5.6
Author: Josh Klein
Author URI: https://jklein.me
*/

function ia_email_templater_add()
{
    add_menu_page(
        __('IA Email Templater', 'textdomain'),
        'IA Email Templater',
        'manage_options',
        plugin_dir_path(__FILE__) . 'admin-view.php',
        '',
        'dashicons-email-alt',
        9
    );
}
add_action('admin_menu', 'ia_email_templater_add');

function ia_email_templater_enqueue()
{
    wp_enqueue_style('ia-email-admin', plugin_dir_url(__FILE__) . 'ia-email-admin.css');
    wp_enqueue_script('ia-email-script', plugin_dir_url(__FILE__) . 'ia-email-script.js');
    wp_enqueue_media();
}
add_action('admin_enqueue_scripts', 'ia_email_templater_enqueue');

function ia_email_install()
{
    global $wpdb;
    $table_templater = $wpdb->prefix . 'ia_email_templater';
    $table_events = $wpdb->prefix . 'ia_email_events';
    $table_event_imgs = $wpdb->prefix . 'ia_email_event_imgs';
    $table_event_buttons = $wpdb->prefix . 'ia_email_event_buttons';

    $wpdb->query(
        "CREATE TABLE IF NOT EXISTS $table_templater (
	    	id int NOT NULL AUTO_INCREMENT,
            header_image_id int NOT NULL,
	    	header_text text NOT NULL,
	    	intro_paragraph text NOT NULL,
            intro_signature text NOT NULL,
            footer_signup text NOT NULL,
            footer_socials text NOT NULL,
	    	PRIMARY KEY  (id)
	    )"
    );

    $wpdb->query(
        "CREATE TABLE IF NOT EXISTS $table_events (
            id int NOT NULL AUTO_INCREMENT,
            email_id int NOT NULL,
            tec_event_id int NOT NULL,
            event_minimized varchar(4) DEFAULT 'no' NOT NULL,
            event_featured varchar(4) DEFAULT 'off' NOT NULL,
            event_two_imgs varchar(4) DEFAULT 'off' NOT NULL,
            event_img_float varchar(5) DEFAULT 'left' NOT NULL,
            event_divider varchar(4) DEFAULT 'off' NOT NULL,
            event_mute varchar(4) DEFAULT 'off' NOT NULL,
            event_header_text varchar(256) DEFAULT '' NOT NULL,
            event_text longtext DEFAULT '' NOT NULL,
            PRIMARY KEY  (id),
            FOREIGN KEY  (email_id) REFERENCES $table_templater (id)
      )"
    );

    $wpdb->query(
        "CREATE TABLE IF NOT EXISTS $table_event_imgs (
            id int NOT NULL AUTO_INCREMENT,
            event_id int NOT NULL,
            event_img_id int NOT NULL,
            PRIMARY KEY  (id),
            FOREIGN KEY  (event_id) REFERENCES $table_events (id)
      )"
    );

    $wpdb->query(
        "CREATE TABLE IF NOT EXISTS $table_event_buttons (
            id int NOT NULL AUTO_INCREMENT,
            event_id int NOT NULL,
            event_button_text varchar(256) DEFAULT '' NOT NULL,
            event_button_link varchar(256) DEFAULT '' NOT NULL,
            PRIMARY KEY  (id),
            FOREIGN KEY  (event_id) REFERENCES $table_events (id)
      )"
    );
}

function ia_email_install_data()
{
    global $wpdb;
    $table_templater = $wpdb->prefix . 'ia_email_templater';
    $table_events = $wpdb->prefix . 'ia_email_events';
    $table_event_imgs = $wpdb->prefix . 'ia_email_event_imgs';
    $table_event_buttons = $wpdb->prefix . 'ia_email_event_buttons';

    $wpdb->insert(
        $table_templater,
        array(
            'header_image_id' => '',
            'header_text' => 'Example Header',
            'intro_paragraph' => 'Initial introduction paragraph.',
            'intro_signature' => 'FirstName LastName • RoleOrTitle',
            'footer_signup' => 'There are no fees to join the Indy Ambassadors. Sign up at <a title="https://indyambassadors.org/" href="https://indyambassadors.org/">indyambassadors.org</a>.',
            'footer_socials' => 'Links: <a href="http://indyambassadors.org">Our website</a> • <a href="https://www.meetup.com/indyambassadors">Meetup</a> • <a href="https://www.facebook.com/indianapolis.ambassadors">Facebook page</a> • <a href="https://www.facebook.com/groups/60576055766">Facebook group</a> • <a href="https://www.youtube.com/channel/UCJ9YIP8Umj3p2IhiQ1-XnSw">YouTube</a>
            We want your volunteer stories, pictures, and events. Email us to <a href="mailto:volunteeradmin@indyambassadors.org">\'Spill the Tea\'</a>.<br>'
        )
    );

    $last_id = $wpdb->insert_id;
    $wpdb->insert(
        $table_events,
        array(
            'email_id' => $last_id,
            'tec_event_id' => '',
            'event_minimized' => 'no',
            'event_featured' => 'off',
            'event_two_imgs' => 'off',
            'event_divider' => 'off',
            'event_mute' => 'off',
            'event_header_text' => 'Initial Header',
            'event_text' => 'Initial event text.'
        )
    );

    $last_id = $wpdb->insert_id;
    $wpdb->insert(
        $table_event_imgs,
        array(
            'event_id' => $last_id,
            'event_img_id' => ''
        )
    );

    $wpdb->insert(
        $table_event_buttons,
        array(
            'event_id' => $last_id,
            'event_button_text' => 'Volunteer',
            'event_button_link' => 'https://www.example.com'
        )
    );
}
register_activation_hook(__FILE__, 'ia_email_install');
register_activation_hook(__FILE__, 'ia_email_install_data');

function ia_email_post($post)
{
    global $wpdb;
    $table_templater = $wpdb->prefix . 'ia_email_templater';
    $table_events = $wpdb->prefix . 'ia_email_events';
    $table_event_imgs = $wpdb->prefix . 'ia_email_event_imgs';
    $table_event_buttons = $wpdb->prefix . 'ia_email_event_buttons';

    $header_image_id = $post['ia-email-header-image'];
    $header = $post['ia-email-title-text'];
    $intro_paragraph = $post['ia-email-intro-p'];
    $intro_signature = $post['ia-email-intro-signature'];
    $footer_signup = $post['ia-email-footer-signup'];
    $footer_socials = $post['ia-email-footer-socials'];

    // instead of all events, this will become a list of changing events?
    $events = [];
    $index = 0;
    //print "\n\n\n\n\n"
    printf("\n\n\n\n\n\n");
    //echo var_dump($post['ia-email-events']);
    /* $post['ia-email-events'] is a very flat array that includes a key and a value for each component of 
       every volunteer event. Event relationship is derived from the order of the array components. 
       Here's an example of one event from that array.
            [121]=> array(1) { ["event-minimized"]=> string(3) "yes" } 
            [122]=> array(1) { ["tec-dropdown"]=> string(1) "0" } 
            [123]=> array(1) { ["event-featured"]=> string(2) "on" } 
            [124]=> array(1) { ["event-mute"]=> string(2) "on" } 
            [125]=> array(1) { ["event-header"]=> string(79) "500 Festival 3-Miler Course Marshal" } 
            [126]=> array(1) { ["event-image-id"]=> array(1) { [0]=> string(5) "22485" } } 
            [127]=> array(1) { ["event-text"]=> string(303) "Provide crowd support and directional assistance..." } 
            [12?]=> array(1) { ["event-button"]=> array(1) { ["id"]=> array(1) { [0]=> string(3) "788" } } }
            [128]=> array(1) { ["event-button"]=> array(1) { ["text"]=> array(1) { [0]=> string(9) "Volunteer" } } } 
            [129]=> array(1) { ["event-button"]=> array(1) { ["link"]=> array(1) { [0]=> string(58) "https://500festival.volunteerlocal.com/volunteer/?id=90794" } } } 
            [12?]=> array(1) { ["event-button"]=> array(1) { ["id"]=> array(1) { [0]=> string(3) "789" } } }
            [130]=> array(1) { ["event-button"]=> array(1) { ["text"]=> array(1) { [0]=> string(3) "Run" } } } 
            [131]=> array(1) { ["event-button"]=> array(1) { ["link"]=> array(1) { [0]=> string(38) "https://www.indymini.com/p/milerseries" } } } 
            [13?]=> array(1) { ["event-button"]=> array(1) { ["id"]=> array(1) { [0]=> string(3) "790" } } }
            [132]=> array(1) { ["event-button"]=> array(1) { ["text"]=> array(1) { [0]=> string(12) "500 Festival" } } } 
            [133]=> array(1) { ["event-button"]=> array(1) { ["link"]=> array(1) { [0]=> string(24) "https://500festival.com/" } } } 
    The following loop builds assembles the components into a comprehensive event object.
    $k is the key of an object in the array and $v is the value (an object) */
    foreach ($post['ia-email-events'] as $k => $v) {
        if (!empty($events)) {
            //
            if (array_key_exists('event-image-id', $events[$index]) && key($v) == 'event-image-id') {
                $events[$index]['event-image-id'] = array_merge($events[$index]['event-image-id'], $v[key($v)]);
            } elseif (array_key_exists('event-button', $events[$index]) && key($v) == 'event-button') {
                $events[$index]['event-button'] = array_merge_recursive($events[$index]['event-button'], $v[key($v)]);
            } elseif (array_key_exists(key($v), $events[$index])) {
                $index++;
                $events[$index][key($v)] = $v[key($v)];
            } else {
                $events[$index][key($v)] = $v[key($v)];
            }
        } else {
            //Key() returns the index of current element of the given array. 
            //Example: $events[0]["event-minimized"]="yes"
            $events[$index][key($v)] = $v[key($v)];
        }
    }
    //echo "hello!!!!!!!!";
    //print "HEEYYY!";
    //print var_dump($events);
    //print var_dump($events);
    //print var_dump($events);
    print var_dump($events);
    /* Post loop processing the same event has a single, complex structure
    [12]=> array(8) { 
        ["event-minimized"]=> string(3) "yes" 
        ["tec-dropdown"]=> string(1) "0" 
        ["event-featured"]=> string(2) "on" 
        ["event-mute"]=> string(2) "on" 
        ["event-header"]=> string(79) "500 Festival 3-Miler Course Marshal" 
        ["event-image-id"]=> array(1) { 
            [0]=> string(5) "22485" 
        } 
        ["event-text"]=> string(303) "Provide crowd support and directional assistance..." 

        ["event-button"]=> array(3) { ["id"]=> array(1) { [0]=> string(1) "1" } ["text"]=> array(1) { [0]=> string(9) "Volunteer" } ["link"]=> array(1) { [0]=> string(23) "https://www.example.com" } } } }

        ["event-button"]=> array(3) { 
            ["id"]=> array(3) { 
                [0]=> string(3) "788" 
                [1]=> string(3) "789" 
                [2]=> string(3) "790" 
            } 
            ["text"]=> array(3) { 
                [0]=> string(9) "Volunteer" 
                [1]=> string(3) "Run" 
                [2]=> string(12) "500 Festival" 
            } 
            ["link"]=> array(3) { 
                [0]=> string(58) "https://500festival.volunteerlocal.com/volunteer/?id=90794" 
                [1]=> string(38) "https://www.indymini.com/p/milerseries" 
                [2]=> string(24) "https://500festival.com/" 
            } 
        } 
    } */
    for ($i = 0; $i < count($events); $i++) {
        if (!array_key_exists('event-featured', $events[$i])) {
            $events[$i]['event-featured'] = 'off';
        }
        if (!array_key_exists('event-two-imgs', $events[$i])) {
            $events[$i]['event-two-imgs'] = 'off';
        }
        if (!array_key_exists('event-divider', $events[$i])) {
            $events[$i]['event-divider'] = 'off';
        }
        if (!array_key_exists('event-mute', $events[$i])) {
            $events[$i]['event-mute'] = 'off';
        }
        if (!array_key_exists('event-img-float-direction', $events[$i])) {
            $events[$i]['event-img-float-direction'] = 'left';
        }
    }
    $the_events = $events;

    $wpdb->update(
        $table_templater,
        array(
            'header_image_id' => $header_image_id,
            'header_text' => $header,
            'intro_paragraph' => $intro_paragraph,
            'intro_signature' => $intro_signature,
            'footer_signup' => $footer_signup,
            'footer_socials' => $footer_socials
        ),
        array('id' => 1)
    );
    //$last_id = $wpdb->insert_id;
    $last_id = 1;

    //'event_img_float' => $event['event-img-float-direction'],
    foreach ($the_events as $event) {
        if (!empty($event)) {
            if (!empty($event['event-id'])) {
                //TODO: delete if so indicated
                $event_id = $event['event-id'];
                $wpdb->update(
                    $table_events,
                    array(
                        'email_id' => $last_id,
                        'tec_event_id' => $event['tec-dropdown'],
                        'event_minimized' => $event['event-minimized'],
                        'event_featured' => $event['event-featured'],
                        'event_two_imgs' => $event['event-two-imgs'],
                        'event_divider' => $event['event-divider'],
                        'event_mute' => $event['event-mute'],
                        'event_header_text' => $event['event-header'],
                        'event_text' => $event['event-text']
                    ),
                    array('id' => $event_id)
                );
            } else {
                $wpdb->insert(
                    $table_events,
                    array(
                        'email_id' => $last_id,
                        'tec_event_id' => $event['tec-dropdown'],
                        'event_minimized' => $event['event-minimized'],
                        'event_featured' => $event['event-featured'],
                        'event_two_imgs' => $event['event-two-imgs'],
                        'event_divider' => $event['event-divider'],
                        'event_mute' => $event['event-mute'],
                        'event_header_text' => $event['event-header'],
                        'event_text' => $event['event-text']
                    )
                 );
                $event_id = $wpdb->insert_id;
            }
                        
            /*foreach ($event['event-image-id'] as $event_img) {
                $wpdb->insert(
                    $table_event_imgs,
                    array(
                        'event_id' => $event_id,
                        'event_img_id' => $event_img
                    )
                );
            }
            */
            for ($i = 0; $i < count($event['event-button']['text']); $i++) {
                //print "button id: " . $event['event-button']['id'][$i]; 
                if (!empty($event['event-button']['id'][$i])) {
                    //TODO: delete if so indicated
                    $wpdb->update(
                        $table_event_buttons,
                        array(
                            'event_id' => $event_id,
                            'event_button_text' => $event['event-button']['text'][$i],
                            'event_button_link' => $event['event-button']['link'][$i]
                        ),
                        array('id' => $event['event-button']['id'][$i])
                    );
                } else {
                    $wpdb->insert(
                        $table_event_buttons,
                        array(
                            'event_id' => $event_id,
                            'event_button_text' => $event['event-button']['text'][$i],
                            'event_button_link' => $event['event-button']['link'][$i]
                        )
                    );
                };
            }
        }
    }
}

function ia_email_get($var)
{
    global $wpdb;
    $table_templater = $wpdb->prefix . 'ia_email_templater';
    $table_events = $wpdb->prefix . 'ia_email_events';

    if ($var == 'events') {
        $result = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_events WHERE email_id = (SELECT MAX(email_id) FROM $table_events)"
            )
        );
    } else {
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT $var FROM $table_templater
            ORDER BY id DESC
            LIMIT 1
            "
            )
        );
    }

    return $result;
}

function ia_email_get_imgs($email_id)
{
    global $wpdb;
    $table_events = $wpdb->prefix . 'ia_email_events';
    $table_event_imgs = $wpdb->prefix . 'ia_email_event_imgs';

    $result = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_event_imgs WHERE event_id = (SELECT id FROM $table_events WHERE id = $email_id)"
        )
    );
    return $result;
}

function ia_email_get_buttons($email_id)
{
    global $wpdb;
    $table_events = $wpdb->prefix . 'ia_email_events';
    $table_event_buttons = $wpdb->prefix . 'ia_email_event_buttons';

    $result = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_event_buttons WHERE event_id = (SELECT id FROM $table_events WHERE id = $email_id)"
        )
    );
    return $result;
}

function ia_email_get_buttons_html($event_id)
{
    $result = '';
    $event_buttons = ia_email_get_buttons($event_id);
    if (!empty($event_buttons[0]->event_button_text)) {
        $result .= '<span style="margin: 0px 0px 10px 0px; font-family: Arial,sans-serif; float: right;"><nobr>';
        $left_margin = '0px';
        foreach ($event_buttons as $event_button) { 
            $result .= '<a href="' . stripslashes($event_button->event_button_link) . '" ' ;
            $result .= 'style="background: #ffffff; border: 4px solid #8dc1d6; text-decoration: none; padding: 10px 8px; ';
            $result .= 'margin: 10px 0px 0px ' . $left_margin . '; ';
            $result .= 'color: #000000; border-radius: 4px; display: inline-block; mso-padding-alt: 0; text-underline-color: #ffffff;">';
            $result .= '<span style="mso-text-raise: 10pt; font-weight: bold;">' . stripslashes($event_button->event_button_text) . '</span></a>';
            $left_margin = "4px";
        }
        $result .='</nobr></span>';
    };
    return $result;
}
