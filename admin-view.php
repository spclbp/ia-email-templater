<?php

use AIOSEO\Plugin\Common\Main\Media;
use AIOSEO\Plugin\Common\Models\Model;

if (!empty($_POST)) {
    ia_email_post($_POST);
}
?>

<div class="wrap">
    <div class="ia-email-admin-header">
        <h1>Indy Ambassadors Email Templater</h1>
    </div>
    <div class="ia-email-admin-inputs">
        <div class="ia-email-admin-inputs-left">
            <h3 class="ia-email-templater-header">Newsletter Content</h3>
            <form method="post" action="<?php echo the_permalink(); ?>">
                <div class="ia-email-admin-inputs-left-text">
                    <label for="ia-email-header-image">Header Image</label>
                    <div class="ia-email-header-image-wrapper">
                        <img src="<?php echo wp_get_attachment_image_url(ia_email_get('header_image_id'), 'full'); ?>" alt="Header Image Preview" class="ia-email-header-image">
                        <input type="hidden" name="ia-email-header-image" class="ia-email-header-image-id" value="<?php echo ia_email_get('header_image_id'); ?>">
                        <input type="button" value="Choose Image" class="ia-email-button ia-email-select-image-header">
                    </div>
                    <div style="display:none;"><!--I tried commenting this section out, but saves stopped working -->
                        <label for="ia-email-title-text">Header Text</label>
                        <input type="text" name="ia-email-title-text" id="ia-email-title-text" value="<?php echo stripslashes(ia_email_get('header_text')); ?>"></input>
                        <label for="ia-email-intro-p">Intro Text</label>
                        <?php
                        wp_editor(
                            stripslashes(ia_email_get('intro_paragraph')),
                            'ia-email-intro-p',
                            array(
                                'media_buttons' => false,
                                'textarea_rows' => '10',
                                'textarea_name' => 'ia-email-intro-p'
                            )
                        );
                        ?>
                        <label for="ia-email-intro-signature">Intro Signature</label>
                        <input type="text" name="ia-email-intro-signature" id="ia-email-intro-signature" value="<?php echo ia_email_get('intro_signature'); ?>"></input>
                    </div>
                    <div class="ia-email-events-wrapper">
                        <?php
                        $events = ia_email_get('events');
                        foreach ($events as $key => $event) {
                            if ($event->event_minimized == "no") { ?>
                                <div class="ia-email-events-row">
                                    <input type="hidden" name="ia-email-events[][event-minimized]" value="no"></input> <?php 
                            } else { ?>
                                 <div class="ia-email-events-row ia-email-events-row-hide">
                                    <input type="hidden" name="ia-email-events[][event-minimized]" value="yes"></input> <?php 
                            } ?>
                                    <div class="ia-email-events-row-header">
                                        <h3 class="ia-email-events-row-header-text">Row</h3>
                                        <p class="ia-email-events-row-header-label"> <?php 
                                            if (!empty($event->event_header_text)) {
                                                echo esc_html(stripslashes($event->event_header_text));
                                            } else {
                                                echo esc_html(substr(stripslashes($event->event_text),0,60) . "...");
                                            } ?>
                                        </p>
                                        <input type="hidden" name="ia-email-events[][event-id]"  class="ia-email-event-id" value="<?php echo $event->id; ?>"></input>
                                        <div class="ia-email-events-row-buttons">
                                            <button class="ia-email-button-small ia-email-move-down"><img src="<?php echo plugin_dir_url(__FILE__) . 'icons/chevron-down-solid.svg'; ?>" alt="Move Down" title="Move Down"></button>
                                            <button class="ia-email-button-small ia-email-move-up"><img src="<?php echo plugin_dir_url(__FILE__) . 'icons/chevron-up-solid.svg'; ?>" alt="Move Up" title="Move Up"></button>
                                            <button class="ia-email-button-small ia-email-minimize"><img src="<?php echo plugin_dir_url(__FILE__) . 'icons/window-minimize-solid.svg'; ?>" alt="Minimize" title="Minimize"></button>
                                            <button class="ia-email-button-small ia-email-maximize"><img src="<?php echo plugin_dir_url(__FILE__) . 'icons/plus-solid.svg'; ?>" alt="Maximize" title="Maximize"></button>
                                            <button class="ia-email-button-small ia-email-remove"><img src="<?php echo plugin_dir_url(__FILE__) . 'icons/trash-solid.svg'; ?>" alt="Remove" title="Remove"></button>
                                        </div>
                                    </div>
                                    <div class="ia-email-events-row-content">
                                        <div class="ia-email-events-get-tec">
                                            <label for="ia-email-tec-dropdown">TEC Event</label>
                                            <select class="ia-email-tec-dropdown" name="ia-email-events[][tec-dropdown]">
                                                <option value="none">None</option>
                                                <?php
                                                if ($event->tec_event_id != '') { ?>
                                                    <option value="<?php echo $event->tec_event_id; ?>" selected>-- <?php echo get_the_title(tribe_get_event($event->tec_event_id)); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="ia-email-events-row-props">
                                            <label class="ia-email-events-prop">
                                                Featured
                                                <input type="checkbox" name="ia-email-events[][event-featured]" <?php if ($event->event_featured == 'on') { ?> checked <?php } ?>></input>
                                                <span class="slider"></span>
                                            </label>
                                            <label class="ia-email-events-prop">
                                                Two Images
                                                <input type="checkbox" name="ia-email-events[][event-two-imgs]" <?php if ($event->event_two_imgs == 'on') { ?> checked <?php } ?>></input>
                                                <span class="slider"></span>
                                            </label>
                                            <label class="ia-email-events-prop">
                                                Image Right
                                                <input type="checkbox" name="ia-email-events[][event-img-right]" <?php if ($event->event_img_right == 'on') { ?> checked <?php } ?>></input>
                                                <span class="slider"></span>
                                            </label>
                                            <label class="ia-email-events-prop">
                                                Divider
                                                <input type="checkbox" name="ia-email-events[][event-divider]" <?php if ($event->event_divider == 'on') { ?> checked <?php } ?>></input>
                                                <span class="slider"></span>
                                            </label>
                                            <label class="ia-email-events-prop">
                                                Mute
                                                <input type="checkbox" name="ia-email-events[][event-mute]" <?php if ($event->event_mute == 'on') { ?> checked <?php } ?>></input>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                        <label for="ia-email-event-header">Event Row Header</label>
                                        <input type="text" name="ia-email-events[][event-header]" class="event-row-header" value="<?php echo esc_html(stripslashes($event->event_header_text)); ?>"></input>
                                        <label for="ia-email-event-image">Event Row Image</label>
                                        <?php
                                        $event_imgs = ia_email_get_imgs($event->id);
                                        if (empty($event_imgs)) { ?>
                                            <div class="ia-email-event-image-wrapper">
                                            <input type="hidden" name="ia-email-events[][event-image-id][]" class="ia-email-event-image-id" value="">
                                                <img src="" alt="Event Image Preview" class="ia-email-event-image-preview">
                                                <input type="hidden" name="ia-email-events[][event-image-image-id][]" class="ia-email-event-image-image-id" value="">
                                                <input type="button" value="Choose Image" class="ia-email-button ia-email-select-image">
                                            </div>
                                            <?php
                                        } else {
                                            foreach ($event_imgs as $event_img) { ?>
                                                <div class="ia-email-event-image-wrapper">
                                                    <img src="<?php echo wp_get_attachment_image_url($event_img->event_img_id, 'full'); ?>" alt="Event Image Preview" class="ia-email-event-image-preview">
                                                    <input type="hidden" name="ia-email-events[][event-image-id][]" class="ia-email-event-image-id" value="<?php echo $event_img->id; ?>">
                                                    <input type="hidden" name="ia-email-events[][event-image-image-id][]" class="ia-email-event-image-image-id" value="<?php echo $event_img->event_img_id; ?>">
                                                    <input type="button" value="Choose Image" class="ia-email-button ia-email-select-image">
                                                </div>
                                        <?php
                                            }
                                        } ?>
                                        <label for="ia-email-event-text">Event Row Text</label>
                                        <!-- <textarea name="ia-email-events[][event-text]" rows="5"><?php echo stripslashes($event->event_text); ?></textarea> -->
                                        <?php
                                        wp_editor(
                                            stripslashes($event->event_text),
                                            'ia-email-event-text-' . $key,
                                            array(
                                                'media_buttons' => false,
                                                'textarea_rows' => '10',
                                                'textarea_name' => 'ia-email-events[][event-text]'
                                            )
                                        );
                                        ?>
                                        <?php
                                        $event_buttons = ia_email_get_buttons($event->id);
                                        if (empty($event_buttons)) { ?>
                                            <div class="ia-email-event-button-wrapper">
                                                <div class="ia-email-event-button-inputs">
                                                    <label for="ia-email-event-button-text">Event Row Button Text</label>
                                                    <input type="text" name="ia-email-events[][event-button][text][]" class="event-button-text" value="Volunteer"></input>
                                                    <label for="ia-email-event-link">Event Row Button Link</label>
                                                    <input type="text" name="ia-email-events[][event-button][link][]" value="https://www.example.com"></input>
                                                </div>
                                                <div class="ia-email-event-button-controls">
                                                    <button class="ia-email-button-small ia-email-button-add">+</button>
                                                    <button class="ia-email-button-small ia-email-button-remove">-</button>
                                                </div>
                                            </div>
                                            <?php
                                        } else {
                                            foreach ($event_buttons as $event_button) { ?>
                                                <div class="ia-email-event-button-wrapper">
                                                    <div class="ia-email-event-button-inputs">
                                                        <input type="hidden" name="ia-email-events[][event-button][id][]"  value="<?php echo $event_button->id; ?>"></input>
                                                        <label for="ia-email-event-button-text">Event Row Button Text</label>
                                                        <input type="text" name="ia-email-events[][event-button][text][]"  class="event-button-text" value="<?php echo stripslashes($event_button->event_button_text); ?>"></input>
                                                        <label for="ia-email-event-link">Event Row Button Link</label>
                                                        <input type="text" name="ia-email-events[][event-button][link][]" value="<?php echo stripslashes($event_button->event_button_link); ?>"></input>
                                                    </div>
                                                    <div class="ia-email-event-button-controls">
                                                        <button class="ia-email-button-small ia-email-button-add"><img src="<?php echo plugin_dir_url(__FILE__) . 'icons/plus-solid.svg'; ?>" alt="Add Button Row" title="Add Button Row"></button>
                                                        <button class="ia-email-button-small ia-email-button-remove"><img src="<?php echo plugin_dir_url(__FILE__) . 'icons/trash-solid.svg'; ?>" alt="Remove Button Row" title="Remove Button Row"></button>
                                                    </div>
                                                </div>
                                        <?php
                                            }
                                        } ?>
                                    </div>
                                </div>
                        <?php } ?>
                    </div>
                    <!--I tried commenting the next two items out, but saves stopped working -->
                    <label for="ia-email-footer-signup" style="display:none;">Footer Sign-Up Text</label>
                    <input type="text" name="ia-email-footer-signup" id="ia-email-footer-signup" value="<?php echo esc_attr(stripslashes(ia_email_get('footer_signup'))); ?>" style="display:none;"></input>
                    <label for="ia-email-footer-socials">Footer</label>
                    <?php
                    wp_editor(
                        stripslashes(ia_email_get('footer_socials')),
                        'ia-email-footer-socials',
                        array(
                            'media_buttons' => false,
                            'textarea_rows' => '10',
                            'textarea_name' => 'ia-email-footer-socials'
                        )
                    );
                    ?>
                    <div class="ia-email-bottom-buttons">
                        <button id="add-event" class="ia-email-button">Add Row</button>
                        <input type="submit" value="Save" id="ia-email-save" class="ia-email-button">
                        <button id="copy-code" class="ia-email-button">Copy Code to Clipboard</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="ia-email-admin-inputs-right">
            <h2 class="ia-email-templater-header">Preview</h2>
            <div id="the-preview">
                <!-- This is the HTML for a 'Volunteering Matters' Newsletter. -->
                <div role="article" aria-roledescription="email" lang="en" style="-webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background-color: #ffffff;">
                    <table role="presentation" style="width: 100%; border: 0px; border-spacing: 0px; max-width: 660px; margin: 0 auto;">
                        <tbody>
                            <tr>
                                <td align="center" style="width: 100%; max-width: 660px;">
                                    <img src="<?php echo wp_get_attachment_url(ia_email_get('header_image_id')); ?>" width="100%" alt="" style="width: 100%; height: auto;" />
                                </td>
                            </tr>
                            <?php
                            $row_num=0;
                            foreach ($events as $event) {
                                if (($event->event_mute !== 'on') && ($event->event_featured == 'on')) {
                                    if ($event->event_divider == 'on') { ?>
                                        <tr style="background-color: #ffffff;height:20px;"><td></td></tr>
                                        <tr style="background-color: Gainsboro;"><td>
                                        <div style="text-align: center; line-height: 1; margin-bottom: 4px;">
                                            <span style="font-size: 36px; font-weight: bold;"><?php echo stripslashes($event->event_header_text); ?></span><br />
                                        </div>
                                        <div style="text-align: center; font-size: 14px; line-height: 1;">
                                            <?php echo stripslashes($event->event_text); ?>
                                        </div>
                                        </td></tr>
                                    <?php
                                    } else {
                                        $event_header = stripslashes($event->event_header_text);
                                        $event_text=stripslashes($event->event_text);
                                        if (empty($event_text) && !empty($event_header)) {
                                            ?><span style="font-size: 0px;"><?php echo $event_header;?></span><?php
                                        } elseif (!empty($event_header)) { ?>
                                            <tr style="background-color: WhiteSmoke;"><td style="padding:8px;">
                                                <h3 style="margin-bottom:4px;">
                                                    <?php echo $event_header;  ?>
                                                </h3>
                                            </td></tr>
                                        <?php } 
                                        
                                        // insert html codes to communicate paragraph and line breaks
                                        if (!empty($event_text)) {
                                            $event_paragraphs = preg_split('/(\r\n|\n|\r)/', $event_text);
                                            $event_text = "";

                                            for ($i = 0; $i < count($event_paragraphs); $i++) {
                                                if (!empty($event_paragraphs[$i])) {
                                                    if (($i + 1) < count($event_paragraphs)) {
                                                        if (empty($event_paragraphs[$i + 1])) {
                                                            $event_text .= '<p>' . $event_paragraphs[$i] . '</p>';
                                                        } else {
                                                            $event_text .= $event_paragraphs[$i] . '<br/>';
                                                        }
                                                    } else {
                                                        $event_text .= $event_paragraphs[$i];
                                                    }
                                                }
                                            }
                                        }
                                        
                                        ?>
                                        <tr><td>
                                        <?php
                                            $event_imgs = ia_email_get_imgs($event->id);
                                            $event_image1_url=wp_get_attachment_image_url($event_imgs[0]->event_img_id, 'full');
                                            
                                            $event_buttons_html = ia_email_get_buttons_html($event->id);
                                            $float_direction = ($event->event_img_right == 'on') ? 'right' : 'left';
                                            if (count($event_imgs) > 1) { ?>
                                                <div>
                                                    <img src="<?php echo wp_get_attachment_image_url($event_imgs[0]->event_img_id, 'full'); ?>" width="23%" alt="" 
                                                    style="display: inline; width: 32%; max-width: 99px; height: auto; float: <?php echo $float_direction; ?>; margin: 0px 0px 2px 2px;" />
                                                    <img src="<?php echo wp_get_attachment_image_url($event_imgs[1]->event_img_id, 'full'); ?>" width="23%" alt="" 
                                                    style="display: inline; width: 32%; max-width: 99px; height: auto; float: <?php echo $float_direction; ?>; margin: 0px 10px 2px 2px;" />
                                                    <?php echo $event_text;  ?>
                                                </div>
                                                <?php
                                            } elseif (!empty($event_image1_url)) { ?>
                                                <?php $event_words = preg_split('/\s+/', $event_text);
                                                if (count($event_words) > 75) {
                                                    echo implode(" ", array_slice($event_words,0,18)); ?>
                                                    <img src="<?php echo wp_get_attachment_image_url($event_imgs[0]->event_img_id, 'full'); ?>" width="47%" alt="" 
                                                        style="display: block; width: 65%; max-width: 200px; height: auto; float: <?php echo $float_direction; ?>; margin: 0px 10px 2px 2px;" />
                                                    <?php echo implode(" ", array_slice($event_words, 18)); 
                                                } else { ?>
                                                    <img src="<?php echo wp_get_attachment_image_url($event_imgs[0]->event_img_id, 'full'); ?>" width="47%" alt="" 
                                                        style="display: block; width: 65%; max-width: 200px; height: auto; float: <?php echo $float_direction; ?>; margin: 0px 10px 2px 2px;" />
                                                    <?php echo $event_text;    
                                                }
                                            } else { 
                                                echo $event_text;
                                            } 
                                            echo $event_buttons_html; ?>
                                    </td></tr>
                            <?php
                                    }
                                }
                            } 
                            ?>
                            <tr style="background-color: #ffffff;height:20px;"><td></td></tr>
                            <tr style="background-color: Gainsboro;">
                                <td>
                                    <div style="text-align: center; line-height: 1; margin-bottom: 4px;"><span style="font-size: 36px; font-weight: bold;">More Volunteer Events</span></div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <ul>
                                        <?php
                                        foreach ($events as $event) {
                                            if (($event->event_mute !== 'on') && ($event->event_featured !== 'on')) {
                                                $event_buttons = ia_email_get_buttons($event->id);
                                                ?>
                                                <li style="padding: 2px;"><a href="<?php echo stripslashes($event_buttons[0]->event_button_link); ?>"><?php echo stripslashes($event->event_header_text); ?></a></li>
                                        <?php
                                            }
                                        } 
                                        ?>
                                    </ul>
                                </td>
                            </tr>

                            <tr style="background-color: WhiteSmoke;text-align:center"><td>
                                <h3 style="margin-bottom:0px;">Are we missing any volunteer opportunities?</h3>
                                Let us know! It could be featured in our next newsletter.<br/>Submit
                                    <a href="https://www.indyambassadors.org/events/community/add">an event</a>,
                                    <a href="https://www.indyambassadors.org/add-ongoing/" >an ongoing opportunity</a>, or
                                    <a href="mailto:volunteeradmin@indyambassadors.org">email us.</a>
                                </span>
                            </td></tr>
                            <tr style="text-align:center">
                                <td>
                                    <p><?php 
                                        $footer_text = stripslashes(ia_email_get('footer_socials'));
                                        // insert html codes to communicate paragraph and line breaks
                                        if (!empty($footer_text)) {
                                            $footer_paragraphs = preg_split('/(\r\n|\n|\r)/', $footer_text);
                                            $footer_text = "";

                                            for ($i = 0; $i < count($footer_paragraphs); $i++) {
                                                if (!empty($footer_paragraphs[$i])) {
                                                    if (($i + 1) < count($footer_paragraphs)) {
                                                        if (empty($footer_paragraphs[$i + 1])) {
                                                            $footer_text .= '<p>' . $footer_paragraphs[$i] . '</p>';
                                                        } else {
                                                            $footer_text .= $footer_paragraphs[$i] . '<br/>';
                                                        }
                                                    } else {
                                                        $footer_text .= $footer_paragraphs[$i];
                                                    }
                                                }
                                            }
                                        }
                                        echo $footer_text; ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="ia-email-admin-outputs">
            <div class="ia-email-admin-outputs-header">
                <h2 class="ia-email-templater-header">The HTML</h2> <button id="toggle-code" class="ia-email-button-small">Show / Hide</button>
            </div>
            <pre><code id="the-code"></code></pre>
        </div>
    </div>