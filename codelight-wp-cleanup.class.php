<?php

/*
 * This is just a container class.
 */

class Codelight_WP_Cleanup
{

    /**
     * Get rid of the Customizer.
     * Thanks to https://wordpress.org/plugins/customizer-remove-all-parts/
     */
    public function disable_customizer()
    {
        add_action('admin_init', function () {
            remove_action('plugins_loaded', '_wp_customize_include', 10);
            remove_action('admin_enqueue_scripts', '_wp_customize_loader_settings', 11);
            add_action('load-customize.php', function () {
                wp_die(__('The customizer is disabled.', 'codelight'));
            });
        });

        add_action('init', function () {
            add_filter('map_meta_cap', function ($caps = [], $cap = '', $user_id = 0, $args = []) {
                if ($cap == 'customize') {
                    return ['nope'];
                }
                return $caps;
            }, 10, 4);
        });
    }

    /*
     * XML-RPC is rarely used anyway, so it's turned off by default.
     *
     * Enable XML-RPC: add_theme_support('cl-xmlrpc');
     */
    public function disable_xmlrpc()
    {
        add_filter('xmlrpc_enabled', function () {
            return false;
        });
    }

    /*
     * This disables update CHECKS, http request for plugin, core and theme
     * updates. Currently WP still makes a lone request against plugin api,
     * not sure. This speeds up first load of plugins and other wp-admin pages.
     *
     * Enable: add_theme_support('cl-disable-plugin-update-check');
     */
    public function disable_plugin_update_check()
    {
        add_filter('pre_site_transient_update_core', [$this, 'remove_core_updates']);
        add_filter('pre_site_transient_update_plugins', [$this, 'remove_core_updates']);
        add_filter('pre_site_transient_update_themes', [$this, 'remove_core_updates']);
    }

    public function remove_core_updates()
    {
        global $wp_version;
        return (object)[
            'last_checked'    => time(),
            'version_checked' => $wp_version,
        ];
    }

    /*
     * For users who don't have edit_posts capability:
     * - hide admin bar
     * - redirect to home page after login
     * - disallow access to dashboard
     *
     * Enable: add_theme_support('cl-restricted-dashboard-access');
     */
    public function restrict_dashboard_access()
    {
        if (!current_user_can('edit_posts')) {
            show_admin_bar(false);
            add_filter('login_redirect', [$this, 'login_redirect'], 10, 3);
            add_action('admin_init', [$this, 'dashboard_redirect']);
        }
    }

    public function login_redirect($redirect_to, $request, $user)
    {
        return home_url();
    }

    public function dashboard_redirect()
    {
        wp_safe_redirect(home_url());
    }

    /*
     * By default, remove "Tools" from admin menu for non-admin users.
     *
     * Disable: add_theme_support('cl-enable-tools')
     */
    public function remove_tools()
    {

        if ($this->user_is_admin()) {
            return;
        }

        add_action('admin_menu', [$this, 'remove_menu_tools_page']);
    }

    public function remove_menu_tools_page()
    {

        remove_menu_page('tools.php');
    }

    /*
     * Remove "Comments" from admin menu, admin bar; disable Posts & Pages post type comment support; remove Recent Comments dashboard widget
     * As per http://wordpress.stackexchange.com/a/17936
     *
     * Enable: add_theme_support('cl-remove-comments')
     */
    public function remove_comments()
    {
        add_action('init', [$this, 'remove_default_post_types_comment_support'], 100);
        add_action('wp_before_admin_bar_render', [$this, 'remove_admin_bar_comments']);
        add_action('admin_menu', [$this, 'remove_menu_comments_page']);
        add_action('wp_dashboard_setup', [$this, 'remove_dashboard_comments_widget']);
    }

    public function remove_menu_comments_page()
    {
        remove_menu_page('edit-comments.php');
    }

    public function remove_default_post_types_comment_support()
    {
        remove_post_type_support('post', 'comments');
        remove_post_type_support('page', 'comments');
    }

