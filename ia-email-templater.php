<?php
/*
Plugin Name: Indy Ambassadors Email Templater
Description: A custom plugin for generating the weekly newsletter by Indy Ambassadors. Requires The Events Calendar Plugin.
Version: 1.4
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
            event_featured varchar(4) DEFAULT 'off' NOT NULL,
            event_two_imgs varchar(4) DEFAULT 'off' NOT NULL,
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
            'event_featured' => 'off',
            'event_two_imgs' => 'off',
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

    $events = [];
    $index = 0;
    foreach ($post['ia-email-events'] as $k => $v) {
        if (!empty($events)) {
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
            $events[$index][key($v)] = $v[key($v)];
        }
    }

    for ($i = 0; $i < count($events); $i++) {
        if (!array_key_exists('event-featured', $events[$i])) {
            $events[$i]['event-featured'] = 'off';
        }
        if (!array_key_exists('event-two-imgs', $events[$i])) {
            $events[$i]['event-two-imgs'] = 'off';
        }
    }
    $the_events = $events;

    $wpdb->insert(
        $table_templater,
        array(
            'header_image_id' => $header_image_id,
            'header_text' => $header,
            'intro_paragraph' => $intro_paragraph,
            'intro_signature' => $intro_signature,
            'footer_signup' => $footer_signup,
            'footer_socials' => $footer_socials
        )
    );
    $last_id = $wpdb->insert_id;

    foreach ($the_events as $event) {
        if (!empty($event)) {
            $wpdb->insert(
                $table_events,
                array(
                    'email_id' => $last_id,
                    'tec_event_id' => $event['tec-dropdown'],
                    'event_featured' => $event['event-featured'],
                    'event_two_imgs' => $event['event-two-imgs'],
                    'event_header_text' => $event['event-header'],
                    'event_text' => $event['event-text']
                )
            );
            $event_id = $wpdb->insert_id;

            foreach ($event['event-image-id'] as $event_img) {
                $wpdb->insert(
                    $table_event_imgs,
                    array(
                        'event_id' => $event_id,
                        'event_img_id' => $event_img
                    )
                );
            }

            for ($i = 0; $i < count($event['event-button']['text']); $i++) {
                $wpdb->insert(
                    $table_event_buttons,
                    array(
                        'event_id' => $event_id,
                        'event_button_text' => $event['event-button']['text'][$i],
                        'event_button_link' => $event['event-button']['link'][$i]
                    )
                );
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
