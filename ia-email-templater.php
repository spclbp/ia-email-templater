<?php
/*
Plugin Name: Indy Ambassadors Email Templater
Description: A custom plugin for generating the weekly newsletter by Indy Ambassadors. Requires The Events Calendar Plugin.
Version: 1.10.0
Requires at least: 6.0
Requires PHP: 5.6
Author: Josh Klein
Contributor: Chris Blair
Author URI: https://jklein.me
*/

// Load local stubs only for non-WordPress tooling (linting/static analysis).
if (!defined('ABSPATH')) {
    require_once __DIR__ . '/wordpress-stubs.php';
}

function ia_email_templater_add()
{
    // Listed under the IA Newsletters menu, where the posts it creates live.
    add_submenu_page(
        'edit.php?post_type=ia_newsletter',
        __('IA Email Templater', 'textdomain'),
        'IA Email Templater',
        'manage_options',
        plugin_dir_path(__FILE__) . 'admin-view.php'
    );
}
add_action('admin_menu', 'ia_email_templater_add');

function ia_email_templater_enqueue()
{
    $admin_css = plugin_dir_path(__FILE__) . 'ia-email-admin.css';
    $admin_js = plugin_dir_path(__FILE__) . 'ia-email-script.js';
    wp_enqueue_style('ia-email-admin', plugin_dir_url(__FILE__) . 'ia-email-admin.css', array(), filemtime($admin_css));
    wp_enqueue_script('ia-email-script', plugin_dir_url(__FILE__) . 'ia-email-script.js', array(), filemtime($admin_js));
    wp_enqueue_media();
}
add_action('admin_enqueue_scripts', 'ia_email_templater_enqueue');

function ia_email_event_promotional_photo_meta_box()
{
    add_meta_box(
        'ia-email-event-promotional-photo',
        'Newsletter Promotional Photo',
        'ia_email_render_event_promotional_photo_meta_box',
        'tribe_events',
        'side',
        'default'
    );
}
add_action('add_meta_boxes_tribe_events', 'ia_email_event_promotional_photo_meta_box');

function ia_email_render_event_promotional_photo_meta_box($post)
{
    $photo_id = intval(get_post_meta($post->ID, 'ia_email_promotional_image_id', true));
    $event_emoji = get_post_meta($post->ID, 'ia_email_event_emoji', true);
    wp_nonce_field('ia_email_save_promotional_photo', 'ia_email_promotional_photo_nonce');
    ?>
    <div class="ia-email-event-promotional-photo">
        <label for="ia_email_event_emoji"><strong>Newsletter Event Emoji</strong></label>
        <div class="ia-email-event-emoji-picker">
            <input type="hidden" name="ia_email_event_emoji" id="ia_email_event_emoji" value="<?php echo esc_attr($event_emoji); ?>">
            <input type="text" id="ia_email_event_emoji_custom" class="ia-email-event-emoji-custom" value="<?php echo esc_attr($event_emoji); ?>" placeholder="Choose or enter an emoji" aria-label="Custom newsletter event emoji">
            <?php foreach (array('📅', '🙌', '🏃', '🎉', '🌳', '🧹', '❤️', '📣') as $emoji) { ?>
                <button type="button" class="button ia-email-event-emoji-option<?php echo $event_emoji === $emoji ? ' selected' : ''; ?>" data-emoji="<?php echo esc_attr($emoji); ?>" aria-label="Use <?php echo esc_attr($emoji); ?> emoji"><?php echo esc_html($emoji); ?></button>
            <?php } ?>
        </div>
        <p class="description">Choose the emoji used in the newsletter event title.</p>
        <img src="<?php echo esc_url(wp_get_attachment_image_url($photo_id, 'medium')); ?>" alt="Newsletter Promotional Photo Preview" class="ia-email-event-promotional-photo-preview">
        <input type="hidden" name="ia_email_promotional_image_id" class="ia-email-promotional-image-id" value="<?php echo $photo_id; ?>">
        <p>
            <button type="button" class="button ia-email-select-promotional-image">Set promotional photo</button>
            <button type="button" class="button ia-email-remove-promotional-image" <?php echo $photo_id ? '' : 'style="display:none;"'; ?>>Remove</button>
        </p>
    </div>
    <?php
}

