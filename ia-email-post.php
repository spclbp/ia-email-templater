<?php

if (!empty($_POST)) {
    $image_url = $_POST['ia-email-image'];
    $header = $_POST['ia-email-title-text'];
    $intro_paragraph = $_POST['ia-email-intro-p'];

    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'ia_email_templater',
        array(
            'image_url' => $image_url,
            'header' => $header,
            'intro_paragraph' => $intro_paragraph
        ),
        array(
            '%s',
            '%s',
            '%s'
        )
    );
}
