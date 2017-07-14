=== Plugin Name ===
Contributors: baekerIT
Donate link: https://identitaetscheck-plugin.de
Tags: woocommerce, identitycheck
Requires at least: 4.6
Tested up to: 4.7.3
Stable tag: 2.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This Plugin checks the Address Data of a WooCommerce Customer. If this check fails, no Order will be created.

== Description ==

This Plugin adds a WooCommerce Checkout Field in the Front-End for the Birthdate of a Customer.

Additional to this Plugin, a Contract with the SCHUFA and our Company is needed, to use this Plugin.

SCHUFA Identity Check is made to secure up your Orders and match german online shops with the new JuSchG, changed in April 2016 (E-Commerce with Cigars and Alcohol).

When a customer submits a new order, his typed personal data (first name, last name, street, birthdate, zipcode and city) will be sent to our servers, to

compare the typed in informations with our cached records. Only if the customer has never been checked, a request will be sent to the SCHUFA in germany and cached by us.

If the Customer has not been cached on our server https://baeker-it.de, a connection to the SCHUFA Server www.port.schufa.de/siml2 will be established from our API,

to cache the Customer Data in our Database so the customer will not be checked twice.

Your Access Data will be securly saved on our server to be available at any time - the needed yearly exchange of the certificate will be done by us for free.

IMPORTANT: This Plugin is only needed for E-Commerce Shops, which are selling to german Customers and currently not supporting other Countrys except germany.

Also you will need to purchase a License from https://identitaetscheck-plugin.de to use this Plugin.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/schufa-identity-check` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the SCHUFA Identitätscheck -> Plugin Einstellungen screen to configure the plugin
4. Your Accessdata for the SCHUFA will be hosted Secure on our Servers and will be uses by our API only - additionally you'll get an Overview of every Check.

NOTE: This Plugin needs the cURL PHP Extension. (Minimum PHP 5.4)
== Frequently Asked Questions ==

= Can I run this Plugin directly after Installation? =

Yes, but only if you've got a Certificate from the SCHUFA.

Else, we will Setup the Access for you.

= Will Customers be double checked? =

No - we provide a Cache System, so every Customer will be checked only one time.

== Screenshots ==


== Changelog ==

= 2.9 =

Disabling Requests to SCHUFA and confirming the order, if Customer lives not in germany.

= 2.8 =

Bugfix in the ByPass Function

= 2.7 =

Added a Button for bypassing the Identity Check. Easily create a Customer and use the Button to set his Status as Checked

= 2.6 =

Declaration of Needed Fields changed in cause of some Fatal Errors in some cases.

= 2.5 =

Small Update for legal Reasons

= 2.4 =

Did some Cleanup workings to prevent Error Messages

= 2.1 =

Added a button in the User Profile which allows to mark Users as checked / unchecked.

= 2.0 =

Major Update:

This Version is the first, using our newly developed API

== Arbitrary section ==



== A brief Markdown Example ==

`<?php code(); // goes in backticks ?>`