    public function remove_admin_bar_comments()
    {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu('comments');
    }

    public function remove_dashboard_comments_widget()
    {
        global $wp_meta_boxes;
        unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
    }

    /*
     * By default, for non-admin users:
     *
     * - Hide Appearance > [Themes, Customize, Header, Background]
     * - Remove useless dashboard widgets
     * - Hide update notifications
     *
     * Disable: add_theme_support('cl-disable-admin-cleanup')
     */
    public function admin_cleanup()
    {

        if ($this->user_is_admin()) {
            return;
        }

        $this->hide_update_notifications();

        add_action('admin_menu', [$this, 'remove_menu_pages']);
        add_action('wp_dashboard_setup', [$this, 'remove_dashboard_widgets']);

    }

    /*
     * Hide Appearance > [Themes, Customize, Header, Background]
     *
     * Disable: add_theme_support('cl-disable-admin-cleanup')
     */
    public function remove_menu_pages()
    {
        remove_submenu_page('themes.php', 'themes.php');
        remove_submenu_page('themes.php', 'customize.php');
        remove_submenu_page('themes.php', 'custom-header');
        remove_submenu_page('themes.php', 'custom-background');
        remove_submenu_page('themes.php', 'theme_activation_options');
    }

    /*
     * Remove the following Dashboard widgets:
     * - QuickPress
     *
     * Disable: add_theme_support('cl-disable-admin-cleanup')
     */
    public function remove_dashboard_widgets()
    {
        global $wp_meta_boxes;
        unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
    }


    /*
     * Bulk unregister widgets.
     *
     * Special args: 'blog', 'misc'
     *
     * Modify: add_filter('cl_remove_widgets', array $args)
     */
    public function remove_widgets($widgets)
    {

        foreach ($widgets as $widget) {

            if ($widget == 'blog') {
                $this->unregister_blog_widgets();
                continue;
            }

            if ($widget == 'misc') {
                $this->unregister_useless_widgets();
                continue;
            }

            unregister_widget($widget);
        }

        if (current_theme_supports('cl-remove-comments')) {
            unregister_widget('WP_Widget_Recent_Comments');
        }

    }

    /*
     * By default, unregister default blog-related widgets
     *
     * Disable: add_theme_support('cl-disable-widget-cleanup')
     */
    public function unregister_blog_widgets()
    {
        unregister_widget('WP_Widget_Archives');
        unregister_widget('WP_Widget_Categories');
        unregister_widget('WP_Widget_Recent_Posts');
        unregister_widget('WP_Widget_RSS');
    }

    /*
     * By default, unregister the utterly pointless default widgets
     *
     * Disable: add_theme_support('cl-disable-widget-cleanup')
     */
    public function unregister_useless_widgets()
    {
        unregister_widget('WP_Widget_Pages');
        unregister_widget('WP_Widget_Calendar');
        unregister_widget('WP_Widget_Links');
        unregister_widget('WP_Widget_Meta');
        unregister_widget('WP_Widget_Tag_Cloud');
    }

    /*
     * Hide update notifications;
     *
     * Disable: add_theme_support('cl-disable-admin-cleanup')
     */
    public function hide_update_notifications()
    {
        remove_action('admin_notices', 'update_nag', 3);
    }


    /*
     * Restrict tinymce formats to p, h2, h3, h4.
     *
     * Disable: add_theme_support('cl-disable-tinymce-cleanup')
     */
    public function tinymce_cleanup($formats)
    {
        $formats['block_formats'] = 'Paragraph=p;Heading h2=h2;Heading h3=h3;Heading h4=h4';
        return $formats;
    }

    /*
     * Force IE to use the Edge engine for rendering
     *
     * Since the X-UA-COMPATIBLE meta tag is not valid W3C HTML5,
     * it can be sent with headers instead as a workaround.
     *
     * add_filter('wp_headers', array($this, 'add_x_ua_compatible_header'));
     */
    public function add_x_ua_compatible_header($headers)
    {
        $headers['X-UA-Compatible'] = 'IE=edge,chrome=1';
        return $headers;
    }


