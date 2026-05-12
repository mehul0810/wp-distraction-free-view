=== WP Distraction Free View ===
Contributors: mehul0810
Tags: distraction free, reading mode, fullscreen, accessibility, reader
Donate link: https://buymeacoffee.com/mehulgohil
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 2.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This WordPress plugin provides a distraction free reader view for posts, pages, and public custom post types.

== Description ==
**WP Distraction Free View** adds a focused reader mode for WordPress content. Visitors can open content in a clean modal, switch to fullscreen, and print the readable view.

The settings screen uses WordPress-native components and stores settings in the existing plugin option so upgrades preserve current configuration.

= Features =

1. Fullscreen reader mode
2. Print Support
3. WordPress Design System based settings UI
4. Public post type controls
5. Reader Button block for single content and Query Loop templates
6. Block-based modal templates for full site editing themes
7. Shortcode `[wpdfv]` support

= Benefits =
1. Engage your site visitors
2. Give readers a focused view of long-form content
3. Reduce surrounding theme and widget distractions while viewing selected content.

= Connect with WP Distraction Free View - WordPress Plugin =

Stay in touch with us for important plugin news and updates:

* **[GitHub](https://github.com/mehul0810/wp-distraction-free-view "Visit the development of WP Distraction Free View")**

= Contribute to WP Distraction Free View  - WordPress Plugin =

This plugin is proudly open source (GPL license) and we're always looking for more contributors. Whether you know another language, can code like no one's business, or just have an idea, we would love your help and input.

Here's a few ways you can contribute to the plugin:

* Star/fork/watch the [GitHub repository](https://github.com/mehul0810/wp-distraction-free-view "Visit the GitHub Repository") to learn more about what issues we're tackling and the project is developing. If you've never worked with Github before, learn about [pull requests here](https://help.github.com/articles/about-pull-requests/) and submit one for WP Distraction Free View, we'd love to provide you our feedback.

* Translate "WP Distraction Free View" into your native language. The best place to do that is here on wordpress.org. Go to [https://translate.wordpress.org/](https://translate.wordpress.org/projects/wp-plugins/wp-distraction-free-view), then search for your language, click the "Plugins" tab, then search for "WP Distraction Free View". When you've submitted at least 95% of "WP Distraction Free View" plugin strings, the language moderators will review and approve your translations and then they will be available to all WordPress users for your native language.


== Installation ==
Please follow below instructions to install this plugin manually:

1. Upload "wp-distraction-free-view" to the "wp-content/plugins/" directory.

2. Activate the plugin through the "Plugins" menu in WordPress.

3. Change the settings from "Settings > Distraction Free Mode" submenu in WordPress Admin Panel.

== Frequently Asked Questions ==

= Do the plugin support Print feature? =

Yes. The reader modal includes a print action.

= What is dual fullscreen mode? =

With Dual Fullscreen mode, user can view the posts in popup mode (i.e. browser's viewport) and then again clicking on fullscreen button will hide browser and display whole article occupying your system screen.

= Can I place the reader button manually? =

Yes. New installs keep automatic insertion disabled by default. Add the Reader Button block in single templates, posts, pages, custom post types, or Query Loop templates. Existing installs keep their previous automatic insertion behavior after upgrade.

= Can block themes customize the modal layout? =

Yes. The plugin registers a default block-based modal template and a WP Distraction Free View pattern category. Themes and site-specific code can add more templates with the `wpdfv_modal_templates` filter.

== Screenshots ==
1. Refreshed Admin Settings
2. Read Mode button on posts listing
3. Refreshed Fullscreen mode

== Changelog ==

= 2.1.0 =
- Added: Reader Button block for posts, pages, public custom post types, and Query Loop templates
- Added: Block-based modal templates with a default template and pattern category for FSE themes
- Added: Setting to disable automatic reader button insertion, disabled by default for new installs
- Changed: Existing installs keep automatic reader button insertion enabled during upgrade unless they had disabled it
- Changed: Reader modal now opens as a full-width viewport modal

= 2.0.0 =
- Changed: Minimum requirements are now WordPress 6.0 and PHP 8.2
- Changed: Rebuilt admin settings with WordPress components and REST API
- Changed: Rebuilt distraction free view modal with WordPress components
- Changed: Modernized the asset build around @wordpress/scripts
- Fixed: Shortcode rendering no longer calls a removed helper function
- Fixed: Runtime autoloading no longer depends on Composer vendor files

= 1.6.0: 16th May 2021 =
- Refactor: Improved UX for the admin settings UI
- Added: Support for display on post types

= 1.5.0: 10th March 2021 =
- Improvement: Refactor code to use PHP namespaces
- Fix: Resolve issues related to assets not loading
- Feature: Add settings and support links on plugins list

= 1.4.5: 26th November 2019 =
- Ensure that "Read Mode" button text can be changed from settings

= 1.4.4: 23rd November 2019 =
- Updated CSS for the "Read Mode" button to have cursor pointer.

= 1.4.3: 19th November 2019 =
- Included Automated Development Process
- Fix for Fullscreen and Print icon

= 1.4.2: 19th November 2019 =
- Shortcode renamed from `[dfview]` to `[wpdfv]`
- Fix: refactor plugin for stability [#4](https://github.com/mehul0810/wp-distraction-free-view/issues/4)
- Feat: update code to support latest WP 5.3 [#1](https://github.com/mehul0810/wp-distraction-free-view/issues/1)
- Security Improvements
- UI Improvements
- Stability Improvements
- Removed Font Awesome support

= 1.4.0 =
- Removed Genericons Support
- Added Font Awesome Support using CDN

= 1.3.0 =
- Improved Print Support
- Improved Fullscreen support
- Powerful Admin Settings Panel Support Added
- Light weight Adjustments
- Added Flexibility
- Improved User Experience

= 1.2.0 =
- Fixed overlay issues on mostly all the themes

= 1.1.0 =
- Fixed few alignment and validation errors
- Fixed popup inconsistency
- Added Print Support

= 1.0.0: Initial Release =
- Ability to display button at specified location in a post.
- [dfview] Shortcode supported.
- Ability to change "DF View" button text.

== Upgrade Notice ==
Please take backup of all the files of the plugin and whole database before upgrading the plugin to be at the safer side using the best practices.
