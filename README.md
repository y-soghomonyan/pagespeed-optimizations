# Custom Performance Optimization Plugin for WordPress

This plugin is a custom solution designed to enhance the performance of WordPress websites by addressing specific areas not fully covered by existing optimization plugins. It implements several key features to improve page load times and reduce server load.

## Features

* **Redis Cache:** Leverages Redis for object caching, significantly speeding up database interactions and reducing server load. This provides direct control over Redis integration, which may not be a standard feature in all caching plugins.
* **Lazy Loading:** Implements comprehensive lazy loading for standard `<img>` tags, background images (CSS `background-image`), and `<iframe>` elements. This includes specific targeting of background images.
* **Delayed JavaScript and CSS:** Allows for delaying the loading of specific JavaScript (`.js`) and CSS (`.css`) files. This can be configured globally through the plugin settings or with granular control on a per-page/post basis, offering flexibility in managing asset loading.
* **Image Preloading:** Enables the preloading of critical images that are essential for the initial rendering of a page. This can be configured globally via the plugin settings or specifically for individual pages and posts to prioritize important visual elements.

## Installation

1.  Download the plugin ZIP file (if applicable).
2.  In your WordPress admin dashboard, navigate to **Plugins** > **Add New**.
3.  Click **Upload Plugin** at the top of the page.
4.  Click **Choose File**, select the plugin ZIP file, and click **Install Now**.
5.  Once the plugin is installed, click **Activate Plugin**.

## Configuration

After activating the plugin, a new settings section (e.g., "PageSpeed Settings" or a similar name) will be available in your WordPress admin dashboard. Navigate to this section to configure the plugin's features:

* **Redis Cache:**
    * Options to enable/disable Redis caching.
    * Settings for Redis server address and port.
    * Note: Redis server needs to be running and configured on your hosting environment for this feature to be operational.
* **Image Preloading:**
    * A section to input the URLs of important images to be preloaded globally.
    * Potentially options within the page/post editor to specify images for preloading on individual content.
* **Lazy Loading:**
    * Enable/disable lazy loading for images, background images, and iframes.
    * The image urls added in preload settings will be automatically excluded from lazy loading.
* **Delayed JavaScript and CSS:**
    * A section to input the handles or file paths of JavaScript and CSS files to be delayed.
    * Options for global delays and potentially per-page/post specific configurations.


## Usage

Once configured, the plugin will automatically apply the specified optimizations to your website.

* **Lazy Loading:** Images, background images, and iframes matching the configured settings will only load when they are about to enter the viewport.
* **Delayed JavaScript and CSS:** The specified JavaScript and CSS files will be loaded after a defined delay or a specific event, improving initial page load time.
* **Image Preloading:** The designated important images will be loaded with high priority to ensure they are available early in the rendering process.
* **Redis Cache:** If Redis is enabled and configured correctly on the server, the plugin will cache database queries and objects in Redis, reducing database load and improving response times.

## Important Notes

* **Redis Configuration:** The Redis Cache feature requires a running and correctly configured Redis server on your hosting environment. Please ensure Redis is enabled by your hosting provider.
* **Plugin Conflicts:** While this plugin is designed to work alongside other optimization efforts, conflicts with other caching or performance plugins may occur. It is recommended to test thoroughly after activation and configuration.


## Support

For any issues or questions regarding this plugin, please refer to the relevant documentation or contact the development team.
