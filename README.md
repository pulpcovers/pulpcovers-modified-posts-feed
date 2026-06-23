# Modified Posts Feed

A WordPress plugin that generates an RSS feed of recently modified posts, ordered by last modified date.

[![WordPress](https://img.shields.io/badge/WordPress-6.2%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License: GPL v2+](https://shields.io)](https://spdx.org/licenses/GPL-2.0-or-later.html)

## Description

Modified Posts Feed creates a custom RSS feed that displays your most recently edited posts at the top. Unlike the standard WordPress RSS feed which shows posts by publication date, this feed highlights content updates.

Ideal for content-focused sites that regularly update existing articles, documentation sites where accuracy matters, and any site where content evolves over time.

### Key Features

- Automatic sorting by last modified date
- Featured image support via Media RSS
- Performance optimized with transient caching and database indexing
- Multisite compatible
- Easy configuration via WordPress admin

## Installation

### Via WordPress Admin

1. Download the latest release
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the downloaded ZIP file and install
4. Activate the plugin

### Manual Installation

1. Upload the `modified-posts-feed` folder to `/wp-content/plugins/`
2. Activate through the **Plugins** menu in WordPress

## Usage

After activation, your feed is available at:

https://yoursite.com/feed/modified-posts


Configure the plugin at **Settings > Pulpcovers Modified Posts Feed**.

## Settings Reference

### Feed Settings

**Feed URL Slug**
- Default: `modified-posts`
- Customize the URL path for your feed
- Only lowercase letters, numbers, and hyphens allowed

**Posts Per Page**
- Default: `10`
- Range: 1-100
- Recommended: 10

**Post Types**
- Default: `post`
- Select which content types to include (posts, pages, custom post types)

**Featured Images**
- Default: Disabled
- Include featured images using Media RSS format

### Database Index Settings

**Enable Database Index**
- Default: Disabled
- Adds an index on the `post_modified` column to improve query performance
- Recommended for sites with many posts
- Index is automatically added or removed when the setting is saved

### Cache Settings

**Enable Caching**
- Default: Disabled
- Stores generated feed in WordPress transients
- Significantly reduces server load

**Cache Duration**
- 3600 seconds
- Cache automatically clears when posts are saved/deleted or settings change

## Feed Format

The plugin generates RSS 2.0 feeds with standard elements plus:

- `<content:encoded>` - Full post content
- `<media:content>` - Featured image (if enabled)
- Posts ordered by modification date (newest first)

## Requirements

- WordPress 6.2 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## FAQ

**Does this replace the default WordPress feed?**

No, your standard feed at `/feed/` remains unchanged. This creates an additional feed.

**What counts as "modified"?**

WordPress updates the modification date when post content, title, excerpt, featured image, categories, tags, or custom fields change. Comments and view counts do not trigger updates.

**Will this slow down my site?**

No. The plugin includes transient caching and database indexing for optimal performance.

**Feed shows 404 error**

Go to **Settings > Permalinks** and click "Save Changes" to flush rewrite rules.

**What happens when the plugin is uninstalled?**

When the plugin is permanently deleted, all plugin settings, 
the feed cache, and the database index are automatically 
removed. Rewrite rules are also flushed.

## Performance

The plugin is optimized for performance:

- **Transient Caching:** Stores generated feed to avoid repeated queries
- **Database Index:** Speeds up queries significantly

## Changelog

### 1.3
- Added activation hook for automatic rewrite flush
- Cache now clears automatically when any feed setting changes
- Server-side enforcement of posts per page limit (1-100)
- Rewrite rules automatically flush when feed slug changes

### 1.2
- Added full post content to feed
  
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

GPLv2 License - see the [LICENSE](LICENSE) file for details.

## Credits

Developed by [PulpCovers.com](https://pulpcovers.com)
