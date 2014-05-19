<?php

/*
 * This is just a container class.
 */
class Codelight_WP_Cleanup {

    /*
     * XML-RPC is rarely used anyway, so it's turned off by default.
     *
     * Enable XML-RPC: add_theme_support('cl-xmlrpc');
     */
    public function disable_xmlrpc() {
        add_filter('xmlrpc_enabled', '__return_false');
    }


    /*
     * For users who don't have edit_posts capability:
     * - hide admin bar
     * - redirect to home page after login
     * - disallow access to dashboard
     *
     * Enable: add_theme_support('cl-restricted-dashboard-access');
     */
    public function restrict_dashboard_access() {
        if (!current_user_can('edit_posts')) {
            show_admin_bar(false);
            add_filter('login_redirect', array($this, 'login_redirect'), 10, 3);
            add_action('admin_init', array( $this, 'dashboard_redirect' ));
        }
    }

    public function login_redirect($redirect_to, $request, $user) {
        return home_url();
    }

    public function dashboard_redirect() {
        wp_safe_redirect(home_url());
    }

    /*
     * By default, remove "Tools" from admin menu for non-admin users.
     *
     * Disable: add_theme_support('cl-enable-tools')
     */
    public function remove_tools() {

        if ($this->user_is_admin()) {
            return;
        }

        add_action('admin_menu', array($this, 'remove_menu_tools_page'));
    }

    public function remove_menu_tools_page() {
        
        remove_menu_page('tools.php');
    }

    /*
     * Remove "Comments" from admin menu, admin bar; disable Posts & Pages post type comment support; remove Recent Comments dashboard widget
     * As per http://wordpress.stackexchange.com/a/17936
     *
     * Enable: add_theme_support('cl-remove-comments')
     */
    public function remove_comments() {
        add_action( 'init', array($this, 'remove_default_post_types_comment_support'), 100 );
        add_action( 'wp_before_admin_bar_render', array($this, 'remove_admin_bar_comments') );
        add_action( 'admin_menu', array($this, 'remove_menu_comments_page') );
        add_action( 'wp_dashboard_setup', array($this, 'remove_dashboard_comments_widget') );
    }

    public function remove_menu_comments_page() {
        remove_menu_page('edit-comments.php');
    }

    public function remove_default_post_types_comment_support() {
        remove_post_type_support( 'post', 'comments' );
        remove_post_type_support( 'page', 'comments' );
    }

    public function remove_admin_bar_comments() {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu('comments');
    }

