=== Integration for Barion payment gateway and Fluent Forms ===
Contributors: oaron
Tags: fluent forms, barion, payment gateway, credit card payment
Requires at least: 5.0
Tested up to: 6.6.2
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enable credit card payments through Barion on Fluent Forms (https://fluentforms.com/?ref=2142) payment forms.

== Description ==

This plugin allows your customers to pay via [Barion Smart Gateway](https://www.barion.com/) with Fluent  Forms.

Fluent Forms (https://fluentforms.com/?ref=2142) is a powerful WordPress form builder plugin designed to create custom forms easily with a drag-and-drop interface. It offers advanced functionalities like conditional logic, multi-step forms, and integrations with popular payment gateways, CRMs, and email marketing tools. Known for its user-friendly design and versatility, Fluent Forms is suitable for a wide range of applications, from simple contact forms to complex, data-driven forms.

The plugin itself is free, but it requires the Fluent Forms Pro version to work. If you'd like to support the development of my plugin, I'd appreciate it if you purchase through the affiliate link above.
== Features ==
- Barion  payment for simple products;
- Multilingual support;

= Feedback =

I'd be happy to hear your feedback! Feel free to contact me at ugyfelszolgalat@bitron.hu

= Contribution =

You're welcome to contribute to this open source plugin by creating pull-requests on [Github](https://github.com/oaron/Barion-Payment-for-Fluent-Forms). To do this, you need to fork the repository, implement the changes and push them to your fork. After that you can create a pull request to merge changes from your fork the main repository.

= Bugs =

[Please report bugs as Github issues.](https://github.com/oaron/Barion-Payment-for-Fluent-Forms/issues), or send me an email to ugyfelszolgalat (at) bitron (dot) hu.


== Third-party Services ==

This plugin integrates with the **Barion Payment Gateway**, a third-party service provided by Barion Payment Inc. It relies on the Barion API to process credit card transactions and the **Barion Pixel** for tracking user interactions to help prevent fraud.

=== Data Transmitted to Barion: ===

- **Payment Information**: Users' payment details (e.g., credit card data) are securely sent to Barion during transaction processing.
- **Tracking Information**: The Barion Pixel is implemented to track user behavior on your website, such as page views, which are transmitted to Barion to help detect fraudulent activity.

=== Barion Pixel Usage: ===

The **Base Barion Pixel** collects rudimentary data (like page views) to support fraud prevention. This pixel is necessary for using the Barion Payment Gateway. No personally identifiable information is used for marketing purposes unless explicit user consent is provided. The data is transmitted securely to Barion's servers via TLS encryption.

**Service URL**: https://www.barion.com  
**Privacy Policy**: https://www.barion.com/en/privacy-notice/  
**Terms of Service**: https://www.barion.com/en/general-terms/  

By using this plugin, you agree to Barion’s privacy policy and terms of service. The Barion Pixel script (`bp.js`) is loaded remotely from the following URL:  
https://pixel.barion.com/bp.js.

== External Libraries ==

This plugin includes the **Barion PHP SDK (https://github.com/barion/barion-web-php)**, which is located in the `/lib/Barion/` directory. The SDK uses external dependencies such as **CURL** for making API requests. While the plugin itself uses the WordPress HTTP API for compatibility and performance, the Barion SDK may internally rely on **CURL**.

== Installation ==

= Minimum Requirements =

* Fluent forms free and pro latest version;
* WordPress 4.0 or later
1. The recommended way to install the plugin is through the "Plugins" menu in WordPress
  - Navigate to Plugins > Add New > Search for "barion", you should already see this plugin
  - Hit "Install Now", then enable the plugin
  - Navigate to **Forms > Settings**.
- Once loaded, select the **Payments** tab.
- Under **Payment Methods**, you can enable the Barion payment option. Here, you can enter your test and live payment credentials.
- For each form, you can enable the Barion payment method individually.