    /*
     * Limit post revisions to reduce DB bloat
     *
     * Modify: manually set WP_POST_REVISIONS constant
     */
    public function limit_post_revisions($number = 5)
    {
        if (!defined('WP_POST_REVISIONS')) {
            add_filter('wp_revisions_to_keep', function () use ($number) {
                return $number;
            });
        }
    }


    /*
     * Disable feeds
     *
     * This should be used if the site doesn't use "posts".
     *
     * Enable: add_theme_support('cl-remove-feeds')
     */
    public function remove_feeds()
    {
        if (!is_admin()) {
            add_action('parse_query', [$this, 'parse_feed_query']);
        }
    }

    public function parse_feed_query($obj)
    {
        if ($obj->is_feed() && $obj->is_main_query()) {
            $obj->is_feed = false;
            $obj->set_404();
        }
    }


    /*
     * Remove search and all related functionality
     *
     * Enable: add_theme_support('cl-remove-search')
     */
    public function remove_search()
    {
        add_action('widgets_init', [$this, 'disable_search_widget'], 1);
        if (!is_admin()) {
            add_action('parse_query', [$this, 'parse_search_query'], 5);
        }
        add_filter('get_search_form', [$this, 'get_search_form'], 999);
    }

    public function disable_search_widget()
    {
        unregister_widget('WP_Widget_Search');
    }

    public function parse_search_query($obj)
    {
        if ($obj->is_search && $obj->is_main_query()) {
            unset($_GET['s']);
            unset($_GET['search']);
            unset($_POST['s']);
            unset($_REQUEST['s']);
            unset($obj->query['s']);
            $obj->set('s', '');
            $obj->is_search = false;
            $obj->set_404();
        }
    }

    public function get_search_form($form)
    {
        return '';
    }


    /*
     * Disable specified Archive pages
     *
     * This is useful for removing access to pages that exist by default
     * thanks to a generic index.php or archive.php template,
     * and can look visually broken if they're not styled separately.
     *
     * Valid types are: 'category', 'tag', 'author', 'date', 'attachment' (which is technically not an archive)
     *                  or 'archive' to disable everything
     *
     * Note that disabling attachments will redirect attachment pages to their parent post (or front page if unattached)
     *
     * Enable: add_filter('cl_remove_archives', array $types)
     */
    public function remove_archive_pages($types = [])
    {

        if (empty($types)) {
            return;
        }

        if (in_array('archive', $types)) {
            add_action('parse_query', [$this, 'parse_archive_query']);

            // Also unregister tags & categories
            add_action('init', [$this, 'unregister_category_taxonomy']);
            add_action('init', [$this, 'unregister_tag_taxonomy']);
        }

        if (in_array('category', $types)) {
            add_action('parse_query', [$this, 'parse_category_query']);
            add_action('init', [$this, 'unregister_category_taxonomy']);
        }

        if (in_array('tag', $types)) {
            add_action('parse_query', [$this, 'parse_tag_query']);
            add_action('init', [$this, 'unregister_tag_taxonomy']);
        }

        if (in_array('author', $types)) {
            add_action('parse_query', [$this, 'parse_author_query']);
        }

        if (in_array('date', $types)) {
            add_action('parse_query', [$this, 'parse_date_query']);
        }

        if (in_array('attachment', $types)) {
            add_action('template_redirect', [$this, 'parse_attachment_query']);
        }
    }

    public function parse_archive_query($obj)
    {
        if (!is_admin() && $obj->is_archive() && $obj->is_main_query()) {
            $obj->is_archive = false;
            $obj->set_404();
        }
    }

    public function parse_category_query($obj)
    {
        if (!is_admin() && $obj->is_category() && $obj->is_main_query()) {
            $obj->is_category = false;
            $obj->set_404();
        }
    }

    public function parse_tag_query($obj)
    {
        if (!is_admin() && $obj->is_tag() && $obj->is_main_query()) {
            $obj->is_tag = false;
            $obj->set_404();
        }
    }