function ia_email_save_event_promotional_photo($post_id)
{
    if (!isset($_POST['ia_email_promotional_photo_nonce']) || !wp_verify_nonce($_POST['ia_email_promotional_photo_nonce'], 'ia_email_save_promotional_photo')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $photo_id = isset($_POST['ia_email_promotional_image_id']) ? intval($_POST['ia_email_promotional_image_id']) : 0;
    $event_emoji = isset($_POST['ia_email_event_emoji']) ? sanitize_text_field($_POST['ia_email_event_emoji']) : '';
    if ($photo_id > 0) {
        update_post_meta($post_id, 'ia_email_promotional_image_id', $photo_id);
    } else {
        delete_post_meta($post_id, 'ia_email_promotional_image_id');
    }
    if ($event_emoji !== '') {
        update_post_meta($post_id, 'ia_email_event_emoji', $event_emoji);
    } else {
        delete_post_meta($post_id, 'ia_email_event_emoji');
    }
}
add_action('save_post_tribe_events', 'ia_email_save_event_promotional_photo');

function ia_email_register_promotional_photo_rest_field()
{
    register_rest_field(
        'tribe_events',
        'ia_email_event_emoji',
        array(
            'get_callback' => function ($event) {
                return get_post_meta($event['id'], 'ia_email_event_emoji', true);
            },
            'schema' => array(
                'description' => 'Emoji used in the newsletter event title.',
                'type' => 'string',
                'context' => array('view', 'edit')
            )
        )
    );

    register_rest_field(
        'tribe_events',
        'ia_email_promotional_image',
        array(
            'get_callback' => function ($event) {
                $photo_id = intval(get_post_meta($event['id'], 'ia_email_promotional_image_id', true));
                return array(
                    'id' => $photo_id,
                    'url' => $photo_id ? wp_get_attachment_image_url($photo_id, 'full') : '',
                    'alt' => ia_email_image_alt($photo_id)
                );
            },
            'schema' => array(
                'description' => 'Newsletter promotional photo for the event.',
                'type' => 'object',
                'context' => array('view', 'edit')
            )
        )
    );
}
add_action('rest_api_init', 'ia_email_register_promotional_photo_rest_field');

function ia_email_add_promotional_photo_to_event_rest_response($data, $event_id = 0)
{
    $event_id = $event_id ? intval($event_id) : (isset($data['id']) ? intval($data['id']) : 0);
    $photo_id = intval(get_post_meta($event_id, 'ia_email_promotional_image_id', true));
    $data['ia_email_event_emoji'] = get_post_meta($event_id, 'ia_email_event_emoji', true);
    $data['ia_email_promotional_image'] = array(
        'id' => $photo_id,
        'url' => $photo_id ? wp_get_attachment_image_url($photo_id, 'full') : '',
        'alt' => ia_email_image_alt($photo_id)
    );

    return $data;
}
add_filter('tribe_rest_event_data', 'ia_email_add_promotional_photo_to_event_rest_response', 10, 2);
add_filter('tribe_rest_single_event_data', 'ia_email_add_promotional_photo_to_event_rest_response', 10, 2);

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
            header_image_alt varchar(512) DEFAULT '' NOT NULL,
	    	header_text text NOT NULL,
	    	preview_text text NOT NULL,          
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
            event_position int NOT NULL,
            event_minimized varchar(4) DEFAULT 'no' NOT NULL,
            event_featured varchar(4) DEFAULT 'off' NOT NULL,
            event_two_imgs varchar(4) DEFAULT 'off' NOT NULL,
            event_img_right varchar(4) DEFAULT 'off' NOT NULL,
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
            event_img_link varchar(512) DEFAULT '' NOT NULL,
            event_img_alt varchar(512) DEFAULT '' NOT NULL,
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
            'header_image_alt' => '',
            'header_text' => 'Example Header',
            'preview_text' => 'Exmaple Preview Text',
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
            'event_img_right' => 'off',
            'event_divider' => 'off',
            'event_mute' => 'on',
            'event_header_text' => 'Initial Header',
            'event_text' => 'Initial event text.'
        )
    );

    $last_id = $wpdb->insert_id;
    $wpdb->insert(
        $table_event_imgs,
        array(
            'event_id' => $last_id,
            'event_img_id' => '',
            'event_img_link' => '',
            'event_img_alt' => ''
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

// Adds columns introduced after initial release for sites that already have the tables installed.
function ia_email_maybe_upgrade_db()
{
    global $wpdb;

    $added_columns = array(
        $wpdb->prefix . 'ia_email_event_imgs' => array(
            'event_img_link' => "varchar(512) DEFAULT '' NOT NULL",
            'event_img_alt' => "varchar(512) DEFAULT '' NOT NULL"
        ),
        $wpdb->prefix . 'ia_email_templater' => array(
            'header_image_alt' => "varchar(512) DEFAULT '' NOT NULL"
        )
    );

    foreach ($added_columns as $table => $columns) {
        foreach ($columns as $column => $definition) {
            $column_exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME,
                    $table,
                    $column
                )
            );

            if (intval($column_exists) === 0) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN $column $definition");
            }
        }
    }
}
add_action('plugins_loaded', 'ia_email_maybe_upgrade_db');

