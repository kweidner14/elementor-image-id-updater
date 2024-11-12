<<<<<<< HEAD
# Elementor Image ID Updater

## Description

The **Elementor Image ID Updater** plugin is a tool for WordPress websites built with Elementor that helps maintain the integrity of image references within the site's metadata. It specifically targets scenarios where image IDs associated with Elementor elements may have changed or become incorrect, such as after a database migration, a media offload to external storage, or other similar operations that can lead to broken image links or inconsistent data.

### When to Use This Plugin

- **Media Migrations**: If you've migrated media from a local server to a cloud storage service (e.g., S3) and your image IDs in Elementor elements are no longer accurate.
- **Database Transfers**: When transferring or cloning sites, there may be inconsistencies in the way Elementor image data is stored.
- **Broken Image Links**: If Elementor page elements display broken or incorrect image links due to outdated image metadata.
- **Site Maintenance**: Ideal for large-scale sites that periodically update their media libraries and need to ensure image references in Elementor metadata are correct.

### What It Does

- **Scans Elementor Data**: Traverses Elementor's `_elementor_data` meta field to locate image references.
- **Updates Image IDs**: Finds and updates outdated image IDs with the correct ones based on the image's URL.
- **Dry Run Mode**: Provides an option to preview changes without making any modifications to the database.
- **Batch Processing**: Efficiently processes all pages/posts with Elementor data in batches to reduce server load.
- **Specific Page Targeting**: Allows targeting a specific page for updates or updating all Elementor pages.

By keeping your Elementor image metadata accurate, this plugin helps ensure that images display correctly across your site, reducing potential downtime or visual inconsistencies after site changes or updates.
## Features

- **Dry Run Option**: Preview changes before making them.
- **Page Targeting**: Update images for a specific page or all pages.
- **Batch Processing**: Process pages in batches for efficient updates.
- **Flexible Configuration**: Customize batch size and processing options.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/elementor-image-id-updater` directory or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Access the plugin's settings via **Tools > Image ID Updater** in the WordPress admin menu.

## Usage

1. Go to **Tools > Image ID Updater** from the WordPress admin dashboard.
2. Configure the desired options:
    - **Dry Run (Preview Changes Only)**: Check this box to preview the changes without making any modifications.
    - **Page ID**: Specify a page ID to update (leave blank to process all pages).
    - **Batch Size**: Set the number of pages to process in each batch (applies when processing all pages).
3. Click **Run Image ID Updater** to start the process.

### Notes
- **Dry Run Mode** does not modify any data; it only displays potential changes.
- When **Process All Pages** is selected, the plugin processes pages in batches, based on the specified batch size.
- Results are displayed directly on the page, indicating pages that were processed or skipped.

## Screenshots

*No screenshots are provided in this version.*

## Frequently Asked Questions

### 1. What does "Dry Run" mean?
Dry Run mode allows you to preview the changes the plugin would make without actually applying them.

### 2. Can I target a specific page for updates?
Yes, you can specify a **Page ID** to update a single page's Elementor image IDs.

### 3. What is batch processing?
Batch processing allows you to process multiple pages at a time, which is useful for large sites with many pages.

## Changelog

### Version 1.0
- Initial release.

## Author

Kyle Weidner

## License

This plugin is licensed under the [GNU General Public License v2](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html) or later.

=======
# elementor-image-id-updater
>>>>>>> 64e1a46e0c5b332cef51c849b9cd4469161482ed
