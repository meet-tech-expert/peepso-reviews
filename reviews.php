<?php

/**
 * Plugin Name: PeepSo : Reviews
 * Plugin URI: https://peepso.com
 * Description: Plugin is used for reviews of every profile, store and non-stores.
 * Author: Rinkesh Gupta
 * Author URI: https://peepso.com
 * Version: 6.4.5.0
 * Copyright: (c) 2015 PeepSo, Inc. All Rights Reserved.
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: peepsoreviews
 * Domain Path: /language
 *
 * We are Open Source. You can redistribute and/or modify this software under the terms of the GNU General Public License (version 2 or later)
 * as published by the Free Software Foundation. See the GNU General Public License or the LICENSE file for more details.
 * This software is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
 */

class PeepSoReviews
{
    private static $_instance = NULL;

    const PLUGIN_NAME     = 'Reviews';
    const PLUGIN_VERSION = '6.4.5.0';
    const PLUGIN_RELEASE = ''; //ALPHA1, BETA1, RC1, '' for STABLE

    public $widgets = array(
        'PeepSoWidgetReviews',
    );
    private $view_user_id;

    private static function ready()
    {
        if (class_exists('PeepSo')) {
            $plugin_version = explode('.', self::PLUGIN_VERSION);
            $peepso_version = explode('.', PeepSo::PLUGIN_VERSION);

            if (4 == count($plugin_version)) {
                array_pop($plugin_version);
            }

            if (4 == count($peepso_version)) {
                array_pop($peepso_version);
            }

            $plugin_version = implode('.', $plugin_version);
            $peepso_version = implode('.', $peepso_version);

            return ($peepso_version == $plugin_version);
        }

        return FALSE;
    }

    /**
     * peepso_all_plugins filter integration
     * PEEPSO_VER_MIN is the minimum REQUIRED PeepSo version - if PeepSo is BELOW this number, disable self
     * PEEPSO_VER_MAX is the maximum TESTED PeepSo version -  if PeepSo is ABOVE this number, render a a warning
     * Hooking into peepso_all_plugins without these two constants will result in strict version lock
     */

    private function __construct()
    {
        define('REVIEWS_MAX_UPLOAD_SIZE', 20);
        define('REVIEWS_MAX_FILES', 3);
        define('REVIEWS_MAX_CHAR_TITLE', 100);
        define('REVIEWS_MAX_CHAR_DESC', 500);
        define('REVIEWS_PER_PAGE', 10);
        /** VERSION INDEPENDENT hooks **/

        // Admin
        if (is_admin()) {
            add_action('admin_init', array(&$this, 'peepso_check'));
            add_filter('peepso_config_email_messages', array(&$this, 'config_email_messages'));
            add_filter('peepso_config_email_messages_defaults',  function ($emails) {
                require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '/install' . DIRECTORY_SEPARATOR . 'activate.php');
                $install = new PeepSoReviewsInstall();
                $defaults = $install->get_email_contents();
                return array_merge($emails, $defaults);
            });
        }

        // Compatibility
        add_filter('peepso_all_plugins', function ($plugins) {
            $plugins[plugin_basename(__FILE__)] = get_class($this);
            return $plugins;
        });