function ia_email_post($post)
{
    global $wpdb;
    $table_templater = $wpdb->prefix . 'ia_email_templater';
    $table_events = $wpdb->prefix . 'ia_email_events';
    $table_event_imgs = $wpdb->prefix . 'ia_email_event_imgs';
    $table_event_buttons = $wpdb->prefix . 'ia_email_event_buttons';

    $header_image_id = $post['ia-email-header-image'];
    $header_image_alt = $post['ia-email-header-image-alt'] ?? '';
    $header = $post['ia-email-title-text'];
    $intro_paragraph = $post['ia-email-intro-p'];
    $intro_signature = $post['ia-email-intro-signature'];
    $footer_signup = $post['ia-email-footer-signup'];
    $footer_socials = $post['ia-email-footer-socials'];

    // instead of all events, this will become a list of changing events?
    $events = [];
    $index = 0;
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

            [12?]=> array(1) { ["event-two-imgs"]=> string(2) "on" } 
            [12?]=> array(1) { ["event-header"]=> string(11) "Third Event" } 
            [12?]=> array(1) { ["event-image-id"]=> array(1) { [0]=> string(1) "1" } } 
            [12?]=> array(1) { ["event-image-image-id"]=> array(1) { [0]=> string(5) "22555" } } 
            [12?]=> array(1) { ["event-image-id"]=> array(1) { [0]=> string(0) "" } }           <--- a new secondary image added
            [12?]=> array(1) { ["event-image-image-id"]=> array(1) { [0]=> string(5) "22339" } } 

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
    $img_index = -1;
    foreach ($post['ia-email-events'] as $k => $v) {
        if (!empty($events)) {
            if (key($v) == 'event-image-id') {
                $img_index++;
                $events[$index]['images'][$img_index]["event-image-id"] = $v[key($v)][0];
            } elseif (key($v) == 'event-image-image-id') {
                $events[$index]['images'][$img_index]['event-image-image-id'] = $v[key($v)][0];
                //$events[$index]['images'][$img_index]->eventImageImageId=$v[key($v)];
            } elseif (key($v) == 'event-image-link') {
                $events[$index]['images'][$img_index]['event-image-link'] = $v[key($v)][0];
            } elseif (key($v) == 'event-image-alt') {
                $events[$index]['images'][$img_index]['event-image-alt'] = $v[key($v)][0];
            } elseif (array_key_exists('event-button', $events[$index]) && key($v) == 'event-button') {
                $events[$index]['event-button'] = array_merge_recursive($events[$index]['event-button'], $v[key($v)]);
            } elseif (array_key_exists(key($v), $events[$index])) {
                $index++;
                $events[$index][key($v)] = $v[key($v)];
                $events[$index]['images'] = [];
                $img_index = -1;
            } else {
                $events[$index][key($v)] = $v[key($v)];
            }
        } else {
            //Key() returns the index of current element of the given array. 
            //Example: $events[0]["event-minimized"]="yes"
            $events[$index][key($v)] = $v[key($v)];
        }
    }
    //print var_dump($events);
    /* Post loop processing the same event has a single, complex structure
    [12]=> array(8) { 
        ["event-minimized"]=> string(3) "yes" 
        ["tec-dropdown"]=> string(1) "0" 
        ["event-featured"]=> string(2) "on" 
        ["event-mute"]=> string(2) "on" 
        ["event-header"]=> string(79) "500 Festival 3-Miler Course Marshal" 
        ["images"]=> array(2) { 
            [0]=> array(2) { 
                ["event-image-id"]=> string(3) "170" 
                ["event-image-image-id"]=> string(5) "22204" 
            } 
            [1]=> array(2) { 
                ["event-image-id"]=> string(0) "" 
                ["event-image-image-id"]=> string(5) "22557" 
            } 
        } 
        ["event-text"]=> string(303) "Provide crowd support and directional assistance..." 
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
    $event_position=1;
    for ($i = 0; $i < count($events); $i++) {
        if ($events[$i]['event-header'] != 'delete') {
            $events[$i]['event-position'] = $event_position++;
        }
        if (!array_key_exists('event-featured', $events[$i])) {
            $events[$i]['event-featured'] = 'off';
        }
        if (!array_key_exists('event-two-imgs', $events[$i])) {
            $events[$i]['event-two-imgs'] = 'off';
        }
        if (!array_key_exists('event-img-right', $events[$i])) {
            $events[$i]['event-img-right'] = 'off';
        }
        if (!array_key_exists('event-divider', $events[$i])) {
            $events[$i]['event-divider'] = 'off';
        }
        if (!array_key_exists('event-mute', $events[$i])) {
            $events[$i]['event-mute'] = 'off';
        }
        if (!array_key_exists('event-img-right', $events[$i])) {
            $events[$i]['event-img-right'] = 'off';
        }
    }
    $the_events = $events;

    $templater_row_exists = intval( $wpdb->get_var( "SELECT COUNT(*) FROM $table_templater WHERE id = 1" ) );
    if ( $templater_row_exists === 0 ) {
        $wpdb->insert(
            $table_templater,
            array(
                'id'               => 1,
                'header_image_id'  => intval( $header_image_id ),
                'header_image_alt' => $header_image_alt,
                'header_text'      => $header,
                'preview_text'     => '',
                'intro_paragraph'  => $intro_paragraph,
                'intro_signature'  => $intro_signature,
                'footer_signup'    => $footer_signup,
                'footer_socials'   => $footer_socials,
            ),
            array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );
    } else {
        $wpdb->update(
            $table_templater,
            array(
                'header_image_id'  => intval( $header_image_id ),
                'header_image_alt' => $header_image_alt,
                'header_text'      => $header,
                'intro_paragraph'  => $intro_paragraph,
                'intro_signature'  => $intro_signature,
                'footer_signup'    => $footer_signup,
                'footer_socials'   => $footer_socials,
            ),
            array( 'id' => 1 ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );
    }
    $last_id = 1;

    foreach ($the_events as $event) {
        if (!empty($event)) {
            if (!empty($event['event-id'])) {
                $event_id = $event['event-id'];
                if ( isset( $event['event-unchanged'] ) && $event['event-unchanged'] === 'yes' ) {
                    continue;
                }
                if ($event['event-header'] == 'delete') {
                    $wpdb->delete(
                        $table_event_buttons,
                        array('event_id' => $event_id)
                    );
                    $wpdb->delete(
                        $table_event_imgs,
                        array('event_id' => $event_id)
                    );
                    $wpdb->delete(
                        $table_events,
                        array('id' => $event_id)
                    );
                } else {
                    $wpdb->update(
                        $table_events,
                        array(
                            'email_id' => $last_id,
                            'tec_event_id' => $event['tec-dropdown'],
                            'event_position' => $event['event-position'],
                            'event_minimized' => $event['event-minimized'],
                            'event_featured' => $event['event-featured'],
                            'event_two_imgs' => $event['event-two-imgs'],
                            'event_img_right' => $event['event-img-right'],
                            'event_divider' => $event['event-divider'],
                            'event_mute' => $event['event-mute'],
                            'event_header_text' => $event['event-header'],
                            'event_text' => $event['event-text']
                        ),
                        array('id' => $event_id)
                    );
                };
            } else {
                $wpdb->insert(
                    $table_events,
                    array(
                        'email_id' => $last_id,
                        'tec_event_id' => $event['tec-dropdown'],
                        'event_position' => $event['event-position'],
                        'event_minimized' => $event['event-minimized'],
                        'event_featured' => $event['event-featured'],
                        'event_two_imgs' => $event['event-two-imgs'],
                        'event_img_right' => $event['event-img-right'],
                        'event_divider' => $event['event-divider'],
                        'event_mute' => $event['event-mute'],
                        'event_header_text' => $event['event-header'],
                        'event_text' => $event['event-text']
                    )
                 );
                $event_id = $wpdb->insert_id;
            }
            
            for ($i = 0; $i < count($event['images']); $i++) {
                //print 'image number: ' . $event['images'][$i]['event-image-id'];
                //print 'image image number: ' . $event['images'][$i]['event-image-image-id'];
                if (!empty($event['images'][$i]['event-image-id'])) {
                    if ((empty($event['images'][$i]['event-image-image-id'])) || ($event['images'][$i]['event-image-image-id'] == 0)) {
                        $wpdb->delete(
                            $table_event_imgs,
                            array('id' => $event['images'][$i]['event-image-id'])
                        );
                    } else {
                        $wpdb->update(
                            $table_event_imgs,
                            array(
                                'event_img_id' => $event['images'][$i]['event-image-image-id'],
                                'event_img_link' => $event['images'][$i]['event-image-link'] ?? '',
                                'event_img_alt' => $event['images'][$i]['event-image-alt'] ?? '',
                            ),
                            array('id' => $event['images'][$i]['event-image-id'])
                        );
                    }
                } elseif ((!empty($event['images'][$i]['event-image-image-id'])) && ($event['images'][$i]['event-image-image-id'] > 0)) {
                    $wpdb->insert(
                        $table_event_imgs,
                        array(
                            'event_id' => $event_id,
                            'event_img_id' => $event['images'][$i]['event-image-image-id'],
                            'event_img_link' => $event['images'][$i]['event-image-link'] ?? '',
                            'event_img_alt' => $event['images'][$i]['event-image-alt'] ?? ''
                        )
                    );
                }
            }

            for ($i = 0; $i < count($event['event-button']['text']); $i++) {
                //print "button id: " . $event['event-button']['id'][$i]; 
                if (!empty($event['event-button']['id'][$i])) {
                    if ($event['event-button']['text'][$i] == 'delete') {
                        $wpdb->delete(
                            $table_event_buttons,
                            array('id' => $event['event-button']['id'][$i])
                        );
                    } else {
                        $wpdb->update(
                            $table_event_buttons,
                            array(
                                'event_id' => $event_id,
                                'event_button_text' => $event['event-button']['text'][$i],
                                'event_button_link' => $event['event-button']['link'][$i]
                            ),
                            array('id' => $event['event-button']['id'][$i])
                        );
                    }
                } elseif ($event['event-button']['text'][$i] != 'delete') {
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
                //"SELECT * FROM $table_events WHERE email_id = (SELECT MAX(email_id) FROM $table_events)"
                "SELECT * FROM $table_events WHERE email_id = 1 ORDER BY event_position ASC"
            )
        );
    } else {
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT $var FROM $table_templater WHERE id = 1 LIMIT 1"
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

/* Alt text for one graphic: the value typed into the templater, falling back to the alt text
   stored on the attachment itself so older rows keep whatever the media library already had. */
function ia_email_image_alt($attachment_id, $alt = '')
{
    $alt = trim((string) stripslashes((string) $alt));
    if ($alt !== '') {
        return $alt;
    }

    $attachment_id = intval($attachment_id);
    return $attachment_id ? (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true) : '';
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

/* The codepoint ranges this plugin treats as emoji. Shared so the strip and the normalize helpers
   cannot drift apart, and includes the variation selector and zero-width joiner that bind
   multi-codepoint sequences such as flags and family emoji together. */
function ia_email_emoji_codepoint_ranges()
{
    return array(
        array(0x1F1E6, 0x1F1FF),
        array(0x1F300, 0x1F5FF),
        array(0x1F600, 0x1F64F),
        array(0x1F680, 0x1F6FF),
        array(0x1F700, 0x1F77F),
        array(0x1F780, 0x1F7FF),
        array(0x1F800, 0x1F8FF),
        array(0x1F900, 0x1F9FF),
        array(0x1FA00, 0x1FAFF),
        array(0x2600, 0x26FF),
        array(0x2700, 0x27BF),
        array(0x200D, 0x200D),
        array(0xFE0F, 0xFE0F)
    );
}

function ia_email_is_emoji_codepoint($codepoint)
{
    foreach (ia_email_emoji_codepoint_ranges() as $range) {
        if ($codepoint >= $range[0] && $codepoint <= $range[1]) {
            return true;
        }
    }

    return false;
}

// Removes emoji characters while preserving regular punctuation and text.
function ia_email_strip_emojis($text)
{
    if (!is_string($text) || $text === '') {
        return $text;
    }

    $character_class = '';
    foreach (ia_email_emoji_codepoint_ranges() as $range) {
        $character_class .= $range[0] === $range[1]
            ? sprintf('\x{%04X}', $range[0])
            : sprintf('\x{%04X}-\x{%04X}', $range[0], $range[1]);
    }

    return preg_replace('/[' . $character_class . ']/u', '', $text);
}

function ia_email_register_newsletter_post_type()
{
    register_post_type(
        'ia_newsletter',
        array(
            'labels' => array(
                'name' => __('IA Newsletters', 'textdomain'),
                'singular_name' => __('IA Newsletter', 'textdomain')
            ),
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-email',
            'supports' => array('title', 'editor', 'excerpt', 'revisions', 'thumbnail'),
            'rewrite' => array('slug' => 'ia-newsletters')
        )
    );
}
add_action('init', 'ia_email_register_newsletter_post_type');

/* The Featured Image panel needs two separate things to be true, and either can be undone by code
   that loads after this plugin, so both are reasserted late on init, again on wp_loaded (after
   every init callback, before the editor or REST API checks either one), and whenever anything
   re-registers the post type:

   - The post type must support thumbnails. The supports list above covers that, unless another
     plugin re-registers a post type named 'ia_newsletter' without it.
   - The theme must allow post thumbnails for this post type. A theme that passes an explicit list
     of post types to add_theme_support leaves custom types out. add_theme_support merges into that
     list rather than replacing it, and bails out when the theme already supports every post type,
     so calling it here cannot narrow what the theme asked for. */
function ia_email_newsletter_thumbnail_support()
{
    add_post_type_support('ia_newsletter', 'thumbnail');
    add_theme_support('post-thumbnails', array('ia_newsletter'));
}
add_action('init', 'ia_email_newsletter_thumbnail_support', 99);
add_action('wp_loaded', 'ia_email_newsletter_thumbnail_support');

function ia_email_newsletter_reregistered($post_type)
{
    if ($post_type === 'ia_newsletter') {
        add_post_type_support('ia_newsletter', 'thumbnail');
    }
}
add_action('registered_post_type', 'ia_email_newsletter_reregistered');

/* Explains why the Featured Image panel is missing for newsletters, or returns '' when both
   requirements hold. Shown after creating a newsletter post so a live site can be diagnosed. */
function ia_email_newsletter_thumbnail_problem()
{
    $problems = array();
    if (!post_type_supports('ia_newsletter', 'thumbnail')) {
        $problems[] = 'the ia_newsletter post type does not support thumbnails (another plugin may register an "ia_newsletter" post type too)';
    }
    if (!current_theme_supports('post-thumbnails', 'ia_newsletter')) {
        $theme_support = get_theme_support('post-thumbnails');
        $problems[] = 'the active theme does not allow featured images for newsletters (theme allows: '
            . (is_array($theme_support) && isset($theme_support[0]) ? implode(', ', (array) $theme_support[0]) : 'none')
            . ')';
    }
    return implode('; ', $problems);
}

/* Themes disagree about the featured image on a custom post type's page: some never print it, some
   only use it as a background or hidden hero, and page builders render content outside the loop.
   So the newsletter page always gets its header graphic as the first thing in the content, and the
   theme's own copy is suppressed there so it never shows twice. Archives and other pages keep the
   theme's thumbnails. */
function ia_email_is_newsletter_page_post($post_id)
{
    return is_singular('ia_newsletter') && intval($post_id) === get_queried_object_id();
}

function ia_email_hide_theme_newsletter_thumbnail($html, $post_id)
{
    return ia_email_is_newsletter_page_post($post_id) ? '' : $html;
}
add_filter('post_thumbnail_html', 'ia_email_hide_theme_newsletter_thumbnail', 10, 2);

function ia_email_newsletter_header_in_content($content)
{
    $post_id = get_the_ID();
    $thumbnail_id = $post_id ? get_post_thumbnail_id($post_id) : 0;
    if (!$thumbnail_id || !ia_email_is_newsletter_page_post($post_id)) {
        return $content;
    }

    // wp_get_attachment_image skips post_thumbnail_html, so the filter above does not blank it.
    $header = wp_get_attachment_image($thumbnail_id, 'full', false, array('style' => 'width: 100%; height: auto;'));
    return '<figure class="ia-email-newsletter-header">' . $header . '</figure>' . $content;
}
add_filter('the_content', 'ia_email_newsletter_header_in_content', 5);

function ia_email_flush_newsletter_rewrites()
{
    ia_email_register_newsletter_post_type();
    ia_email_register_substack_feed();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ia_email_flush_newsletter_rewrites');

/* Full-text feed of the newest newsletters for Substack's RSS importer, served at /feed/substack/
   (or ?feed=substack with plain permalinks). Substack takes the post body from content:encoded,
   which opens with the header banner (the featured image). */
function ia_email_register_substack_feed()
{
    add_feed('substack', 'ia_email_render_substack_feed');
}
add_action('init', 'ia_email_register_substack_feed');

/* add_feed and a changed post type slug only take effect once rewrite rules are rebuilt. Activation
   handles new installs; a site that updated the plugin in place gets one rebuild here per bump of the
   stored version ('2' added the ia-newsletters slug). Later rebuilds include both on their own
   because they are registered on every init. */
function ia_email_maybe_flush_substack_feed_rewrites()
{
    if (get_option('ia_email_substack_feed_rewrites') !== '2') {
        flush_rewrite_rules(false);
        update_option('ia_email_substack_feed_rewrites', '2');
    }
}
add_action('init', 'ia_email_maybe_flush_substack_feed_rewrites', 100);

// Plain text for an XML element: no tags, and no HTML-only entities such as &nbsp;.
function ia_email_xml_text($text)
{
    $text = trim(html_entity_decode(wp_strip_all_tags((string) $text), ENT_QUOTES, 'UTF-8'));
    return htmlspecialchars(ia_email_xml_safe($text), ENT_QUOTES | ENT_XML1, 'UTF-8');
}

// Drops control characters that XML 1.0 forbids even inside CDATA.
function ia_email_xml_safe($text)
{
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $text);
}

function ia_email_cdata($text)
{
    // A literal ]]> would end the section early, so split it across two sections.
    return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', ia_email_xml_safe($text)) . ']]>';
}

