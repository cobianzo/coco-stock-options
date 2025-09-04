=== Coco Visual Transition ===
Contributors: @cobianzo
Tags: frets, container transitions, visual effects, decorative patterns, seamless transitions
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 1.0.4
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Scanner of options, providing an endpoint and an app to track and analyze Bull Spread PUTs

== Description ==

This plugin defines Custom Post Types (CPT) for tracking required stocks. It scans for future options values for each stock from the CBOE API (`https://cdn.cboe.com/api/global/delayed_quotes/options/<stock-symbol>.json`) via a cron job. The results are formatted and saved as post meta, then exposed through a custom REST API endpoint.


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/visual-transition` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Start using the new fret pattern controls in your Group blocks

== Frequently Asked Questions ==

= How do I add a fret pattern to my Group block? =

Simply select any Group block in the editor and look for the Visual Transition controls in the block sidebar. Choose your desired pattern and customize its appearance.

= Does this work with any theme? =

Yes, Visual Transition works with any theme that supports the WordPress core Group block.

== Changelog ==

= 2.0.0 =
* Initial public release
* Added fret pattern controls to Group blocks
* Implemented container clipping functionality
* Added multiple pattern options

== Upgrade Notice ==

= 2.0.0 =
First public release with core Group block extensions and pattern options.
