<?php
/*
 Plugin Name: Codelight WP Cleanup
 Plugin URI: http://codelight.eu
 Description: Remove various unneeded functionality from your WP installation.
 Author: Codelight.eu
 Version: 1.2
 Author URI: http://codelight.eu
 */

if (!defined('WPINC')) {
    die;
}

require_once('codelight-wp-cleanup.class.php');

function codelight_wp_cleanup_init()
{
    $cleanup = new Codelight_WP_Cleanup();

    // If the support for this plugin is not specifically enabled in the active theme, do nothing
    if (!current_theme_supports('cl-wp-cleanup')) {
        return;
    }

    // Disable customizer
    if (current_theme_supports('cl-disable-customizer')) {
        $cleanup->disable_customizer();
    }

    // Disable update checks for plugins, core and themes
    if (current_theme_supports('cl-disable-plugin-update-check')) {
        $cleanup->disable_plugin_update_check();
    }

    // By default, turn off XML-RPC
    if (!current_theme_supports('cl-enable-xmlrpc')) {
        add_filter('xmlrpc_enabled', function () {
            return false;
        });
    }

    // Disable admin bar and dashboard access to users without 'edit_posts' capability
    if (current_theme_supports('cl-restricted-dashboard-access')) {
        $cleanup->restrict_dashboard_access();
    }

    // By default, remove Tools from non-admin user menus
    if (!current_theme_supports('cl-enable-tools')) {
        $cleanup->remove_tools();
    }

    // Remove Comments from non-admin user menus and admin bar, remove comments from post & page types
    if (current_theme_supports('cl-remove-comments')) {
        $cleanup->remove_comments();
    }

    // By default, remove update notifications, hide some links from Appearance menu and clean up dashboard
    if (!current_theme_supports('cl-disable-admin-cleanup')) {
        $cleanup->admin_cleanup();
    }

    // Disable all feeds
    // ! Make sure to remove the Feed link from header
    if (current_theme_supports('cl-remove-feeds')) {
        $cleanup->remove_feeds();
    }

    // Disable search and all related functionality
    if (current_theme_supports('cl-remove-search')) {
        $cleanup->remove_search();
    }

    // Clean up less useful stuff from tinymce editor
    if (!current_theme_supports('cl-disable-tinymce-cleanup')) {
        add_filter('tiny_mce_before_init', [$cleanup, 'tinymce_cleanup']);
    }

    if (in_array('all', apply_filters('cl_remove_widgets', ['misc']))) {
        // Disable all widgets
        remove_action('_admin_menu', 'wp_widgets_add_menu');
    } else {
        // Clean up un-used widgets; misc ~useless widgets are removed by default
        add_action('widgets_init', function () use ($cleanup) {
            $cleanup->remove_widgets(apply_filters('cl_remove_widgets', ['misc']));
        });
    }

    // Disable specific archive page types
    $cleanup->remove_archive_pages(apply_filters('cl_remove_archives', []));

    // By default, add X-UA-Compatible header
    add_filter('wp_headers', [$cleanup, 'add_x_ua_compatible_header']);

    // By default, if not specifically set to another number, limit post revisions to 5
    $cleanup->limit_post_revisions();
}

/**
 *  These supports have to be defined before any actions.
 */
function codelight_wp_cleanup_pluggable()
{
    $cleanup = new Codelight_WP_Cleanup();

    // By default, remove password change emails for admin
    if (!current_theme_supports('cl-enable-password-changed-admin-email')) {
        $cleanup->disable_password_changed_admin_email();
    }

    // By default, remove password change emails for admin
    /*if (!current_theme_supports('cl-enable-new-user-admin-email')) {
        $cleanup->disable_new_user_admin_email();
    }*/

    // By default, remove user registration email for admin
    if (!current_theme_supports('cl-enable-user-registered-admin-email')) {
        $cleanup->disable_user_registered_admin_email();
    }
}

add_action('after_setup_theme', 'codelight_wp_cleanup_init', 1000);
codelight_wp_cleanup_pluggable();