/* Email-service placeholders such as {unsubscription_url}, {{first_name}} or *|FNAME|* are never
   filled in on Substack. A link that points at one (an unsubscribe link, usually) is removed along
   with its text; any other placeholder is deleted. */
function ia_email_strip_merge_tags($html)
{
    $placeholder = '(?:(?:\{\{?|%7B(?:%7B)?)\s*[a-z][a-z0-9_.-]*\s*(?:\}\}?|%7D(?:%7D)?)|\*\|[a-z0-9_:]+\|\*)';
    $html = preg_replace('~<a\b[^>]*\bhref\s*=\s*(["\'])[^"\']*' . $placeholder . '[^"\']*\1[^>]*>.*?</a>~is', '', $html);
    // "Hi {first_name}, ..." should read "Hi, ...", not "Hi , ...".
    return preg_replace('~(?:[ \t]*' . $placeholder . '(?=[,.;:!?]))|' . $placeholder . '~i', '', $html);
}

// Trims whitespace, &nbsp; and stray <br> from both ends of each heading; drops headings left empty.
function ia_email_trim_headings($html)
{
    $space = '(?:\s|&nbsp;|&#0*160;|&#x0*a0;|\xC2\xA0|<br\s*/?>)*';
    $html = preg_replace_callback('~(<h([1-6])\b[^>]*>)(.*?)(</h\2>)~is', function ($matches) use ($space) {
        return $matches[1] . preg_replace('~^' . $space . '|' . $space . '$~i', '', $matches[3]) . $matches[4];
    }, $html);
    return preg_replace('~<h([1-6])\b[^>]*></h\1>~i', '', $html);
}

