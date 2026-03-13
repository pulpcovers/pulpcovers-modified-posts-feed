=== Modified Posts Feed ===
Contributors: pulpcovers
Tags: rss, feed, modified posts, updated posts, syndication
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.1
License: CC0-1.0
License URI: https://creativecommons.org/publicdomain/zero/1.0/

Creates a dedicated RSS feed of recently modified posts, ordered by last modified date. Ideal for news sites, editors, and content workflows.

== Description ==

**Modified Posts Feed** adds a new RSS feed to your WordPress site that lists posts ordered by their *last modified* date instead of their publish date.

This is useful for:

- News sites that frequently update articles  
- Editorial teams who want to track recent content changes  
- Automated systems that monitor content updates  
- Anyone who needs a feed of *updated* posts, not just newly published ones  

The plugin is lightweight, fast, and includes optional caching and database indexing for improved performance on large sites.

### 🔧 Features

- Adds a new RSS feed at:  
  `https://yoursite.com/feed/modified-posts/`
- Orders posts by **last modified date (DESC)**
- Supports custom post types
- Optional featured images via Media RSS
- Optional caching for performance
- Optional database index on `post_modified` for large sites
- Multisite‑compatible
- Fully customizable via filters


### Filters

Developers can customize:

- Feed slug  
- Post types  
- Post limit  
- Caching behavior  
- Featured image output  

See the source code for available filters.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through **Plugins → Installed Plugins**
3. Visit **Settings → Modified Posts Feed** to configure options
4. Your feed will be available at:  
   `https://yoursite.com/feed/modified-posts/`

== Frequently Asked Questions ==

= Can I change the feed URL slug? =  
Yes. Go to **Settings → Modified Posts Feed** and change the slug.  
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

== Changelog ==

= 1.0.1 =
* Added settings page
* Added optional featured images
* Added optional caching
* Added optional database index
* Improved multisite support
* Code cleanup and documentation

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.1 =
Recommended update. Adds settings page, caching, and performance improvements.




