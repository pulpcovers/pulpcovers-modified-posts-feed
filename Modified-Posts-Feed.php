<?php
/**
 * Plugin Name: Modified Posts Feed
 * Plugin URI: https://github.com/pulpcovers/Modified-Posts-Feed
 * Description: Generates an RSS feed of recently modified posts, ordered by last modified date
 * Version: 1.0.1
 * Author: PulpCovers.com
 * Author URI: https://pulpcovers.com
 * License: CC0-1.0
 * Text Domain: modified-posts-feed
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Modified_Posts_Feed {
    
    private $cache_key;
    private $settings_loaded = false;
    
    /**
     * CONFIGURATION DEFAULTS
     * =====================
     * These are fallback values if options are not set.
     * Users can configure these via Settings > Modified Posts Feed
     */
    
    // Feed Settings
    private $feed_slug = 'modified-posts';
    private $posts_per_page = 50;
    private $post_types = array('post');
    
    // Cache Settings
    private $cache_enabled = true;
    private $cache_duration = 900;
    
    // Feature Toggles
    private $show_featured_images = true;
    
    /**
     * Initialize the plugin
     */
    public function __construct() {
        // Make cache key unique per site (for multisite compatibility)
        $this->cache_key = 'modified_posts_feed_output_' . get_current_blog_id();
        
        add_action('init', array($this, 'init_feed'));
        add_action('pre_get_posts', array($this, 'modify_feed_query'));
        add_action('save_post', array($this, 'clear_cache'));
        add_action('deleted_post', array($this, 'clear_cache'));
        add_action('switch_theme', array($this, 'clear_cache'));
        
        // Admin settings
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_settings_page'));
            add_action('admin_init', array($this, 'register_settings'));
            add_action('admin_init', array($this, 'register_cache_clear_hooks'));
            add_action('admin_init', array($this, 'maybe_flush_rewrite_rules'));
        }
    }
    
    /**
     * Initialize feed (loads settings and registers feed)
     */
    public function init_feed() {
        $this->load_settings();
        add_feed($this->feed_slug, array($this, 'generate_feed'));
    }
    
    /**
     * Flush rewrite rules if needed after activation
     */
    public function maybe_flush_rewrite_rules() {
        if (get_transient('modified_posts_feed_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_transient('modified_posts_feed_flush_rewrite_rules');
        }
    }
    
    /**
     * Register hooks to clear cache when settings change
     */
    public function register_cache_clear_hooks() {
        add_action('update_option_blogname', array($this, 'clear_cache'));
        add_action('update_option_blogdescription', array($this, 'clear_cache'));
        add_action('update_option_modified_posts_feed_slug', array($this, 'clear_cache'));
        add_action('update_option_modified_posts_feed_posts_per_page', array($this, 'clear_cache'));
        add_action('update_option_modified_posts_feed_post_types', array($this, 'clear_cache'));
        add_action('update_option_modified_posts_feed_show_images', array($this, 'clear_cache'));
    }
    
    /**
     * Load settings from database (lazy loading with flag check)
     */
    private function load_settings() {
        if ($this->settings_loaded) {
            return;
        }
        
        // Load settings from database (with fallbacks to defaults)
        $this->feed_slug = get_option('modified_posts_feed_slug', $this->feed_slug);
        $this->posts_per_page = get_option('modified_posts_feed_posts_per_page', $this->posts_per_page);
        $this->post_types = get_option('modified_posts_feed_post_types', $this->post_types);
        $this->cache_enabled = get_option('modified_posts_feed_cache_enabled', $this->cache_enabled);
        $this->cache_duration = get_option('modified_posts_feed_cache_duration', $this->cache_duration);
        $this->show_featured_images = get_option('modified_posts_feed_show_images', $this->show_featured_images);
        
        // Apply filters to allow further customization
        $this->feed_slug = apply_filters('modified_posts_feed_slug', $this->feed_slug);
        $this->posts_per_page = apply_filters('modified_posts_feed_limit', $this->posts_per_page);
        $this->post_types = apply_filters('modified_posts_feed_post_types', $this->post_types);
        $this->cache_enabled = apply_filters('modified_posts_feed_enable_cache', $this->cache_enabled);
        $this->cache_duration = apply_filters('modified_posts_feed_cache_duration', $this->cache_duration);
        $this->show_featured_images = apply_filters('modified_posts_feed_show_images', $this->show_featured_images);
        
        $this->settings_loaded = true;
    }
    
    /**
     * Clear feed cache when content changes
     */
    public function clear_cache() {
        delete_transient($this->cache_key);
    }
    
    /**
     * Modify the feed query to order by modified date
     */
    public function modify_feed_query($query) {
        // Load settings only if this is our feed
        if ($query->is_feed()) {
            $this->load_settings();
            
            if ($query->is_feed($this->feed_slug) && !is_admin()) {
                $query->set('orderby', 'modified');
                $query->set('order', 'DESC');
                $query->set('post_status', 'publish');
                $query->set('post_type', $this->post_types);
                $query->set('posts_per_page', $this->posts_per_page);
                $query->set('no_found_rows', true);
                $query->set('update_post_meta_cache', true);
                
                do_action('modified_posts_feed_query', $query);
            }
        }
    }
    
    /**
     * Generate the RSS feed
     */
    public function generate_feed() {
        if ($this->cache_enabled) {
            $cached_feed = get_transient($this->cache_key);
            if (false !== $cached_feed) {
                $this->send_headers();
                // Cached feed output is pre-generated XML and must not be escaped.
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $cached_feed;
                exit;
            }
        }
        
        // Send headers before starting buffer
        $this->send_headers();
        
        // Start output buffering to cache the feed
        ob_start();
        
        $this->render_feed();
        
        // Cache the output
        if ($this->cache_enabled) {
            $output = ob_get_contents();
            if ($output) {
                set_transient($this->cache_key, $output, $this->cache_duration);
            }
        }
        
        ob_end_flush();
    }
    
    /**
     * Send HTTP headers
     */
    private function send_headers() {
        // Prevent sending headers twice
        if (headers_sent()) {
            return;
        }
        
        $charset = get_option('blog_charset');
        header('Content-Type: ' . feed_content_type('rss2') . '; charset=' . $charset, true);
        
        // HTTP cache headers
        header('Cache-Control: max-age=' . $this->cache_duration . ', must-revalidate');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $this->cache_duration) . ' GMT');
        
        $last_modified = get_lastpostmodified('GMT');
        if ($last_modified) {
            header('Last-Modified: ' . mysql2date('D, d M Y H:i:s', $last_modified, false) . ' GMT');
        }
    }
    
    /**
     * Render the feed XML
     */
    private function render_feed() {
        $charset = get_option('blog_charset');
        $site_name = get_bloginfo('name');
        $site_description = get_bloginfo('description');
        $feed_url = home_url('/feed/' . $this->feed_slug);
        $home_url = home_url('/');
        $last_modified = get_lastpostmodified('GMT');
        $language = get_option('rss_language');
        $update_period = apply_filters('rss_update_period', 'hourly');
        $update_frequency = apply_filters('rss_update_frequency', '1');
        $use_excerpt = get_option('rss_use_excerpt');
        
        echo '<?xml version="1.0" encoding="' . esc_attr($charset) . '"?' . '>';
        ?>

<rss version="2.0"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:wfw="http://wellformedweb.org/CommentAPI/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:atom="http://www.w3.org/2005/Atom"
    xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
    xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
    <?php if ($this->show_featured_images) : ?>xmlns:media="http://search.yahoo.com/mrss/"<?php endif; ?>
    <?php do_action('rss2_ns'); ?>>

<channel>
    <title><?php echo esc_html($site_name); ?> - Recently Modified Posts</title>
    <atom:link href="<?php echo esc_url($feed_url); ?>" rel="self" type="application/rss+xml" />
    <link><?php echo esc_url($home_url); ?></link>
    <description><?php echo esc_html($site_description); ?> - Recently Modified Posts</description>
    <?php if ($last_modified) : ?>
    <lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', $last_modified, false); ?></lastBuildDate>
    <?php endif; ?>
    <language><?php echo esc_html($language); ?></language>
    <sy:updatePeriod><?php echo esc_html($update_period); ?></sy:updatePeriod>
    <sy:updateFrequency><?php echo esc_html($update_frequency); ?></sy:updateFrequency>
    <?php do_action('rss2_head'); ?>
    
    <?php
    if (have_posts()) :
        while (have_posts()) : the_post();
            $post_id = get_the_ID();
            $pub_date = mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false);
            $mod_date = mysql2date('D, d M Y H:i:s +0000', get_the_modified_time('Y-m-d H:i:s', true), false);
            $comment_count = get_comments_number();
    ?>
        <item>
            <title><?php the_title_rss(); ?></title>
            <link><?php the_permalink_rss(); ?></link>
            <pubDate><?php echo $pub_date; ?></pubDate>
            <dc:modified><?php echo $mod_date; ?></dc:modified>
            <dc:creator><![CDATA[<?php the_author(); ?>]]></dc:creator>
            <?php the_category_rss('rss2'); ?>
            <guid isPermaLink="false"><?php the_guid(); ?></guid>
            
            <?php
            // Featured image support
            if ($this->show_featured_images && has_post_thumbnail($post_id)) {
                $thumbnail_id = get_post_thumbnail_id($post_id);
                $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'large');
                
                if ($thumbnail_url) {
                    $thumbnail_meta = wp_get_attachment_metadata($thumbnail_id);
                    echo '<media:content url="' . esc_url($thumbnail_url) . '" medium="image"';
                    
                    if (is_array($thumbnail_meta) && isset($thumbnail_meta['width'], $thumbnail_meta['height'])) {
                        echo ' width="' . absint($thumbnail_meta['width']) . '" height="' . absint($thumbnail_meta['height']) . '"';
                    }
                    echo ' />' . "\n";
                }
            }
            ?>
            
            <description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
            <?php if (!$use_excerpt) : ?>
                <content:encoded><![CDATA[<?php the_content_feed('rss2'); ?>]]></content:encoded>
            <?php endif; ?>
            
            <?php if ($comment_count > 0) : ?>
                <wfw:commentRss><?php echo esc_url(get_post_comments_feed_link($post_id, 'rss2')); ?></wfw:commentRss>
                <slash:comments><?php echo $comment_count; ?></slash:comments>
            <?php endif; ?>
            
            <?php rss_enclosure(); ?>
            <?php do_action('rss2_item'); ?>
        </item>
    <?php
        endwhile;
        wp_reset_postdata();
    endif;
    ?>