/* Substack copies the HTML onto another domain, so a root-relative or relative src/href would break.
   Resolves them against $base_url; URLs with a scheme (https:, mailto:, tel:, data:) and in-page
   #anchors are left alone. */
function ia_email_absolute_urls($html, $base_url)
{
    return preg_replace_callback('~(\s(?:src|href)\s*=\s*)(["\'])(.*?)\2~i', function ($matches) use ($base_url) {
        $url = trim(html_entity_decode($matches[3], ENT_QUOTES, 'UTF-8'));
        if ($url === '' || preg_match('~^(?:[a-z][a-z0-9+.-]*:|#)~i', $url)) {
            return $matches[0];
        }
        return $matches[1] . $matches[2] . esc_url(WP_Http::make_absolute_url($url, $base_url)) . $matches[2];
    }, $html);
}

function ia_email_substack_content($html, $base_url)
{
    $html = ia_email_emoji_to_unicode($html);
    $html = ia_email_strip_merge_tags($html);
    $html = ia_email_trim_headings($html);
    $html = ia_email_absolute_urls($html, $base_url);
    // Removing placeholders can leave a paragraph with nothing in it.
    $html = preg_replace('#<p>(?:\s|&nbsp;|<br\s*/?>)*</p>#i', '', $html);
    return trim($html);
}

