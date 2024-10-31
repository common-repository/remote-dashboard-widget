=== Remote Dashboard Widget ===
Contributors: wpdashboardwidget
Requires at least: 5.4
Tested up to: 6.2
Stable tag: 0.0.30
License: GPLv2
License URI: GPLv2
Tags: dashboard widget,contact details

Marketing widget for (remotely) displaying website maintainer or -support contact information on the WordPress dashboard

== Description ==
Remote Dashboard Widget is a marketing widget for displaying website maintainer or -support details. This plugin is intended to (remotely) communicate contact information through the WordPress admin dashboard, but is suitable for all kinds of passive communication with your customers such as promotions or happy talk-like-a-pirate-day wishes. Define the content of your widget in your account at <a href="https://wpdashboardwidget.com/">wpdashboardwidget.com</a> and display this content (HTML) in a widget in any of the client websites you maintain. If anything changes, you can remotely update the widget content, effectively updating the displayed widget information in all the connected sites.

This plugin connects to <a href="https://wpdashboardwidget.com/">wpdashboardwidget.com</a> from where it retrieves the predefined HTML from your account. This HTML is shown in the WordPress dashboard of connected sites. To create your widget content, an account is required for <a href="https://wpdashboardwidget.com/">wpdashboardwidget.com</a>. Please also check our <a href="https://wpdashboardwidget.com/privacy-policy">privacy policy</a>.

== Changelog ==
= 0.0.30 =
- Version bump

= 0.0.29 =
- Only trigger cmb2 before_form hook on plugin option page

= 0.0.28 =
- Allow data uri
- Include demo widget logo

= 0.0.27 =
- Fixes settings link

= 0.0.26 =
- Improves readme
- Fixes warnings

= 0.0.25 =
- Removes UpdateChecker
- Improves the readme

= 0.0.24.1 =
- Disables UpdateChecker
- Name Change

= 0.0.24 =
- Adds settings link

= 0.0.23 =
- Adds title color option
- Adds demo token
- Adds assets
- Fixes policies for paid features

= 0.0.22 =
- Implements uninstall hook
- Implements deactivation hook
- Rename folder structure
- Translatable strings
- Adds option to limit widget visibility to selected roles
- Adds option to skip transient cache
- Adds HTMLPurifier parsing of widget content
- Adds composer update to zip scripts

= 0.0.21 =
- Actually delete transient when options are saved

= 0.0.20 =
- Don't save transient if no token is set
- Fix using stored widgets when none are stored
- Correctly delete transient when options are saved

= 0.0.19 =
- Adds options for positioning the widget
- Adds options for disabling other dashboard widgets

= 0.0.18 =
- Adds transient caching
- Adds option for transient expiry
- Only process widget code on dashboard screen
- Upgrades update checker

= 0.0.17 =
- Upgrades composer packages
- Adds return types

= 0.0.16 =
- Updates comments
- Updates widget get request to transfer the client url
- Client URL default is now an array

= 0.0.15 =
- Version bump

= 0.0.14 =
- Fixes issues with placeholder widget
- Removes comments

= 0.0.13 =
- Adds backup color

= 0.0.12 =
- Enables calling the development environment through the token

= 0.0.11 =
- Adds admin styling
- Adds possibility for remote styling

= 0.0.10 =
- Adds remote connection to retrieve Widget content
- Adds settings page
- Refactoring
