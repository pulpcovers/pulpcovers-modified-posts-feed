<?php
/**
 * Uninstall Pulpcovers Modified Posts Feed
 *
 * Removes all plugin data when the plugin is deleted.
 *
 * @package Pulpcovers_Modified_Posts_Feed
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Remove database index if it exists
 */
function pulpcovers_mpf_remove_index() {
    global $wpdb;
    
    $table = $wpdb->posts;
    
    // Check if index exists before dropping
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
    $index_exists = $wpdb->get_results(
        $wpdb->prepare(
            "SHOW INDEX FROM %i WHERE Key_name = %s",
            $table,
            'modified_posts_feed_idx'
        )
    );
    
    if ( ! empty( $index_exists ) ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query(
            $wpdb->prepare(
                "ALTER TABLE %i DROP INDEX modified_posts_feed_idx",
                $table
            )
        );
    }
}

/**
 * Remove all plugin options
 */
function pulpcovers_mpf_remove_options() {
    delete_option( 'modified_posts_feed_slug' );
    delete_option( 'modified_posts_feed_limit' );
    delete_option( 'modified_posts_feed_post_types' );
    delete_option( 'modified_posts_feed_cache_enabled' );
    delete_option( 'modified_posts_feed_featured_image' );
    delete_option( 'modified_posts_feed_index_enabled' );
}

/**
 * Remove all plugin transients
 */
function pulpcovers_mpf_remove_transients() {
    delete_transient( 'modified_posts_feed_cache' );
}

/**
 * Remove feed rewrite rules
 */
function pulpcovers_mpf_flush_rewrite_rules() {
    flush_rewrite_rules();
}

// Execute cleanup
pulpcovers_mpf_remove_index();
pulpcovers_mpf_remove_options();
pulpcovers_mpf_remove_transients();
pulpcovers_mpf_flush_rewrite_rules();