/* Substack ignores enclosure and media:content, so the header banner (the featured image) also leads
   the body. The newsletter page gets it from ia_email_newsletter_header_in_content, which only runs
   on the single-post view, so it is never in the feed body twice. */
function ia_email_substack_header_html($post_id)
{
    $thumbnail_id = get_post_thumbnail_id($post_id);
    $url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'full') : '';
    if (empty($url)) {
        return '';
    }
    $alt = ia_email_image_alt($thumbnail_id);
    return '<figure><img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" /></figure>';
}

// <enclosure> and <media:content> for the featured image (the newsletter header), or '' if none.
function ia_email_substack_image_tags($post_id)
{
    $thumbnail_id = get_post_thumbnail_id($post_id);
    $image = $thumbnail_id ? wp_get_attachment_image_src($thumbnail_id, 'full') : false;
    if (empty($image)) {
        return '';
    }

    $url = esc_url(WP_Http::make_absolute_url($image[0], home_url('/')));
    $type = esc_attr(get_post_mime_type($thumbnail_id));
    $file = get_attached_file($thumbnail_id);
    // RSS 2.0 requires a byte length; 0 is the accepted value when it is unknown (e.g. offloaded media).
    $length = ($file && file_exists($file)) ? intval(filesize($file)) : 0;
    $alt = ia_email_xml_text(ia_email_image_alt($thumbnail_id));

    $tags = '<enclosure url="' . $url . '" length="' . $length . '" type="' . $type . '" />' . "\n";
    $tags .= '<media:content url="' . $url . '" medium="image" type="' . $type . '"'
        . (!empty($image[1]) ? ' width="' . intval($image[1]) . '"' : '')
        . (!empty($image[2]) ? ' height="' . intval($image[2]) . '"' : '')
        . '>' . ($alt !== '' ? '<media:description type="plain">' . $alt . '</media:description>' : '')
        . '</media:content>';
    return $tags;
}