    public function remove_dashboard_comments_widget() {
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
    public function admin_cleanup() {

        if ($this->user_is_admin()) {
            return;
        }

        $this->hide_update_notifications();
        
        add_action( 'admin_menu', array($this, 'remove_menu_pages') );
        add_action( 'wp_dashboard_setup', array($this, 'remove_dashboard_widgets') );

    }

    /*
     * Hide Appearance > [Themes, Customize, Header, Background]
     *
     * Disable: add_theme_support('cl-disable-admin-cleanup')
     */
    public function remove_menu_pages() {
        remove_submenu_page('themes.php', 'themes.php');
        remove_submenu_page('themes.php', 'customize.php');
        remove_submenu_page('themes.php', 'custom-header');
        remove_submenu_page('themes.php', 'custom-background');
    }

    /*
     * Remove the following Dashboard widgets:
     * - QuickPress
     *
     * Disable: add_theme_support('cl-disable-admin-cleanup')
     */
    public function remove_dashboard_widgets() {
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
    public function remove_widgets($widgets) {

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

        if ( current_theme_supports('cl-remove-comments') ) {
            unregister_widget('WP_Widget_Recent_Comments'); 
        }

    }

    /*
     * By default, unregister default blog-related widgets
     *
     * Disable: add_theme_support('cl-disable-widget-cleanup')
     */
    public function unregister_blog_widgets() {
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
    public function unregister_useless_widgets() {
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
    public function hide_update_notifications() {
        remove_action('admin_notices', 'update_nag', 3);
    }


    /*
     * Force IE to use the Edge engine for rendering
     *
     * Since the X-UA-COMPATIBLE meta tag is not valid W3C HTML5,
     * it can be sent with headers instead as a workaround.
     * 
     * add_filter('wp_headers', array($this, 'add_x_ua_compatible_header'));
     */ 
    public function add_x_ua_compatible_header($headers) {
        $headers['X-UA-Compatible'] = 'IE=edge,chrome=1';
        return $headers;
    }


    /*
     * Limit post revisions to reduce DB bloat
     *
     * Modify: manually set WP_POST_REVISIONS constant
     */
    public function limit_post_revisions($number = 5) {
        if (!defined('WP_POST_REVISIONS')) {
            define('WP_POST_REVISIONS', $number);
        }
    }


    /*
     * Disable feeds
     *
     * This should be used if the site doesn't use "posts".
     *
     * Enable: add_theme_support('cl-remove-feeds')
     */
    public function remove_feeds() {
        if ( ! is_admin() ) {
            add_action('parse_query', array($this, 'parse_feed_query'));
        }
    }

    public function parse_feed_query($obj) {
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
    public function remove_search() {
        add_action('widgets_init', array($this, 'disable_search_widget'), 1);
        if ( ! is_admin() ) {
            add_action('parse_query', array($this, 'parse_search_query'), 5);
        }
        add_filter('get_search_form', array($this, 'get_search_form'), 999);
    }

    public function disable_search_widget() {
        unregister_widget( 'WP_Widget_Search' );
    }

    public function parse_search_query($obj) {
        if ($obj->is_search && $obj->is_main_query()) {
            unset( $_GET['s'] );
            unset( $_GET['search'] );
            unset( $_POST['s'] );
            unset( $_REQUEST['s'] );
            unset( $obj->query['s'] );
            $obj->set('s', '');
            $obj->is_search = false;
            $obj->set_404();
        }
    }

    public function get_search_form($form) {
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
    public function remove_archive_pages($types = array()) {

        if (empty($types)) {
            return;
        }

        if (in_array('archive', $types)) {
            add_action('parse_query', array($this, 'parse_archive_query'));

            // Also unregister tags & categories
            add_action('init', array($this, 'unregister_category_taxonomy'));
            add_action('init', array($this, 'unregister_tag_taxonomy'));
        }

        if (in_array('category', $types)) {
            add_action('parse_query', array($this, 'parse_category_query'));
            add_action('init', array($this, 'unregister_category_taxonomy'));
        }

        if (in_array('tag', $types)) {
            add_action('parse_query', array($this, 'parse_tag_query'));
            add_action('init', array($this, 'unregister_tag_taxonomy'));
        }

        if (in_array('author', $types)) {
            add_action('parse_query', array($this, 'parse_author_query'));
        }

        if (in_array('date', $types)) {
            add_action('parse_query', array($this, 'parse_date_query'));
        }

        if (in_array('attachment', $types)) {
            add_action('template_redirect', array($this, 'parse_attachment_query'));
        }
    }

    public function parse_archive_query($obj) {
        if (!is_admin() && $obj->is_archive() && $obj->is_main_query()) {
            $obj->is_archive = false;
            $obj->set_404();
        }
    }

    public function parse_category_query($obj) {
        if (!is_admin() && $obj->is_category() && $obj->is_main_query()) {
            $obj->is_category = false;
            $obj->set_404();
        }
    }

    public function parse_tag_query($obj) {
        if (!is_admin() && $obj->is_tag() && $obj->is_main_query()) {
            $obj->is_tag = false;
            $obj->set_404();
        }
    }

    public function parse_author_query($obj) {
        if (!is_admin() && $obj->is_author() && $obj->is_main_query()) {
            $obj->is_author = false;
            $obj->set_404();
        }
    }

    public function parse_date_query($obj) {
        if (!is_admin() && $obj->is_date() && $obj->is_main_query()) {
            $obj->is_date = false;
            $obj->set_404();
        }
    }

    public function parse_attachment_query() {
        if (!is_admin() && is_attachment() && is_main_query()) {
            global $post;

            if ( isset($post->post_parent) && is_numeric($post->post_parent) && ($post->post_parent != 0) ) {
                wp_redirect(get_permalink($post->post_parent), 301);
                exit;
            } elseif ( is_attachment() ) {
                wp_redirect(get_bloginfo('wpurl'), 302);
                exit;   
            }
        }
    }

    public function unregister_category_taxonomy() {
        register_taxonomy('category', array());
    }

    public function unregister_tag_taxonomy() {
        register_taxonomy('post_tag', array());
    }

    /*
     * Check if user has the "Administrator" role.
     */
    public function user_is_admin() {
        $current_user = wp_get_current_user();
        return in_array('administrator', $current_user->roles);
    }

}