    public function parse_author_query($obj)
    {
        if (!is_admin() && $obj->is_author() && $obj->is_main_query()) {
            $obj->is_author = false;
            $obj->set_404();
        }
    }

    public function parse_date_query($obj)
    {
        if (!is_admin() && $obj->is_date() && $obj->is_main_query()) {
            $obj->is_date = false;
            $obj->set_404();
        }
    }

    public function parse_attachment_query()
    {
        if (!is_admin() && is_attachment() && is_main_query()) {
            global $post;

            if (isset($post->post_parent) && is_numeric($post->post_parent) && ($post->post_parent != 0)) {
                wp_redirect(get_permalink($post->post_parent), 301);
                exit;
            } elseif (is_attachment()) {
                wp_redirect(get_bloginfo('wpurl'), 302);
                exit;
            }
        }
    }

    public function unregister_category_taxonomy()
    {
        register_taxonomy('category', []);
    }

    public function unregister_tag_taxonomy()
    {
        register_taxonomy('post_tag', []);
    }

    /*
     * Check if user has the "Administrator" role.
     */
    public function user_is_admin()
    {
        $current_user = wp_get_current_user();
        return in_array('administrator', $current_user->roles);
    }

    public function disable_password_changed_admin_email()
    {
        if (!function_exists('wp_password_change_notification')) {
            function wp_password_change_notification($user)
            {
            }
        }
    }

    /*public function disable_new_user_admin_email() {
        if ( !function_exists( 'wp_new_user_notification' ) ) {
            function wp_new_user_notification($user_id, $deprecated = null, $notify = '') {
                if ( $deprecated !== null ) {
                    _deprecated_argument( __FUNCTION__, '4.3.1' );
                }

                global $wpdb;
                $user = get_userdata( $user_id );

                // `$deprecated was pre-4.3 `$plaintext_pass`. An empty `$plaintext_pass` didn't sent a user notification.
                if ( 'admin' === $notify || ( empty( $deprecated ) && empty( $notify ) ) ) {
                    return;
                }

                // The blogname option is escaped with esc_html on the way into the database in sanitize_option
                // we want to reverse this for the plain text arena of emails.
                $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);


                // Generate something random for a password reset key.
                $key = wp_generate_password( 20, false );

                do_action( 'retrieve_password_key', $user->user_login, $key );

                // Now insert the key, hashed, into the DB.
                if ( empty( $wp_hasher ) ) {
                    $wp_hasher = new PasswordHash( 8, true );
                }
                $hashed = time() . ':' . $wp_hasher->HashPassword( $key );
                $wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

                $switched_locale = switch_to_locale( get_user_locale( $user ) );

                $message = sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
                $message .= __('To set your password, visit the following address:') . "\r\n\r\n";
                $message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login') . ">\r\n\r\n";

                $message .= wp_login_url() . "\r\n";

                wp_mail($user->user_email, sprintf(__('[%s] Your username and password info'), $blogname), $message);

                if ( $switched_locale ) {
                    restore_previous_locale();
                }
            }
        }
    }*/

    public function disable_user_registered_admin_email()
    {
        // based on the plugin "Disable User Registration Notification Emails"
        add_action('init', function () {
            // Unhook the actions from wp-includes/default-filters.php
            remove_action('register_new_user', 'wp_send_new_user_notifications');
            remove_action('edit_user_created_user', 'wp_send_new_user_notifications', 10);

            // Replace with our action that sends the user email only
            add_action('register_new_user', [$this, 'send_registered_user_email']);
            add_action('edit_user_created_user', [$this, 'send_registered_user_email'], 10, 2);
        });
    }

    public function send_registered_user_email($userId, $to = 'both')
    {
        if (empty($to) || $to == 'admin') {
            // Admin only, so we don't do anything
            return;
        }
        // For 'both' or 'user', we notify only the user
        wp_send_new_user_notifications($userId, 'user');
    }
}