function ia_email_render_substack_feed()
{
    $newsletters = new WP_Query(
        array(
            'post_type' => 'ia_newsletter',
            'post_status' => 'publish',
            'posts_per_page' => 2,
            'orderby' => 'date',
            'order' => 'DESC',
            'ignore_sticky_posts' => true,
            'no_found_rows' => true
        )
    );

    $date_format = 'D, d M Y H:i:s +0000';
    $last_build = empty($newsletters->posts)
        ? gmdate($date_format)
        : get_post_modified_time($date_format, true, $newsletters->posts[0]);
    $archive_link = get_post_type_archive_link('ia_newsletter');

    header('Content-Type: application/rss+xml; charset=UTF-8', true);
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:atom="http://www.w3.org/2005/Atom"
    xmlns:media="http://search.yahoo.com/mrss/">
<channel>
    <title><?php echo ia_email_xml_text(get_bloginfo('name')); ?></title>
    <link><?php echo esc_url($archive_link ? $archive_link : home_url('/')); ?></link>
    <atom:link href="<?php echo esc_url(get_feed_link('substack')); ?>" rel="self" type="application/rss+xml" />
    <description><?php echo ia_email_xml_text(get_bloginfo('description')); ?></description>
    <language><?php echo ia_email_xml_text(get_bloginfo('language')); ?></language>
    <lastBuildDate><?php echo $last_build; ?></lastBuildDate>
<?php
    while ($newsletters->have_posts()) {
        $newsletters->the_post();
        $post_id = get_the_ID();
        $permalink = get_permalink($post_id);
        // The raw field, not get_the_content(), so <!--more--> and <!--nextpage--> cannot cut the body short.
        $content = ia_email_substack_header_html($post_id) . "\n\n"
            . apply_filters('the_content', get_post_field('post_content', $post_id));
?>
    <item>
        <title><?php echo ia_email_xml_text(get_the_title($post_id)); ?></title>
        <link><?php echo esc_url($permalink); ?></link>
        <guid isPermaLink="true"><?php echo esc_url($permalink); ?></guid>
        <pubDate><?php echo get_post_time($date_format, true, $post_id); ?></pubDate>
        <dc:creator><?php echo ia_email_cdata(get_the_author()); ?></dc:creator>
        <description><?php echo ia_email_xml_text(get_the_excerpt($post_id)); ?></description>
        <content:encoded><?php echo ia_email_cdata(ia_email_substack_content($content, $permalink)); ?></content:encoded>
        <?php echo ia_email_substack_image_tags($post_id); ?>

    </item>
<?php
    }
    wp_reset_postdata();
?>
</channel>
</rss>
<?php
}

// Tags allowed in newsletter post_content. No style/class/table markup survives.
function ia_email_semantic_allowed_html()
{
    return array(
        'p' => array(),
        'br' => array(),
        'strong' => array(),
        'b' => array(),
        'em' => array(),
        'i' => array(),
        'u' => array(),
        'a' => array('href' => true, 'title' => true),
        'ul' => array(),
        'ol' => array(),
        'li' => array(),
        'h2' => array(),
        'h3' => array(),
        'h4' => array(),
        'blockquote' => array(),
        'figure' => array(),
        'img' => array('src' => true, 'alt' => true)
    );
}

// TinyMCE can store emojis as <img class="emoji">; swap them back to the emoji character.
function ia_email_emoji_images_to_text($html)
{
    return preg_replace_callback('/<img\b[^>]*>/i', function ($matches) {
        if (!preg_match('/\bclass\s*=\s*["\'][^"\']*\b(?:emoji|wp-smiley)\b/i', $matches[0])) {
            return $matches[0];
        }
        return preg_match('/\balt\s*=\s*(["\'])(.*?)\1/i', $matches[0], $alt) ? $alt[2] : '';
    }, $html);
}

/* Every output format carries real Unicode emoji characters. Two things get in the way: the editor
   stores emoji as <img class="emoji"> tags, and WordPress writes numeric HTML entities when a
   column cannot hold a four-byte character. Undo both. Expects unslashed input. */
function ia_email_emoji_to_unicode($html)
{
    $html = ia_email_emoji_images_to_text((string) $html);

    return preg_replace_callback('/&#(x[0-9a-f]+|[0-9]+);/i', function ($matches) {
        $codepoint = (stripos($matches[1], 'x') === 0)
            ? hexdec(substr($matches[1], 1))
            : intval($matches[1]);

        return ia_email_is_emoji_codepoint($codepoint)
            ? html_entity_decode($matches[0], ENT_QUOTES, 'UTF-8')
            : $matches[0];
    }, $html);
}

