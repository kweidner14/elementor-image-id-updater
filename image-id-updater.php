<?php
/*
Plugin Name: Elementor Image ID Updater
Description: A plugin to update Elementor image IDs in metadata with a Dry Run option and specific page targeting.
Version: 1.0
Author: Kyle Weidner
*/

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register admin page
function eiiu_add_admin_page() {
    add_menu_page(
        'Elementor Image ID Updater',
        'Image ID Updater',
        'manage_options',
        'eiiu-updater',
        'eiiu_admin_page',
        'dashicons-update',
        20
    );
}
add_action( 'admin_menu', 'eiiu_add_admin_page' );

// Admin page content
function eiiu_admin_page() {
    $dry_run = isset( $_POST['eiiu_dry_run'] ) ? true : false;
    $page_id = isset( $_POST['eiiu_page_id'] ) ? intval( $_POST['eiiu_page_id'] ) : 0;
    $batch_size = isset( $_POST['eiiu_batch_size'] ) ? intval( $_POST['eiiu_batch_size'] ) : 50; // Default batch size
    $process_all = isset( $_POST['eiiu_process_all'] ) ? true : false;

    ?>
    <div class="wrap">
        <h1>Elementor Image ID Updater</h1>
        <p>Choose to perform a dry run to preview changes, or run the updater to apply changes to all pages.</p>
        <form method="post">
            <input type="hidden" name="eiiu_run_script" value="1">
            <label>
                <input type="checkbox" name="eiiu_dry_run" value="1" <?php checked( $dry_run ); ?> />
                Dry Run (Preview Changes Only)
            </label>
            <br><br>
            <label for="eiiu_page_id">Page ID to Update (Leave blank to process all pages):</label>
            <input type="number" name="eiiu_page_id" value="<?php echo esc_attr( $page_id ); ?>" min="1" />
            <br><br>
            <label for="eiiu_batch_size">Batch Size (for processing all pages):</label>
            <input type="number" name="eiiu_batch_size" value="<?php echo esc_attr( $batch_size ); ?>" min="1" />
            <br><br>
            <label>
                <input type="checkbox" name="eiiu_process_all" value="1" <?php checked( $process_all ); ?> />
                Process All Pages
            </label>
            <br><br>
            <?php submit_button( 'Run Image ID Updater' ); ?>
        </form>
        <div id="eiiu-output" style="margin-top: 20px;">
            <h2>Output:</h2>
            <div>
                <?php
                // Handle form submission and display output here
                if ( isset( $_POST['eiiu_run_script'] ) && $_POST['eiiu_run_script'] == '1' ) {
                    $dry_run = isset( $_POST['eiiu_dry_run'] ) ? true : false;
                    $page_id = isset( $_POST['eiiu_page_id'] ) ? intval( $_POST['eiiu_page_id'] ) : 0;
                    $batch_size = isset( $_POST['eiiu_batch_size'] ) ? intval( $_POST['eiiu_batch_size'] ) : 50;
                    $process_all = isset( $_POST['eiiu_process_all'] ) ? true : false;

                    if ( $process_all ) {
                        // Run for all pages in batches
                        eiiu_run_script_for_all_pages_in_batches( $dry_run, $batch_size );
                    } else {
                        // Run for a specific page ID
                        if ( $page_id > 0 ) {
                            eiiu_run_script( $dry_run, $page_id );
                        } else {
                            echo '<div class="notice notice-error"><p>Please enter a valid Page ID, or select "Process All Pages".</p></div>';
                        }
                    }
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

// Custom function to traverse and update `background_image` structures
function traverse_and_update_all_pages( &$data, $dry_run, &$updated ) {
    global $wpdb;

    if ( is_array( $data ) ) {
        foreach ( $data as $key => &$value ) {
            // Check for specific image keys
            if ( in_array($key, ['background_image', 'background_overlay_image', 'background_overlay_image_tablet', 'background_overlay_image_mobile']) &&
                is_array( $value ) && isset( $value['url'], $value['id'] ) && is_numeric( $value['id'] ) ) {
                $old_id = (int) $value['id'];
                $image_url = $value['url'];

                // Find the new ID for the given URL
                $new_id = $wpdb->get_var( $wpdb->prepare("
                    SELECT ID FROM $wpdb->posts 
                    WHERE guid = %s 
                      AND post_type = 'attachment'
                ", $image_url ) );

                if ( $new_id && $new_id != $old_id ) {
                    echo "<p>Dry Run - Page ID: {$GLOBALS['current_page_id']}, Old ID: {$old_id}, New ID: {$new_id}, URL: {$image_url}</p>";
                    if ( ! $dry_run ) {
                        // Replace the old ID with the new ID
                        $value['id'] = (int) $new_id;
                    }
                    $updated = true;
                } else {
                    echo "<p>No new ID found for Old ID: {$old_id}, URL: {$image_url}</p>";
                }
            } elseif ( is_array( $value ) ) {
                // Recursively traverse nested arrays
                traverse_and_update_all_pages( $value, $dry_run, $updated );
            }
        }
    }
}

// Batch processing for all pages
function eiiu_run_script_for_all_pages_in_batches( $dry_run, $batch_size = 50 ) {
    global $wpdb;

    if ( $dry_run ) {
        echo '<div class="notice notice-info"><p>Dry Run Mode: No changes will be made.</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>Running the Image ID Updater for All Pages in Batches...</p></div>';
    }

    // Fetch total count of posts with `_elementor_data` meta key limited to posts and pages
    $total_pages = $wpdb->get_var("
        SELECT COUNT(pm.post_id)
        FROM $wpdb->postmeta pm
        INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
        WHERE pm.meta_key = '_elementor_data'
          AND p.post_type IN ('page', 'post')
    ");

    if ( $total_pages > 0 ) {
        $updated_count = 0;
        $processed_count = 0;
        $offset = 0;
        $batch_number = 1;

        while ( $offset < $total_pages ) {
            echo "<h3>Processing Batch {$batch_number} (Offset: {$offset}, Batch Size: {$batch_size})</h3>";
            ob_flush();
            flush();

            // Fetch a batch of posts/pages with `_elementor_data` meta key
            $elementor_pages = $wpdb->get_results( $wpdb->prepare("
                SELECT pm.post_id, pm.meta_value 
                FROM $wpdb->postmeta pm
                INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
                WHERE pm.meta_key = '_elementor_data'
                  AND p.post_type IN ('page', 'post')
                LIMIT %d OFFSET %d
            ", $batch_size, $offset ) );

            if ( $elementor_pages ) {
                foreach ( $elementor_pages as $elementor_page ) {
                    $elementor_data = json_decode( $elementor_page->meta_value, true );
                    $updated = false;

                    // Traverse the Elementor data
                    $GLOBALS['current_page_id'] = $elementor_page->post_id; // Store the current page ID for reference in output
                    traverse_and_update_all_pages( $elementor_data, $dry_run, $updated );

                    if ( $updated ) {
                        $updated_count++;
                        if ( ! $dry_run ) {
                            update_post_meta( $elementor_page->post_id, '_elementor_data', wp_slash( wp_json_encode( $elementor_data ) ) );
                            echo "<p>Updated page ID {$elementor_page->post_id} with new image IDs.</p>";
                        } else {
                            echo "<p>Dry run: Page ID {$elementor_page->post_id} would be updated.</p>";
                        }
                    } else {
                        echo "<p>No updates needed for Page ID {$elementor_page->post_id}.</p>";
                    }

                    $processed_count++;
                    ob_flush();
                    flush();
                }
            }

            // Increment offset for the next batch
            $offset += $batch_size;
            $batch_number++;

            // Output progress for completed batch
            echo "<p>Batch {$batch_number} complete. Processed {$processed_count} pages so far out of {$total_pages} total.</p>";
            ob_flush();
            flush();
        }

        echo "<p>Total Pages Processed: {$processed_count}</p>";
        echo "<p>Total Pages Updated: {$updated_count}</p>";

    } else {
        echo '<div class="notice notice-warning"><p>No Elementor pages or posts found.</p></div>';
    }

    if ( $dry_run ) {
        echo '<div class="notice notice-info"><p>Dry Run complete. No changes were made.</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>Process complete.</p></div>';
    }
    ob_flush();
    flush();
}

function eiiu_run_script( $dry_run, $page_id ) {
    global $wpdb;

    echo '<p>Starting processing for a single page...</p>';
    ob_flush();
    flush();

    // Fetch the specified page with `_elementor_data` meta key
    $elementor_page = $wpdb->get_row( $wpdb->prepare("
        SELECT post_id, meta_value FROM $wpdb->postmeta
        WHERE meta_key = '_elementor_data' AND post_id = %d
    ", $page_id ) );

    if ( $elementor_page ) {
        $elementor_data = json_decode( $elementor_page->meta_value, true );
        $updated = false;

        // Traverse and update data
        $GLOBALS['current_page_id'] = $elementor_page->post_id; // Store the current page ID for reference in output
        traverse_and_update_all_pages( $elementor_data, $dry_run, $updated );

        if ( $updated ) {
            if ( ! $dry_run ) {
                update_post_meta( $elementor_page->post_id, '_elementor_data', wp_slash( wp_json_encode( $elementor_data ) ) );
                echo "<p>Updated page ID {$elementor_page->post_id} with new image IDs.</p>";
            } else {
                echo "<p>Dry run: Page ID {$elementor_page->post_id} would be updated.</p>";
            }
        } else {
            echo "<p>No updates needed for Page ID {$elementor_page->post_id}.</p>";
        }
    } else {
        echo '<div class="notice notice-warning"><p>No Elementor data found for the specified Page ID.</p></div>';
    }

    if ( $dry_run ) {
        echo '<div class="notice notice-info"><p>Dry Run complete. No changes were made.</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>Process complete.</p></div>';
    }
    ob_flush();
    flush();
}
