=== Plugin Name ===
Contributors: Telogis
Tags: telogis, analytics, asset, tracker, ad, ads, adverts, advertising, advertisement, adserver, cta, rotator, manager, link, google, asynchronous, tracking, image, seo, links, images
Requires at least: 2.9.2
Tested up to: 3.0.0
Stable tag: 1.0.5

This plugin generates a series of images (assets) based on the current page's content, and displays them in an order of relavence.

== Description ==

The Asset Tracker plugin generates a series of images or adverts (assets) based on the current page's content, and displays them in an order of relevance. Basically like contextual advertising but using image ads as the call to action.

Use the assets to either link to other pages of interest within your own site, specific CTA's or another site (affiliate or advertiser) and since the asset clicks are integrated with Google Analytics you can monitor the success of specific ads and what pages they worked best on.

When a set of assets are generated, the html is cached and saved in the database against that page or post. This enables faster reloading of the assets for future page loads (caching).

Customizable settings include:

* Being able to select the total amount of "slots" available for your assets.
* Select how many slots any asset takes up.
* Easily modify the HTML which shows before and after the plugin's output.
* Set your assets to show on the sidebar or footer automatically, or you can disable this and call the function manually anywhere in your template.
* Enter a list of IDs of pages/posts and choose to either not show assets on these or only show assets on these.
* Integration with Google Analytics custom vars. Supports the new asynchronous Google Analytics code, or the non-asynchronous code.
* Customize the link and alt text behind every asset for good SEO practice.

== Installation ==

1. Extract `asset-tracker.zip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the settings page and choose a place to display assets, or place `<?php at_show_assets(); ?>` in your template where you want it to show.

Sample data is installed and tables are created automatically when the plugin is activated.

== Changelog ==

= 1.0.5 =
Fixed a problem with the "Display" setting always saying "Disabled" (minor fix)

= 1.0.4 =
Fixed a problem with sample data not being installed (minor fix)

= 1.0.3 =
Compacted some code, and added some brackets in places that needed them.
Changed the installation function to only insert sample data if there is not already data in the database.
Fixed some problems with double-commas.
Replaced all instances of the deprecated ereg_replace function to preg_replace.
Modified some of the core processing code to make it a little bit faster.

= 1.0.2 =
Added a call to _trackPageview after _setCustomVar. This fixes an issue where assets which link to external sites don't set the custom var properly.

= 1.0.1 =
Moved the Google Analytics code from a function into the onclick for each asset. This fixes an issue with the JavaScript not being run correctly, due to the different scope of the _gaq variable in sendToGA().

= 1.0 =
First release.

== Upgrade Notice ==

= 1.0.2 =
If your assets have links to external sites and you use the Google Analytics asynchronous JavaScript code, you should update.

= 1.0.1 =
You should upgrade to this version if you use the Google Analytics feature.