// Single-line values such as row headers: inline formatting only.
function ia_email_semantic_inline($text)
{
    $allowed = array('strong' => array(), 'b' => array(), 'em' => array(), 'i' => array());
    return trim(wp_kses(ia_email_emoji_to_unicode(stripslashes((string) $text)), $allowed));
}

// Rich editor values: paragraphs via wpautop, then strip everything non-semantic.
function ia_email_semantic_text($html)
{
    $html = ia_email_emoji_to_unicode(stripslashes((string) $html));
    $html = wp_kses(wpautop($html), ia_email_semantic_allowed_html());
    $html = preg_replace('#<p>(?:\s|&nbsp;|<br\s*/?>)*</p>#i', '', $html);
    return trim($html);
}

function ia_email_semantic_image($attachment_id, $link = '', $alt = '')
{
    $url = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'full') : '';
    if (empty($url)) {
        return '';
    }
    $alt = ia_email_image_alt($attachment_id, $alt);
    $img = '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" />';
    $link = trim((string) stripslashes($link));
    if ($link !== '') {
        $img = '<a href="' . esc_url($link) . '">' . $img . '</a>';
    }
    return '<figure>' . $img . '</figure>';
}

function ia_email_semantic_buttons($event_buttons)
{
    $links = array();
    foreach ($event_buttons as $event_button) {
        $text = trim(stripslashes($event_button->event_button_text));
        $link = trim(stripslashes($event_button->event_button_link));
        if ($text !== '' && $link !== '') {
            $links[] = '<a href="' . esc_url($link) . '">' . esc_html($text) . '</a>';
        }
    }
    return empty($links) ? '' : '<p>' . implode(' | ', $links) . '</p>';
}

/* Builds the newsletter body from the saved field values (the same records that drive the
   email preview) rather than parsing the email HTML, so no table/CSS wrappers exist to strip.
   Section order mirrors admin-view.php's preview. */
function ia_email_build_semantic_html()
{
    // The header graphic is the post's featured image, so it is not repeated in the content.
    $out = array();

    $events = ia_email_get('events');
    foreach ($events as $event) {
        if ($event->event_mute === 'on' || $event->event_featured !== 'on') {
            continue;
        }
        $header = trim(ia_email_strip_emojis(ia_email_semantic_inline($event->event_header_text)));
        $text = ia_email_semantic_text($event->event_text);

        if ($event->event_divider === 'on') {
            if ($header !== '') {
                $out[] = '<h2>' . $header . '</h2>';
            }
            $out[] = $text;
            continue;
        }
        // The preview renders a header with no body text invisibly, so leave it out here.
        if ($text === '') {
            continue;
        }
        if ($header !== '') {
            $out[] = '<h3>' . $header . '</h3>';
        }
        foreach (array_slice(ia_email_get_imgs($event->id), 0, 2) as $event_img) {
            $out[] = ia_email_semantic_image(intval($event_img->event_img_id), $event_img->event_img_link, $event_img->event_img_alt);
        }
        $out[] = $text;
        $out[] = ia_email_semantic_buttons(ia_email_get_buttons($event->id));
    }

    $more_events = array();
    foreach ($events as $event) {
        if ($event->event_mute === 'on' || $event->event_featured === 'on') {
            continue;
        }
        $title = ia_email_semantic_inline($event->event_header_text);
        $event_buttons = ia_email_get_buttons($event->id);
        $link = empty($event_buttons) ? '' : trim(stripslashes($event_buttons[0]->event_button_link));
        // One paragraph per event, like the email's rows; the title's leading emoji stands in for a bullet.
        $more_events[] = '<p>' . ($link !== '' ? '<a href="' . esc_url($link) . '">' . $title . '</a>' : $title) . '</p>';
    }
    if (!empty($more_events)) {
        $out[] = '<h2>More Volunteer Events</h2>';
        $out[] = implode("\n", $more_events);
    }

    $out[] = '<p><a href="https://www.indyambassadors.org/events/">👀 See Our Full Calendar! 📅</a></p>';
    $out[] = '<h2>Are we missing any opportunities?</h2>';
    $out[] = '<p>Let us know! It could be in our next issue.<br />Submit '
        . '<a href="https://www.indyambassadors.org/events/community/add">an event</a>, '
        . '<a href="https://www.indyambassadors.org/add-ongoing/">an ongoing opportunity</a>, or '
        . '<a href="mailto:volunteeradmin@indyambassadors.org">email us.</a></p>';

    return implode("\n\n", array_filter($out, 'strlen'));
}

// Creates a draft newsletter post; returns the post ID or a WP_Error.
function ia_email_save_newsletter_post()
{
    $post_id = wp_insert_post(
        array(
            'post_type' => 'ia_newsletter',
            'post_status' => 'draft',
            'post_title' => sprintf(__('Newsletter – %s', 'textdomain'), date_i18n(get_option('date_format'))),
            // wp_insert_post unslashes its input, so slash to preserve backslashes in content.
            'post_content' => wp_slash(ia_email_build_semantic_html())
        ),
        true
    );

    if (is_wp_error($post_id) || empty($post_id)) {
        return $post_id;
    }

    // The newsletter header graphic doubles as the post's featured image.
    $header_image_id = intval(ia_email_get('header_image_id'));
    if ($header_image_id && wp_attachment_is_image($header_image_id)) {
        set_post_thumbnail($post_id, $header_image_id);
    }

    return $post_id;
}
