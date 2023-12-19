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
                    <label for="ia-email-header-iamge">Header Image</label>
                    <div class="ia-email-header-image-wrapper">
                        <img src="<?php echo wp_get_attachment_image_url(ia_email_get('header_image_id'), 'full'); ?>" alt="Header Image Preview" class="ia-email-header-image">
                        <input type="hidden" name="ia-email-header-image" class="ia-email-header-image-id" value="<?php echo ia_email_get('header_image_id'); ?>">
                        <input type="button" value="Choose Image" class="ia-email-button ia-email-select-image">
                    </div>
                    <label for="ia-email-title-text">Header Text</label>
                    <input type="text" name="ia-email-title-text" id="ia-email-title-text" value="<?php echo stripslashes(ia_email_get('header_text')); ?>"></input>
                    <label for="ia-email-intro-p">Intro Text</label>
                    <textarea name="ia-email-intro-p" id="ia-email-intro-p" cols="30" rows="3"><?php echo esc_attr(stripslashes(ia_email_get('intro_paragraph'))); ?></textarea>
                    <label for="ia-email-intro-signature">Intro Signature</label>
                    <input type="text" name="ia-email-intro-signature" id="ia-email-intro-signature" value="<?php echo ia_email_get('intro_signature'); ?>"></input>
                    <div class="ia-email-events-wrapper">
                        <?php
                        $events = ia_email_get('events');
                        foreach ($events as $event) {
                        ?>
                            <div class="ia-email-events-row">
                                <div class="ia-email-events-row-header">
                                    <h3 class="ia-email-events-row-header-text">Event Row</h3>
                                    <div class="ia-email-events-row-buttons">
                                        <button class="ia-email-button-small ia-email-minimize">_</button>
                                        <button class="ia-email-button-small ia-email-maximize">+</button>
                                        <button class="ia-email-button-small ia-email-remove">x</button>
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
                                        <div class="ia-email-events-featured">
                                            <label for="ia-email-event-featured">Featured</label>
                                            <input type="checkbox" name="ia-email-events[][event-featured]" <?php
                                                                                                            if ($event->event_featured == 'on') { ?> checked <?php } ?>></input>
                                        </div>
                                        <div class="ia-email-events-two-imgs">
                                            <label for="ia-email-event-two-imgs">Two Images</label>
                                            <input type="checkbox" name="ia-email-events[][event-two-imgs]" <?php
                                                                                                            if ($event->event_two_imgs == 'on') { ?> checked <?php } ?>></input>
                                        </div>
                                    </div>
                                    <label for="ia-email-event-header">Event Row Header</label>
                                    <input type="text" name="ia-email-events[][event-header]" value="<?php echo stripslashes($event->event_header_text); ?>"></input>
                                    <label for="ia-email-event-image">Event Row Image</label>
                                    <?php
                                    $event_imgs = ia_email_get_imgs($event->id);
                                    if (empty($event_imgs)) { ?>
                                        <div class="ia-email-event-image-wrapper">
                                            <img src="" alt="Event Image Preview" class="ia-email-event-image-preview">
                                            <input type="hidden" name="ia-email-events[][event-image-id][]" class="ia-email-event-image-id" value="">
                                            <input type="button" value="Choose Image" class="ia-email-button ia-email-select-image">
                                        </div>
                                        <?php
                                    } else {
                                        foreach ($event_imgs as $event_img) { ?>
                                            <div class="ia-email-event-image-wrapper">
                                                <img src="<?php echo wp_get_attachment_image_url($event_img->event_img_id, 'full'); ?>" alt="Event Image Preview" class="ia-email-event-image-preview">
                                                <input type="hidden" name="ia-email-events[][event-image-id][]" class="ia-email-event-image-id" value="<?php echo $event_img->event_img_id; ?>">
                                                <input type="button" value="Choose Image" class="ia-email-button ia-email-select-image">
                                            </div>
                                    <?php
                                        }
                                    } ?>
                                    <label for="ia-email-event-text">Event Row Text</label>
                                    <textarea name="ia-email-events[][event-text]" rows="5"><?php echo stripslashes($event->event_text); ?></textarea>
                                    <?php
                                    $event_buttons = ia_email_get_buttons($event->id);
                                    if (empty($event_buttons)) { ?>
                                        <div class="ia-email-event-button-wrapper">
                                            <div class="ia-email-event-button-inputs">
                                                <label for="ia-email-event-button-text">Event Row Button Text</label>
                                                <input type="text" name="ia-email-events[][event-button][text][]" value="Volunteer"></input>
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
                                                    <label for="ia-email-event-button-text">Event Row Button Text</label>
                                                    <input type="text" name="ia-email-events[][event-button][text][]" value="<?php echo stripslashes($event_button->event_button_text); ?>"></input>
                                                    <label for="ia-email-event-link">Event Row Button Link</label>
                                                    <input type="text" name="ia-email-events[][event-button][link][]" value="<?php echo stripslashes($event_button->event_button_link); ?>"></input>
                                                </div>
                                                <div class="ia-email-event-button-controls">
                                                    <button class="ia-email-button-small ia-email-button-add">+</button>
                                                    <button class="ia-email-button-small ia-email-button-remove">-</button>
                                                </div>
                                            </div>
                                    <?php
                                        }
                                    } ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <label for="ia-email-footer-signup">Footer Sign-Up Text</label>
                    <input type="text" name="ia-email-footer-signup" id="ia-email-footer-signup" value="<?php echo esc_attr(stripslashes(ia_email_get('footer_signup'))); ?>"></input>
                    <label for="ia-email-footer-socials">Footer Social Links</label>
                    <textarea name="ia-email-footer-socials" id="ia-email-footer-socials" cols="30" rows="3"><?php echo esc_attr(stripslashes(ia_email_get('footer_socials'))); ?></textarea>
                    <div class="ia-email-bottom-buttons">
                        <button id="add-event" class="ia-email-button">Add Event</button>
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
                    <table role="presentation" style="width: 98.0613%; border: 0px; border-spacing: 0px;">
                        <tbody>
                            <tr>
                                <td align="center" style="width: 100%;">
                                    <div class="outer" style="width: 96%; max-width: 660px; margin: 20px auto;">
                                        <table role="presentation" style="width: 100%; border: 0; border-spacing: 0;">
                                            <tbody>
                                                <tr>
                                                    <td style="padding: 10px 10px 20px 10px; font-family: Arial,sans-serif; font-size: 24px; line-height: 28px; font-weight: bold;">
                                                        <!-- Newsletter header image -->
                                                        <img src="<?php echo wp_get_attachment_url(ia_email_get('header_image_id')); ?>" width="640" alt="" style="width: 100%; height: auto;" />
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px; text-align: left;">
                                                        <!-- Newsletter Header -->
                                                        <h1 style="margin-top: 0; margin-bottom: 16px; font-family: Arial,sans-serif; font-size: 26px; line-height: 32px; font-weight: bold;">
                                                            <?php echo ia_email_get('header_text'); ?>
                                                        </h1>
                                                        <div style="margin: 0; font-family: Arial,sans-serif; font-size: 18px; line-height: 24px;">
                                                            <!-- This is the newsletter introductory paragraph(s). -->
                                                            <p><?php echo stripslashes(ia_email_get('intro_paragraph')); ?></p>
                                                            <p><?php echo stripslashes(ia_email_get('intro_signature')); ?></p>
                                                            <a href='mailto:volunteeradmin@indyambassadors.org'>volunteeradmin@indyambassadors.org</a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div style="text-align: center; line-height: 1;">
                    <span style="font-size: 36px; font-weight: bold;">Volunteer Events</span>
                    <br />
                    <span style="font-size: 14px; font-style: italic; background-color: lightyellow;">Newly reported opportunities are highlighted.</span>
                    <br />
                    <span style="font-size: 14px;"></span>Visit <a href="https://www.indyambassadors.org/events/">our events calendar</a> for full schedule.</span>
                </div>

                <?php
                foreach ($events as $event) { ?>
                    <!--   TEMPLATE  Multiple events, single picture   -->
                    <div class="two-col" style="text-align: center; font-size: 0;background-color: <?php if ($event->event_featured == 'on') { ?> lightyellow; <?php } else { ?> transparent; <?php } ?>"><!-- change to zero/> --> <!--[if mso]>  <table role="presentation" width="100%">  <tr>  <td sstyle="width:50%;padding:10px;" valign="middle">  <![endif]-->
                        <div class="column" style="width: 100%; max-width: 330px; display: inline-block; vertical-align: middle;">
                            <div style="padding: 10px;">
                                <p style="margin: 0; font-family: Arial,sans-serif; font-size: 14px; line-height: 18px;">
                                    <!-- ---------------    Update the article image link ----------------- -->
                                    <?php
                                    $event_imgs = ia_email_get_imgs($event->id);
                                    if (count($event_imgs) > 1) { ?>
                                        <img src="<?php echo wp_get_attachment_image_url($event_imgs[0]->event_img_id, 'full'); ?>" width="150" alt="" style="display: inline; width: 150px; max-width: 100%; height: auto; flat: left;" />
                                        <img src="<?php echo wp_get_attachment_image_url($event_imgs[1]->event_img_id, 'full'); ?>" width="150" alt="" style="display: inline; width: 150px; max-width: 100%; height: auto; float: right;" />
                                    <?php
                                    } else { ?>
                                        <img src="<?php echo wp_get_attachment_image_url($event_imgs[0]->event_img_id, 'full'); ?>" width="310" alt="" style="display: block; width: 310px; max-width: 100%; height: auto;" />
                                    <?php
                                    } ?>
                                </p>
                            </div>
                        </div><!-- [if mso]>  </td>  <td style="width:50%;padding:10px;" valign="middle">  <![endif]-->
                        <div class="column" style="width: 100%; max-width: 330px; display: inline-block; vertical-align: middle;">
                            <div style="padding: 10px; font-size: 14px; line-height: 18px; text-align: left;">
                                <p style="margin-top: 0; margin-bottom: 12px; font-family: Arial,sans-serif; font-weight: bold;">
                                    <!-- ---------------     Update the Article Heading ------------------ -->
                                    <?php echo stripslashes($event->event_header_text); ?>
                                </p>
                                <p style="margin-top: 0; margin-bottom: 14px; font-family: Arial,sans-serif;">
                                    <!-- -------------------- Update the Article Body ----------------------->
                                    <?php echo stripslashes($event->event_text); ?>
                                </p>
                                <p style="margin: 0; font-family: Arial,sans-serif;">
                                    <!-- -----------------   Update the Article Action Link ------------------- -->
                                    <?php
                                    $event_buttons = ia_email_get_buttons($event->id);
                                    foreach ($event_buttons as $event_button) { ?>
                                        <a href="<?php echo stripslashes($event_button->event_button_link); ?>" style="background: #ffffff; border: 2px solid #8dc1d6; text-decoration: none; padding: 10px 8px; color: #000000; border-radius: 4px; display: inline-block; mso-padding-alt: 0; text-underline-color: #ffffff;"><!-- [if mso]><i style="letter-spacing: 25px;mso-font-width:-100%;mso-text-raise:20pt">&nbsp;</i><![endif]--><span style="mso-text-raise: 10pt; font-weight: bold;">
                                                <!-- -------------   Update the Article Action Prompt -------------------- -->
                                                <?php echo stripslashes($event_button->event_button_text); ?>
                                            </span><!-- [if mso]><i style="letter-spacing: 25px;mso-font-width:-100%">&nbsp;</i><![endif]--></a>
                                    <?php
                                    } ?>
                                </p>
                            </div>
                        </div><!-- [if mso]>  </td>  </tr>  </table>  <![endif]-->
                    </div>
                    <div class="spacer" style="line-height: 24px; height: 24px; mso-line-height-rule: exactly;"> </div>
                <?php
                } ?>

                <div class="spacer" style="line-height: 24px; height: 24px; mso-line-height-rule: exactly;"> </div>

                <!-- Below this line is the closing remarks.  All the articles should be above this line. -->
                <div>
                    <p><?php echo stripslashes(ia_email_get('footer_signup')); ?></p>
                    <div><?php echo stripslashes(ia_email_get('footer_socials')); ?></div>
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