        // Activation
        register_activation_hook(__FILE__, function () {
            if (!$this->peepso_check()) {
                return (FALSE);
            }

            require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'activate.php');
            $install = new PeepSoReviewsInstall();
            $res = $install->plugin_activation();
            if (FALSE === $res) {
                // error during installation - disable
                deactivate_plugins(plugin_basename(__FILE__));
            }
            return (TRUE);
        });

        /** VERSION LOCKED hooks **/
        if (self::ready()) {
            if (is_admin()) {
                add_action('admin_init', array(&$this, 'peepso_check'));
            }

            add_action('peepso_init', array(&$this, 'init'));

            // Navigation
            add_filter('peepso_navigation_profile', function ($links) {

                // do nothing if the option is disabled
                if (0 == PeepSo::get_option('reviews_profiles_enable', 1)) {
                    return $links;
                }

                // do nothing if "owner only" is enabled and we are loading someone elses profile
                if (1 == PeepSo::get_option('reviews_profiles_owner_only', 0)) {
                    if (isset($links['_user_id']) && get_current_user_id() != $links['_user_id']) {
                        return $links;
                    }
                }

                $links['reviews'] = array(
                    'href' => PeepSo::get_option('reviews_profiles_slug', 'reviews', TRUE),
                    'label' => PeepSo::get_option('reviews_profiles_label', __('Reviews', 'peepsoreviews'), TRUE),
                    'icon' => PeepSo::get_option('reviews_profiles_icon', 'ps-icon-star', TRUE),
                );

                return $links;
            });

            // Widgets
            // add_filter('peepso_widgets', array(&$this, 'register_widgets'));
        }
    }

    public static function get_instance()
    {
        if (NULL === self::$_instance) {
            self::$_instance = new self();
        }
        return (self::$_instance);
    }

    public function init()
    {
        PeepSo::add_autoload_directory(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR);
        PeepSoTemplate::add_template_directory(plugin_dir_path(__FILE__));

        if (is_admin()) {
            add_action('admin_init', array(&$this, 'peepso_check'));

            add_filter('peepso_admin_config_tabs', function ($tabs) {
                $tabs['reviews'] = array(
                    'label' => __('Reviews', 'peepsoreviews'),
                    'icon' => 'https://images.findyourreptile.com/images/2024/10/30034828/star.png',
                    'tab' => 'reviews',
                    'function' => 'PeepSoConfigSectionReviews',
                );

                return $tabs;
            });
            //delete media if review is deleted
            add_action('before_delete_post', array(&$this, 'delete_associated_media'), 99, 2);
        } else {
            add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
        }

        add_action('peepso_profile_new_review', array(&$this, 'send_email_review'), 99, 2);

        // Render the profile segment attached via peepso_navigation_profile
        $profile_slug = PeepSo::get_option('reviews_profiles_slug', 'reviews', TRUE);
        // var_dump($profile_slug);
        add_action('peepso_profile_segment_' . $profile_slug, function () {

            $this->view_user_id = PeepSoUrlSegments::get_view_id(PeepSoProfileShortcode::get_instance()->get_view_user_id());

            // Verify if the option is enabled and the user has the right to come here.
            $continue = TRUE;

            // If tabs are disabled
            if (0 == PeepSo::get_option('reviews_profiles_enable', 1)) {
                $continue = FALSE;
            }

            // Or "Owner Only" is enabled and we are not the owner
            if (1 == PeepSo::get_option('reviews_profiles_owner_only', 0)) {
                if (get_current_user_id() !=  $this->view_user_id) {
                    $continue = FALSE;
                }
            }
            // var_dump($continue);
            if ($continue) {
                // If everything is OK, print the HTML
                echo PeepSoTemplate::exec_template('reviews', 'profile-reviews', array('view_user_id' => $this->view_user_id), TRUE);
            } else {
                // If not, redirect gracefully to profile home
                PeepSo::redirect(PeepSoUser::get_instance($this->view_user_id)->get_profileurl());
            }
        });

        // add_filter('peepso_widgets', array(&$this, 'register_widgets'));


    }

    /**
     * Check if PeepSo class is present (PeepSo is installed and activated)
     * If there is no PeepSo, immediately disable the plugin and display a warning
     * @return bool
     */
    public function peepso_check()
    {
        if (!class_exists('PeepSo')) {

            add_action('admin_notices', function () {
?>
                <div class="error peepso">
                    <strong>
                        <?php echo sprintf(__('The %s plugin requires the PeepSo plugin to be installed and activated.', 'peepsoreviews'), self::PLUGIN_NAME); ?>
                    </strong>
                </div>
<?php
            });

            unset($_GET['activate']);
            deactivate_plugins(plugin_basename(__FILE__));
            return (FALSE);
        }

        return (TRUE);
    }

    public function enqueue_scripts()
    {
        wp_enqueue_style('reviews-dropzone', plugin_dir_url(__FILE__) . 'assets/css/dropzone.min.css', array('peepso'), self::PLUGIN_VERSION, 'all');
        wp_enqueue_style('peepsoreviews-lightbox', plugin_dir_url(__FILE__) . 'assets/css/lightbox.min.css', array('peepso'), self::PLUGIN_VERSION, 'all');
        wp_enqueue_style('peepsoreviews', plugin_dir_url(__FILE__) . 'assets/css/reviews.css', array('reviews-dropzone'), self::PLUGIN_VERSION, 'all');
        wp_enqueue_script('reviews-dropzon', plugin_dir_url(__FILE__) . 'assets/js/dropzone.min.js', array('peepso'), self::PLUGIN_VERSION, TRUE);
        wp_enqueue_script('peepsoreviews-lightbox', plugin_dir_url(__FILE__) . 'assets/js/lightbox.min.js', array('peepso'), self::PLUGIN_VERSION, TRUE);

        wp_register_script(
            'peepsoreviews',
            plugin_dir_url(__FILE__) . 'assets/js/reviews.js',
            array('reviews-dropzon'),
            self::PLUGIN_VERSION,
            TRUE
        );
        $title_limit    = PeepSo::get_option('reviews_max_char_title', REVIEWS_MAX_CHAR_TITLE, TRUE);
        $desc_limit     = PeepSo::get_option('reviews_max_char_desc', REVIEWS_MAX_CHAR_DESC, TRUE);
        $max_size       = PeepSo::get_option('reviews_image_max_size', REVIEWS_MAX_UPLOAD_SIZE, TRUE);
        $max_files      = PeepSo::get_option('reviews_image_max_files', REVIEWS_MAX_FILES, TRUE);
        $profile_slug   = PeepSo::get_option('reviews_profiles_slug', 'reviews', TRUE);
        wp_localize_script('peepsoreviews', 'peepsoreviewsdata', [
            'form_template' => PeepSoTemplate::exec_template('reviews', 'form-reviews', array(
                'max_char_title' => $title_limit,
                'max_char_desc'  => $desc_limit,
                'max_files'      => $max_files,
                'max_size'       => $max_size,
            ), TRUE),
            'image_max_size' => $max_size,
            'image_max_files' => $max_files,
            'max_char_title' =>  $title_limit,
            'max_char_desc' => $desc_limit,
            'loading_gif' => PeepSo::get_asset('images/ajax-loader.gif'),
            'peepso_nonce' => wp_create_nonce('peepso-nonce'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'ajaxurl_legacy' => get_bloginfo('wpurl') . '/peepsoajax/',
            'site_url' => site_url(),
            'login_texts' => "You must be signed in to leave a review. Please <a href='" . site_url('login') . "'>login</a> or <a href='" . site_url('register') . "'>signup</a>",
            'submit_label' => __('Submit', 'peepsoreviews'),
            'title_error_txt' => sprintf(__('Title must be less than %1$d characters.', 'peepsoreviews'), $title_limit),
            'desc_error_txt' => sprintf(__('Description must be less than %1$d characters.', 'peepsoreviews'), $desc_limit),
            'rating_error_txt' => __("Please select a rating", 'peepsoreviews'),
            'profile_reviews_slug' => $profile_slug,
            'file_size_error_txt' => sprintf(__('The file size ({{filesize}}MB) you uploaded is too big. The maximum file size is %1$dMB.', 'peepsoreviews'), $max_size),
        ]);
        wp_enqueue_script('peepsoreviews');
    }
    public function delete_associated_media($postid, $post)
    {
        // check if review
        if ('review' !==  $post->post_type) return;

        $images = get_field('review_images', $postid);
        // print_r($images);
        if ($images) {
            foreach ($images as $id) {
                wp_delete_attachment($id, true);
            }
        }
    }
    public function get_reviews_count($user_id)
    {
        $args = array(
            'posts_per_page'    => -1,
            'post_type'     => 'review',
            'post_status'   => 'publish',
            'meta_key'      => 'profile_user',
            'meta_value'    => $user_id
        );
        // query
        $post_query = new WP_Query($args);
        $total_rating = 0;
        if ($post_query->found_posts > 0) {
            while ($post_query->have_posts()) {
                $post_query->the_post();
                $total_rating += get_field('review_rating');
            }
            return ['avg' => round(($total_rating / $post_query->found_posts), 1), 'total' => $post_query->found_posts];
        }
        return ['avg' => 0, 'total' => $post_query->found_posts];
    }
    public function show_rating($rating)
    {
        $starRating = $rating;
        $stars = '';
        for ($n = 0; $n < 5; $n++) {
            $remaining = $starRating - $n;
            $icon = '';
            if ($remaining >= 1) $icon = 'fa-star';
            elseif ($remaining > 0) $icon = 'fa-star-half-o';
            elseif ($remaining <= 0) $icon = 'fa-star-o';
            $stars .= '<i class="fa ' . $icon . '"></i>';
        }
        echo $stars;
    }

    public function relative_time()
    {
        $post_date = get_post_time('U');
        $delta = current_time('timestamp') - $post_date;
        if ($delta < 60) {
            echo 'Less than a minute ago';
        } elseif ($delta > 60 && $delta < 120) {
            echo 'About a minute ago';
        } elseif ($delta > 120 && $delta < (60 * 60)) {
            echo strval(round(($delta / 60), 0)), ' minutes ago';
        } elseif ($delta > (60 * 60) && $delta < (120 * 60)) {
            echo 'About an hour ago';
        } elseif ($delta > (120 * 60) && $delta < (24 * 60 * 60)) {
            echo strval(round(($delta / 3600), 0)), ' hours ago';
        } else {
            echo the_time('j\<\s\u\p\>S\<\/\s\u\p\> M y g:i a');
        }
    }

    /**
     * Callback for the core 'peepso_widgets' filter; appends our widgets to the list
     * @param $widgets
     * @return array
     */
    public function register_widgets($widgets)
    {
        // register widgets
        // @TODO that's too hacky - why doesn't autoload work?
        foreach (scandir($widget_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'widgets' . DIRECTORY_SEPARATOR) as $widget) {
            if (strlen($widget) >= 5) require_once($widget_dir . $widget);
        }

        return array_merge($widgets, $this->widgets);
    }
    public function config_email_messages($emails)
    {
        $emails['email_review_message'] = array(
            'title' => __('Review email', 'peepso-core'),
            'description' => __('This will be sent to a user when new review received.', 'peepso-core')
        );

        return ($emails);
    }
    public function get_review($review_id)
    {
        $review = get_post($review_id);
        $data = array();
        if ($review && !is_null($review)) {
            $data['title'] = $review->post_title;
            $data['description'] = $review->post_content;
            $data['star_rating'] = get_field('review_rating', $review_id);
            $images = get_field('review_images', $review_id);
            $image_html = '<div style="display:flex;">';
            if ($images) {
                foreach ($images as $image_id) {
                    $image_html .= '<img width="150" height="105" src="' . wp_get_attachment_image_url($image_id, 'adverts-upload-thumbnail') . ' " style="margin:0 10px;">';
                }
            }
            $image_html .= '</iv>';
            $data['images'] = $image_html;
        }
        return $data;
    }
    public function send_email_review($view_user_id, $review_id)
    {
        $review_data = $this->get_review($review_id);
        $_user    =  PeepSouser::get_instance($view_user_id);
        $data['userfullname'] = $_user->get_fullname();
        $profile_slug   = PeepSo::get_option('reviews_profiles_slug', 'reviews', TRUE);
        $data['profileurl']   = $_user->get_profileurl() . $profile_slug;
        $current_user = PeepSouser::get_instance(get_current_user_id());
        $data['fromfullname'] = $current_user->get_fullname();
        $data['review_title'] = $review_data['title'];
        $data['star_rating'] = $review_data['star_rating'];
        $data['review_experience'] = $review_data['description'];
        $data['review_images'] = $review_data['images'];
        PeepSoMailQueue::add_message($_user->get_id(), $data, __('New Review Received - Find Your Reptile', 'peepsoreviews'), "review_message", "review_message", 0, 1, 1);
    }
}

PeepSoReviews::get_instance();

// EOF