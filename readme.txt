=== Pulpcovers Modified Posts Feed ===
Contributors: pulpcovers
Tags: rss, feed, modified posts, updated posts, syndication
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3
License: GPLv2 or later
License URI: https://creativecommons.org/publicdomain/zero/1.0/

Creates a dedicated RSS feed of recently modified posts, ordered by last modified date. Ideal for news sites, editors, and content workflows.

== Description ==

**Pulpcovers Modified Posts Feed** adds a new RSS feed to your WordPress site that lists posts ordered by their *last modified* date instead of their publish date.

This is useful for:

- News sites that frequently update articles  
- Editorial teams who want to track recent content changes  
- Automated systems that monitor content updates  
- Anyone who needs a feed of *updated* posts, not just newly published ones  

The plugin is lightweight, fast, and includes optional caching and database indexing for improved performance on large sites.

### ⚙️ Settings
All options are configurable via **Settings → Pulpcovers Modified Posts Feed**:
- Feed URL slug
- Number of posts
- Post types to include
- Featured image output
- Feed caching
- Database index

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through **Plugins → Installed Plugins**
3. Visit **Settings → Pulpcovers Modified Posts Feed** to configure options
4. Your feed will be available at:  
   `https://yoursite.com/feed/modified-posts/`

== Frequently Asked Questions ==

= Can I change the feed URL slug? =  
Yes. Go to **Settings → Pulpcovers Modified Posts Feed** and change the slug.  
Rewrite rules will automatically flush.

= Does this affect my main RSS feed? =  
No. This plugin creates a *separate* feed and does not modify the default WordPress feeds.

= Does it support custom post types? =  
Yes. Any public post type can be included.

= Does it support featured images? =  
Yes. When enabled, the feed outputs `<media:content>` tags compatible with most feed readers.

= Is caching required? =  
No, but it is recommended for performance, especially on large sites.

= What does the database index do? =  
It adds an index on the `post_modified` column to speed up queries.  
This is optional but recommended for high‑traffic or large‑content sites.

= What happens when the plugin is uninstalled? =
When the plugin is permanently deleted, all plugin settings, 
the feed cache, and the database index are automatically removed. 
Rewrite rules are also flushed.

== Changelog ==

= 1.3 =
* Added activation hook for automatic rewrite flush
* Cache now clears automatically when any feed setting changes
* Server-side enforcement of posts per page limit (1-100)
* Rewrite rules automatically flush when feed slug changes

= 1.2 =
* Added full post content to feed

= 1.0.1 =
* Manual action buttons to settings page
* Copy-to-clipboard for feed URL
* Code cleanup and documentation

= 1.0.0 =
* Initial release
