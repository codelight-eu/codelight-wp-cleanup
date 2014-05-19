wp-cleanup
==========

Wordpress comes with a bunch of features that are rarely needed when WP is used more as a content management system instead of a blogging platform. This can lead to broken archive pages, admin dashboard bloat and even a couple of small security concerns.

This plugin provides an easy way to selectively disable most of the stuff I've found to be useless in a CMS context.

Note that this plugin has *not* been tested thoroughly - patches and issues are welcome.

Summary
==========

The plugin provides filters to easily:
* Disable specific widgets;
* Disable Categories, Tags and specific archive pages (e.g. Author, Date, Attachment)

You can also enable the following:
* Remove admin bar and restrict dashboard access for users without the 'edit_posts' capability;
* Remove comment support from 'post' and 'page' post types; remove all (most?) comment-related stuff from admin;
* Disable feeds;
* Disable the search functionality.

And by default, the plugin:
* Disables XML-RPC;
* Hides update notifications for non-admin users;
* Removes Quick Press dashboard widget;
* Removes "Tools" from the dashboard menu for non-admin users;
* Removes Appearance > [Themes, Customize, Header, Background] from the dashboard menu for non-admin users;
* Removes a bunch of useless widgets;
* Limits post revisions to 5 (unless explicitly defined elsewhere);
* Adds the X-UA-Compatible http request header.


Configuration
==========

Everything below goes into your theme's _functions.php_

To enable the plugin:

```
add_theme_support('cl-wp-cleanup');
```
Without this, the plugin does nothing.

### Disable categories / tags / archives

Even if date / author / attachment archive templates do not exist, Wordpress will still render those pages using the default template, which can result in those pages being visually broken. These broken or simply unused pages may be indexed by search engines.

To disable specific archives:

```
// Assuming PHP 5.3.0
add_filter('cl_remove_archives', function($types) {
    return array('author', 'date', 'attachment');
});
```

Valid arguments are:
* 'archive' to remove *all* Archives and also Category and Tag taxonomies (basically everything below);
* 'category' to remove Category archives and the Category taxonomy;
* 'tag' to remove Tag archives and the Tag taxonomy;
* 'author' to remove the Author archives;
* 'date' to remove the Date archives;
* 'attachment' to *redirect* all attachment pages to their parent pages, or to the front page when the attachment is not attached to any specific page.

By default, nothing is removed.

### Disable widgets

To clean up some of the less useful default widgets:
```
add_filter('cl_remove_widgets', function($widgets) {
    return array('misc', 'blog');
});
```

Valid arguments are:
* 'misc' to remove Pages, Calendar, Links, Meta, Tag Cloud widgets;
* 'blog' to remove blog-related widgets: Archives, Categories, Recent Posts, RSS;
* or an array of specific widget class names - the full list is available in the [Codex](http://codex.wordpress.org/Function_Reference/unregister_widget)

By default, 'misc' widgets are removed.

### Restrict dashboard access
For users who don't have 'edit_posts' capability, redirects dashboard to front page, disables the admin bar and redirects to front page after login.

To enable:
```
add_theme_support('cl-restricted-dashboard-access');
```

### Remove comments
Removes comment support from Posts and Pages, hides Comments from the menu, removes Comments from admin bar and removes the Recent Comments dashboard widget.

To enable:
```
add_theme_support('cl-remove-comments');
```



### Admin cleanup
For all non-admin users, this feature:
* Hides update notifications
* Removes the following pages from admin:
    * Appearance > Themes
    * Appearance > Customize
    * Appearance > Header
    * Appearance > Background
* Removes Quick Press dashboard widget

This is just a small cleanup to the admin interface.

Note that non-admin users do not have access to the Appearance menu item by default anyway. This feature is useful when the client has a role other than Administrator but still requires access to Widgets and Menus.

To disable admin cleanup:
```
add_theme_support('cl-disable-admin-cleanup');
```

### Remove feeds
Disables feeds. Note that it doesn't remove various feed links from the head - this needs to be done manually.

```
add_theme_support('cl-remove-feeds');
```

### Remove Tools menu item

The Tools menu item is hidden by default.

To re-enable Tools:
```
add_theme_support('cl-enable-tools');
```

### XML-RPC
Disabled by default, as it's a [potential attack vector](http://blog.spiderlabs.com/2014/03/wordpress-xml-rpc-pingback-vulnerability-analysis.html). To enable:

```
add_theme_support('cl-enable-xmlrpc');
```

### Post revisions

To reduce database bloat, the number of post revisions is set to 5, unless explicitly defined. To set a custom number, simply override WP_POST_REVISIONS.

### X-UA-Compatible header

Sets the X-UA-Compatible header to force IE to *not* use compatibility mode. Read more in [this stackoverflow thread](http://stackoverflow.com/questions/6771258/whats-the-difference-if-meta-http-equiv-x-ua-compatible-content-ie-edge-e)
