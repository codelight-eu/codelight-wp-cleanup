<?php
/*
 Plugin Name: Codelight WP Cleanup
 Plugin URI: http://codelight.eu
 Description: Remove various unneeded functionality from your WP installation.
 Author: Codelight.eu
 Version: 1.1
 Author URI: http://codelight.eu
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

function codelight_wp_cleanup_init() {

    // If the support for this plugin is not specifically enabled in the active theme, do nothing
    if ( !current_theme_supports('cl-wp-cleanup') ) {
        return;
    }

    require_once('codelight-wp-cleanup.class.php');
    $cleanup = new Codelight_WP_Cleanup();
    
    // By default, turn off XML-RPC
    if ( !current_theme_supports('cl-enable-xmlrpc') ) {
        add_filter('xmlrpc_enabled', '__return_false');
    }

    // Disable admin bar and dashboard access to users without 'edit_posts' capability
    if ( current_theme_supports('cl-restricted-dashboard-access') ) {
        $cleanup->restrict_dashboard_access();
    }

    // By default, remove Tools from non-admin user menus
    if ( !current_theme_supports('cl-enable-tools') ) {
        $cleanup->remove_tools();
    }

    // Remove Comments from non-admin user menus and admin bar, remove comments from post & page types
    if ( current_theme_supports('cl-remove-comments') ) {
        $cleanup->remove_comments();
    }

    // By default, remove update notifications, hide some links from Appearance menu and clean up dashboard
    if ( !current_theme_supports('cl-disable-admin-cleanup') ) {
        $cleanup->admin_cleanup();
    }

    // Disable all feeds
    // ! Make sure to remove the Feed link from header
    if ( current_theme_supports('cl-remove-feeds') ) {
        $cleanup->remove_feeds();
    }

    // Disable search and all related functionality
    if ( current_theme_supports('cl-remove-search') ) {
        $cleanup->remove_search();
    }

    // Clean up less useful stuff from tinymce editor
    if ( !current_theme_supports('cl-disable-tinymce-cleanup') ) {
        add_filter('tiny_mce_before_init', array($cleanup, 'tinymce_cleanup'));
    }

    // Clean up un-used widgets; misc ~useless widgets are removed by default
    add_action('widgets_init', function() use ($cleanup) {
         $cleanup->remove_widgets( apply_filters('cl_remove_widgets', array('misc')) );
    });

    // Disable specific archive page types
    $cleanup->remove_archive_pages( apply_filters('cl_remove_archives', array()) );

    // By default, add X-UA-Compatible header
    add_filter('wp_headers', array($cleanup, 'add_x_ua_compatible_header'));
    
    // By default, if not specifically set to another number, limit post revisions to 5
    $cleanup->limit_post_revisions();

}

add_action('after_setup_theme', 'codelight_wp_cleanup_init');
