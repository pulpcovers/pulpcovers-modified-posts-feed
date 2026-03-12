# Modified Posts Feed

A WordPress plugin that generates an RSS feed of recently modified posts, ordered by last modified date.

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-CC0--1.0-green.svg)](https://creativecommons.org/publicdomain/zero/1.0/)

## Description

Modified Posts Feed creates a custom RSS feed that displays your most recently edited posts at the top. Unlike the standard WordPress RSS feed which shows posts by publication date, this feed highlights content updates.

Ideal for content-focused sites that regularly update existing articles, documentation sites where accuracy matters, and any site where content evolves over time.

### Key Features

- Automatic sorting by last modified date
- Featured image support via Media RSS
- Performance optimized with transient caching and database indexing
- Multisite compatible
- Easy configuration via WordPress admin
- Customizable with filters and actions

## Installation

### Via WordPress Admin

1. Download the latest release
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the downloaded ZIP file and install
4. Activate the plugin

### Manual Installation

1. Upload the `modified-posts-feed` folder to `/wp-content/plugins/`
2. Activate through the **Plugins** menu in WordPress

### Via Git

```bash
cd wp-content/plugins
git clone https://github.com/pulpcovers/Modified-Posts-Feed.git modified-posts-feed
```
## Usage

After activation, your feed is available at:

https://yoursite.com/feed/modified-posts


Configure the plugin at **Settings > Modified Posts Feed**.

## Settings Reference

### Feed Settings

**Feed URL Slug**
- Default: `modified-posts`
- Customize the URL path for your feed
- Only lowercase letters, numbers, and hyphens allowed

**Posts Per Page**
- Default: `50`
- Range: 1-500
- Recommended: 50-100

**Post Types**
- Default: `post`
- Select which content types to include (posts, pages, custom post types)

**Featured Images**
- Default: Enabled
- Include featured images using Media RSS format

### Cache Settings

**Enable Caching**
- Default: Enabled
- Stores generated feed in WordPress transients
- Significantly reduces server load

**Cache Duration**
- Default: `900` seconds (15 minutes)
- Range: 60-86400 seconds
- Cache automatically clears when posts are saved/deleted or settings change

### Database Index Settings

**Add Index on Activation**
- Default: Enabled
- Adds database index to `post_modified` column
- Improves query speed 10-50x
- Recommended: Keep enabled

**Remove Index on Deactivation**
- Default: Disabled
- Removes database index when plugin is deactivated

**Remove Index on Uninstall**
- Default: Disabled
- Removes database index when plugin is permanently deleted

## Feed Format

The plugin generates RSS 2.0 feeds with standard elements plus:

- `<dc:modified>` - Last modified date
- `<media:content>` - Featured image (if enabled)
- Posts ordered by modification date (newest first)

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## FAQ

**Does this replace the default WordPress feed?**

No, your standard feed at `/feed/` remains unchanged. This creates an additional feed.

**What counts as "modified"?**

WordPress updates the modification date when post content, title, excerpt, featured image, categories, tags, or custom fields change. Comments and view counts do not trigger updates.

**Will this slow down my site?**

No. The plugin includes transient caching, database indexing, and lazy loading for optimal performance.

**Feed shows 404 error**

Go to **Settings > Permalinks** and click "Save Changes" to flush rewrite rules.

**Feed shows old content**

Click "Clear Cache Now" in plugin settings.

## Performance

The plugin is optimized for performance:

- **Transient Caching:** Stores generated feed to avoid repeated queries
- **Database Index:** Speeds up queries significantly
- **Lazy Loading:** Settings only load when needed
- **HTTP Cache Headers:** Proper `Cache-Control` and `Last-Modified` headers

## Developer Documentation

### Available Filters

**Change feed slug:**
(php code)
add_filter('modified_posts_feed_slug', function($slug) {
    return 'updates';
});
(end code)

**Change posts per page:**
(php code)
add_filter('modified_posts_feed_limit', function($limit) {
    return 100;
});
(end code)

**Include custom post types:**
(php code)
add_filter('modified_posts_feed_post_types', function($post_types) {
    return array('post', 'page', 'portfolio');
});
(end code)

**Disable caching:**
(php code)
add_filter('modified_posts_feed_enable_cache', '__return_false');
(end code)

**Change cache duration:**
(php code)
add_filter('modified_posts_feed_cache_duration', function($duration) {
    return 1800; // 30 minutes
});
(end code)

**Toggle featured images:**
(php code)
add_filter('modified_posts_feed_show_images', '__return_false');
(end code)

### Available Actions

**Modify feed query:**
(php code)
add_action('modified_posts_feed_query', function($query) {
    $query->set('category_name', 'news');
});
(end code)

**Add custom RSS elements:**
(php code)
add_action('rss2_item', function() {
    echo '<custom:element>Value</custom:element>';
});
(end code)

### Database Index Control

Control index behavior programmatically:

(php code)
// Prevent index creation
update_option('modified_posts_feed_add_index', false);

// Remove index on deactivation
update_option('modified_posts_feed_remove_index_on_deactivate', true);

// Remove index on uninstall
update_option('modified_posts_feed_remove_index_on_uninstall', true);
(end code)

## Changelog

### 1.0.1
- Added automatic rewrite flush on activation
- Added manual action buttons to settings page
- Added copy-to-clipboard for feed URL
- Improved documentation

### 1.0.0
- Initial release

## Support

- Issues: [GitHub Issues](https://github.com/pulpcovers/Modified-Posts-Feed/issues)
- Website: [PulpCovers.com](https://pulpcovers.com)

## License

CC0-1.0 License - see the [LICENSE](LICENSE) file for details.

## Credits

Developed by [PulpCovers.com](https://pulpcovers.com)