</channel>
</rss>
        <?php
    }
    
    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_options_page(
            'Modified Posts Feed Settings',
            'Modified Posts Feed',
            'manage_options',
            'modified-posts-feed',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Feed Settings
        register_setting('modified_posts_feed_settings', 'modified_posts_feed_slug', array(
            'type' => 'string',
            'default' => 'modified-posts',
            'sanitize_callback' => array($this, 'sanitize_slug')
        ));
        
        register_setting('modified_posts_feed_settings', 'modified_posts_feed_posts_per_page', array(
            'type' => 'integer',
            'default' => 50,
            'sanitize_callback' => array($this, 'sanitize_posts_per_page')
        ));
        
        register_setting('modified_posts_feed_settings', 'modified_posts_feed_post_types', array(
            'type' => 'array',
            'default' => array('post'),
            'sanitize_callback' => array($this, 'sanitize_post_types')
        ));
        
        // Cache Settings
        register_setting('modified_posts_feed_settings', 'modified_posts_feed_cache_enabled', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
        
        register_setting('modified_posts_feed_settings', 'modified_posts_feed_cache_duration', array(
            'type' => 'integer',
            'default' => 900,
            'sanitize_callback' => array($this, 'sanitize_cache_duration')
        ));
        
        // Feature Toggles
        register_setting('modified_posts_feed_settings', 'modified_posts_feed_show_images', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
        
        // Database Index Settings
        register_setting('modified_posts_feed_settings', 'modified_posts_feed_add_index', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
        
        register_setting('modified_posts_feed_settings', 'modified_posts_feed_remove_index_on_deactivate', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
        
        register_setting('modified_posts_feed_settings', 'modified_posts_feed_remove_index_on_uninstall', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
        
        // Feed Settings Section
        add_settings_section(
            'modified_posts_feed_feed_settings',
            'Feed Settings',
            array($this, 'render_feed_section_info'),
            'modified-posts-feed'
        );
        
        add_settings_field(
            'modified_posts_feed_slug',
            'Feed URL Slug',
            array($this, 'render_slug_field'),
            'modified-posts-feed',
            'modified_posts_feed_feed_settings'
        );
        
        add_settings_field(
            'modified_posts_feed_posts_per_page',
            'Posts Per Page',
            array($this, 'render_posts_per_page_field'),
            'modified-posts-feed',
            'modified_posts_feed_feed_settings'
        );
        
        add_settings_field(
            'modified_posts_feed_post_types',
            'Post Types',
            array($this, 'render_post_types_field'),
            'modified-posts-feed',
            'modified_posts_feed_feed_settings'
        );
        
        add_settings_field(
            'modified_posts_feed_show_images',
            'Featured Images',
            array($this, 'render_show_images_field'),
            'modified-posts-feed',
            'modified_posts_feed_feed_settings'
        );
        
        // Cache Settings Section
        add_settings_section(
            'modified_posts_feed_cache_settings',
            'Cache Settings',
            array($this, 'render_cache_section_info'),
            'modified-posts-feed'
        );
        
        add_settings_field(
            'modified_posts_feed_cache_enabled',
            'Enable Caching',
            array($this, 'render_cache_enabled_field'),
            'modified-posts-feed',
            'modified_posts_feed_cache_settings'
        );
        
        add_settings_field(
            'modified_posts_feed_cache_duration',
            'Cache Duration',
            array($this, 'render_cache_duration_field'),
            'modified-posts-feed',
            'modified_posts_feed_cache_settings'
        );
        
        // Database Index Section
        add_settings_section(
            'modified_posts_feed_index_settings',
            'Database Index Settings',
            array($this, 'render_index_section_info'),
            'modified-posts-feed'
        );
        
        add_settings_field(
            'modified_posts_feed_add_index',
            'Add Index on Activation',
            array($this, 'render_add_index_field'),
            'modified-posts-feed',
            'modified_posts_feed_index_settings'
        );
        
        add_settings_field(
            'modified_posts_feed_remove_index_on_deactivate',
            'Remove Index on Deactivation',
            array($this, 'render_remove_deactivate_field'),
            'modified-posts-feed',
            'modified_posts_feed_index_settings'
        );
        
        add_settings_field(
            'modified_posts_feed_remove_index_on_uninstall',
            'Remove Index on Uninstall',
            array($this, 'render_remove_uninstall_field'),
            'modified-posts-feed',
            'modified_posts_feed_index_settings'
        );
    }
    
    /**
     * Sanitization callbacks
     */
    public function sanitize_checkbox($input) {
        return (bool) $input;
    }
    
    public function sanitize_slug($input) {
        $slug = sanitize_title($input);
        if (empty($slug)) {
            add_settings_error('modified_posts_feed_messages', 'invalid_slug', 'Feed slug cannot be empty. Using default.', 'error');
            return 'modified-posts';
        }
        // Trigger rewrite flush if slug changed
        $old_slug = get_option('modified_posts_feed_slug', 'modified-posts');
        if ($old_slug !== $slug) {
            set_transient('modified_posts_feed_flush_rewrite_rules', true, 60);
        }
        return $slug;
    }
    
    public function sanitize_posts_per_page($input) {
        $value = absint($input);
        if ($value < 1) {
            add_settings_error('modified_posts_feed_messages', 'invalid_posts_per_page', 'Posts per page must be at least 1. Using default.', 'error');
            return 50;
        }
        if ($value > 500) {
            add_settings_error('modified_posts_feed_messages', 'large_posts_per_page', 'Posts per page set to ' . $value . '. Large values may impact performance.', 'warning');
        }
        return $value;
    }
    
    public function sanitize_post_types($input) {
        if (!is_array($input) || empty($input)) {
            add_settings_error('modified_posts_feed_messages', 'invalid_post_types', 'At least one post type must be selected. Using default.', 'error');
            return array('post');
        }
        
        $valid_post_types = get_post_types(array('public' => true), 'names');
        $sanitized = array();
        
        foreach ($input as $post_type) {
            if (in_array($post_type, $valid_post_types)) {
                $sanitized[] = $post_type;
            }
        }
        
        if (empty($sanitized)) {
            add_settings_error('modified_posts_feed_messages', 'no_valid_post_types', 'No valid post types selected. Using default.', 'error');
            return array('post');
        }
        
        return $sanitized;
    }
    
    public function sanitize_cache_duration($input) {
        $value = absint($input);
        if ($value < 60) {
            add_settings_error('modified_posts_feed_messages', 'cache_too_short', 'Cache duration must be at least 60 seconds. Using 60.', 'error');
            return 60;
        }
        if ($value > 86400) {
            add_settings_error('modified_posts_feed_messages', 'cache_too_long', 'Cache duration is very long (over 24 hours). This may show stale content.', 'warning');
        }
        return $value;
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Load settings for display
        $this->load_settings();
        
        $index_exists = false;
        $index_action_performed = false;
        
        // Handle manual actions
        if (isset($_POST['clear_cache']) && 
            check_admin_referer('clear_cache', 'modified_posts_feed_clear_cache_nonce')) {
            $this->clear_cache();
            add_settings_error('modified_posts_feed_messages', 'cache_cleared', 'Cache cleared successfully!', 'success');
        }
        
        if (isset($_POST['add_index']) && 
            check_admin_referer('add_index', 'modified_posts_feed_add_index_nonce')) {
            modified_posts_feed_add_index();
            add_settings_error('modified_posts_feed_messages', 'index_added', 'Database index added successfully!', 'success');
            $index_exists = true;
            $index_action_performed = true;
        }
        
        if (isset($_POST['remove_index']) && 
            check_admin_referer('remove_index', 'modified_posts_feed_remove_index_nonce')) {
            modified_posts_feed_remove_index();
            add_settings_error('modified_posts_feed_messages', 'index_removed', 'Database index removed.', 'success');
            $index_exists = false;
            $index_action_performed = true;
        }
        
        // Check index status only if not already determined by actions above
        if (!$index_action_performed) {
            global $wpdb;
            $index_exists = (bool) $wpdb->get_var(
                "SHOW INDEX FROM {$wpdb->posts} WHERE Key_name = 'post_modified'"
            );
        }
        
        $feed_url = home_url('/feed/' . $this->feed_slug);
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <strong>Your feed URL:</strong> 
                    <a href="<?php echo esc_url($feed_url); ?>" target="_blank"><?php echo esc_html($feed_url); ?></a>
                    <button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo esc_js($feed_url); ?>'); this.innerText='Copied!';" style="margin-left: 10px;">Copy URL</button>
                </p>
                <p>
                    <strong>Database Index Status:</strong> 
                    <?php if ($index_exists) : ?>
                        <span style="color: green;">✓ Index exists (queries are optimized)</span>
                    <?php else : ?>
                        <span style="color: orange;">⚠ Index does not exist (queries may be slower)</span>
                    <?php endif; ?>
                </p>
            </div>
            
            <?php settings_errors('modified_posts_feed_messages'); ?>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('modified_posts_feed_settings');
                do_settings_sections('modified-posts-feed');
                submit_button('Save Settings');
                ?>
            </form>
            
            <h2>Manual Actions</h2>
            
            <div class="card" style="max-width: 800px;">
                <h3>Clear Feed Cache</h3>
                <p>Force regeneration of the feed with fresh content.</p>
                <form method="post" action="" style="display: inline;">
                    <?php wp_nonce_field('clear_cache', 'modified_posts_feed_clear_cache_nonce'); ?>
                    <button type="submit" name="clear_cache" class="button button-secondary">
                        Clear Cache Now
                    </button>
                </form>
            </div>
            
            <?php if (!$index_exists) : ?>
            <div class="card" style="max-width: 800px;">
                <h3>Add Database Index</h3>
                <p>Add the database index immediately to improve feed performance.</p>
                <form method="post" action="" style="display: inline;">
                    <?php wp_nonce_field('add_index', 'modified_posts_feed_add_index_nonce'); ?>
                    <button type="submit" name="add_index" class="button button-primary">
                        Add Database Index Now
                    </button>
                </form>
            </div>
            <?php else : ?>
            <div class="card" style="max-width: 800px;">
                <h3>Remove Database Index</h3>
                <p><strong>Warning:</strong> Removing the index will make feed queries slower. Only do this if you have a specific reason.</p>
                <form method="post" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove the database index? This will make queries slower.');">
                    <?php wp_nonce_field('remove_index', 'modified_posts_feed_remove_index_nonce'); ?>
                    <button type="submit" name="remove_index" class="button button-secondary">
                        Remove Database Index
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Section info callbacks
     */
    public function render_feed_section_info() {
        echo '<p>Configure the basic feed settings and content options.</p>';
    }
    
    public function render_cache_section_info() {
        echo '<p>Control how the feed is cached to improve performance. Cache is automatically cleared when settings change.</p>';
    }
    
    public function render_index_section_info() {
        echo '<p>Control database index behavior. The index on the <code>post_modified</code> column significantly improves feed performance.</p>';
    }
    
    /**
     * Field rendering callbacks
     */
    public function render_slug_field() {
        $value = get_option('modified_posts_feed_slug', 'modified-posts');
        ?>
        <input type="text" name="modified_posts_feed_slug" value="<?php echo esc_attr($value); ?>" class="regular-text" pattern="[a-z0-9-]+">
        <p class="description">
            The URL slug for your feed. Your feed will be available at: <code><?php echo esc_html(home_url('/feed/')); ?><strong><?php echo esc_html($value); ?></strong></code>
            <br><em>Note: Only lowercase letters, numbers, and hyphens allowed. Rewrite rules will flush automatically.</em>
        </p>
        <?php
    }
    
    public function render_posts_per_page_field() {
        $value = get_option('modified_posts_feed_posts_per_page', 50);
        ?>
        <input type="number" name="modified_posts_feed_posts_per_page" value="<?php echo esc_attr($value); ?>" min="1" max="500" class="small-text">
        <p class="description">Number of posts to include in the feed (1-500). Recommended: 50-100.</p>
        <?php
    }
    
    public function render_post_types_field() {
        $selected_types = get_option('modified_posts_feed_post_types', array('post'));
        $post_types = get_post_types(array('public' => true), 'objects');
        
        echo '<fieldset>';
        foreach ($post_types as $post_type) {
            $checked = in_array($post_type->name, $selected_types) ? 'checked' : '';
            ?>
            <label style="display: block; margin-bottom: 5px;">
                <input type="checkbox" name="modified_posts_feed_post_types[]" value="<?php echo esc_attr($post_type->name); ?>" <?php echo $checked; ?>>
                <?php echo esc_html($post_type->labels->name); ?> (<?php echo esc_html($post_type->name); ?>)
            </label>
            <?php
        }
        echo '</fieldset>';
        ?>
        <p class="description">Select which post types to include in the feed.</p>
        <?php
    }
    
    public function render_show_images_field() {
        $value = get_option('modified_posts_feed_show_images', true);
        ?>
        <label>
            <input type="checkbox" name="modified_posts_feed_show_images" value="1" <?php checked($value, true); ?>>
            Include featured images in the feed (Media RSS format)
        </label>
        <p class="description">Recommended: Most feed readers display featured images nicely.</p>
        <?php
    }
    
    public function render_cache_enabled_field() {
        $value = get_option('modified_posts_feed_cache_enabled', true);
        ?>
        <label>
            <input type="checkbox" name="modified_posts_feed_cache_enabled" value="1" <?php checked($value, true); ?>>
            Enable feed caching
        </label>
        <p class="description">Recommended: Significantly improves performance by storing generated feed.</p>
        <?php
    }
    
    public function render_cache_duration_field() {
        $value = get_option('modified_posts_feed_cache_duration', 900);
        $minutes = round($value / 60);
        ?>
        <input type="number" name="modified_posts_feed_cache_duration" value="<?php echo esc_attr($value); ?>" min="60" max="86400" class="small-text"> seconds
        <span class="description">(<?php echo esc_html($minutes); ?> minutes)</span>
        <p class="description">
            How long to cache the feed. Common values:
            <button type="button" class="button button-small" onclick="document.querySelector('[name=modified_posts_feed_cache_duration]').value=300">5 min</button>
            <button type="button" class="button button-small" onclick="document.querySelector('[name=modified_posts_feed_cache_duration]').value=900">15 min</button>
            <button type="button" class="button button-small" onclick="document.querySelector('[name=modified_posts_feed_cache_duration]').value=1800">30 min</button>
            <button type="button" class="button button-small" onclick="document.querySelector('[name=modified_posts_feed_cache_duration]').value=3600">1 hour</button>
        </p>
        <?php
    }
    
    public function render_add_index_field() {
        $value = get_option('modified_posts_feed_add_index', true);
        ?>
        <label>
            <input type="checkbox" name="modified_posts_feed_add_index" value="1" <?php checked($value, true); ?>>
            Automatically add database index when plugin is activated
        </label>
        <p class="description">Recommended: Improves query performance significantly.</p>
        <?php
    }
    
    public function render_remove_deactivate_field() {
        $value = get_option('modified_posts_feed_remove_index_on_deactivate', false);
        ?>
        <label>
            <input type="checkbox" name="modified_posts_feed_remove_index_on_deactivate" value="1" <?php checked($value, true); ?>>
            Remove database index when plugin is deactivated
        </label>
        <p class="description">Not recommended: The index benefits all modified-date queries, not just this plugin.</p>
        <?php
    }
    
    public function render_remove_uninstall_field() {
        $value = get_option('modified_posts_feed_remove_index_on_uninstall', false);
        ?>
        <label>
            <input type="checkbox" name="modified_posts_feed_remove_index_on_uninstall" value="1" <?php checked($value, true); ?>>
            Remove database index when plugin is deleted (uninstalled)
        </label>
        <p class="description">Optional: Only enable if you want complete cleanup when deleting the plugin.</p>
        <?php
    }
}

// Initialize the plugin
new Modified_Posts_Feed();

/**
 * Flush rewrite rules and clear cache on plugin activation
 */
function modified_posts_feed_activate() {
    // Verify user has permission to activate plugins
    if (!current_user_can('activate_plugins')) {
        return;
    }
    
    // Set flag to flush rewrite rules on next admin page load
    set_transient('modified_posts_feed_flush_rewrite_rules', true, 60);
    
    // Clear cache for all sites in network
    if (is_multisite()) {
        $sites = get_sites(array('number' => 10000));
        foreach ($sites as $site) {
            delete_transient('modified_posts_feed_output_' . $site->blog_id);
        }
    } else {
        delete_transient('modified_posts_feed_output_' . get_current_blog_id());
    }
    
    // Check option - defaults to true (add index for better performance)
    $add_index = get_option('modified_posts_feed_add_index', true);
    
    if ($add_index) {
        modified_posts_feed_add_index();
    }
}
register_activation_hook(__FILE__, 'modified_posts_feed_activate');

/**
 * Flush rewrite rules and clear cache on plugin deactivation
 */
function modified_posts_feed_deactivate() {
    // Verify user has permission to deactivate plugins
    if (!current_user_can('activate_plugins')) {
        return;
    }
    
    flush_rewrite_rules();
    
    // Clear cache for all sites in network
    if (is_multisite()) {
        $sites = get_sites(array('number' => 10000));
        foreach ($sites as $site) {
            delete_transient('modified_posts_feed_output_' . $site->blog_id);
        }
    } else {
        delete_transient('modified_posts_feed_output_' . get_current_blog_id());
    }
    
    // Check option - defaults to false (keep index by default)
    $remove_index = get_option('modified_posts_feed_remove_index_on_deactivate', false);
    
    if ($remove_index) {
        modified_posts_feed_remove_index();
    }
}
register_deactivation_hook(__FILE__, 'modified_posts_feed_deactivate');

/**
 * Clean up on plugin uninstall (only runs on deletion, not deactivation)
 */
function modified_posts_feed_uninstall() {
    // Verify user has permission to delete plugins
    if (!current_user_can('delete_plugins')) {
        return;
    }
    
    // Clean up transients for all sites
    if (is_multisite()) {
        $sites = get_sites(array('number' => 10000));
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            
            // Check option BEFORE deleting it
            $remove_index = get_option('modified_posts_feed_remove_index_on_uninstall', false);
            
            delete_transient('modified_posts_feed_output_' . $site->blog_id);
            delete_transient('modified_posts_feed_flush_rewrite_rules');
            delete_option('modified_posts_feed_slug');
            delete_option('modified_posts_feed_posts_per_page');
            delete_option('modified_posts_feed_post_types');
            delete_option('modified_posts_feed_cache_enabled');
            delete_option('modified_posts_feed_cache_duration');
            delete_option('modified_posts_feed_show_images');
            delete_option('modified_posts_feed_add_index');
            delete_option('modified_posts_feed_remove_index_on_deactivate');
            delete_option('modified_posts_feed_remove_index_on_uninstall');
            
            // Remove index if option was set
            if ($remove_index) {
                modified_posts_feed_remove_index();
            }
            
            restore_current_blog();
        }
    } else {
        // Check option BEFORE deleting it
        $remove_index = get_option('modified_posts_feed_remove_index_on_uninstall', false);
        
        // Clean up transients
        delete_transient('modified_posts_feed_output_' . get_current_blog_id());
        delete_transient('modified_posts_feed_flush_rewrite_rules');
        
        // Clean up all options
        delete_option('modified_posts_feed_slug');
        delete_option('modified_posts_feed_posts_per_page');
        delete_option('modified_posts_feed_post_types');
        delete_option('modified_posts_feed_cache_enabled');
        delete_option('modified_posts_feed_cache_duration');
        delete_option('modified_posts_feed_show_images');
        delete_option('modified_posts_feed_add_index');
        delete_option('modified_posts_feed_remove_index_on_deactivate');
        delete_option('modified_posts_feed_remove_index_on_uninstall');
        
        // Remove index if option was set
        if ($remove_index) {
            modified_posts_feed_remove_index();
        }
    }
}
register_uninstall_hook(__FILE__, 'modified_posts_feed_uninstall');

/**
 * Add database index for better performance
 * NOTE: This makes a permanent change to your database
 */
function modified_posts_feed_add_index() {
    global $wpdb;
    
    // Check if index exists
    $index_exists = $wpdb->get_var(
        "SHOW INDEX FROM {$wpdb->posts} WHERE Key_name = 'post_modified'"
    );
    
    if (!$index_exists) {
        $result = $wpdb->query(
            "ALTER TABLE {$wpdb->posts} ADD INDEX post_modified (post_modified)"
        );
        
        // Log if there was an error
        if ($result === false && $wpdb->last_error) {
            error_log('Modified Posts Feed: Could not add database index - ' . $wpdb->last_error);
        }
    }
}

/**
 * Remove database index
 */
function modified_posts_feed_remove_index() {
    global $wpdb;
    
    // Check if index exists
    $index_exists = $wpdb->get_var(
        "SHOW INDEX FROM {$wpdb->posts} WHERE Key_name = 'post_modified'"
    );
    
    if ($index_exists) {
        $wpdb->query(
            "ALTER TABLE {$wpdb->posts} DROP INDEX post_modified"
        );
    }
}
