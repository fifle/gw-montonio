=== Payment Gateway for Montonio on GiveWP ===
Contributors: pablothef
Donate link: https://donationbox.ee/donation?campaign_title=Buy%20me%20a%20hot%20choco.%20Support%20the%20project!&detail=Annetus+GiveWP&payee=Pavel+Flei%C5%A1er&iban=EE614204278622417401&pp=paflei&rev=pavelvtd
Tags: banklink, pangalink, estonia, latvia, lithuania, poland, finland, payment gateway, givewp, internet bank, i-bank
Requires at least: 3.0.1
Tested up to: 6.2
Stable tag: 1.0.1
License: GPLv3 or later
License URI: https://spdx.org/licenses/GPL-3.0-or-later.html

This add-on for GiveWP Donation Plugin allows to accept payments via Montonio payment gateway.

== Description ==

This add-on for GiveWP Donation Plugin allows to accept payments via Montonio payment gateway. The Montonio payment solution enables online stores to accept payments from all major banks in Estonia, Latvia, Lithuania, Finland and Poland with a single integration. This brings the capability for GiveWP donors to initiate donations from bank accounts directly, without entering credit card numbers or similar.

Integration requires owner of the fundraising website to register your organization on Montonio website. Get started on Montonio's website: https://montonio.com/payments/

Supported banks:
* Estonia: Swedbank, SEB, LHV, Coop Pank, Luminor, Citadele, Revolut
* Latvia: Swedbank, SEB, Citadele, Luminor, Revolut
* Lithuania: Swedbank, SEB, Luminor, Šiaulių bankas, Citadele, Revolut
* Finland: OP, Nordea, Danske Bank, Säästöpankki, POP Pankki, Oma Säästöpankki, S-Pankki, Ålandsbanken, Handelsbanken, Revolut
* Poland: Blik, Bank Polski, Bank Pekao, Santander, ING, mBank, BNP Paribas, Millennium Bank, Alior Bank, Credit Agricole, Inteligo, Revolut

Important notes:
* Montonio integration for credit cards through Stripe is not supported by this add-on yet.
* This plugin may not work at the same time as the Montonio for WooCommerce is enabled. We hope to fix this problem with the next plugin release!

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `gw-montonio` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Activate Montonio gateway on GiveWP Settings (Settings > Payment Gateways > check "Enabled" checkbox for Montonio gateway)

== Frequently Asked Questions ==

= How to get access and secret key? =

Both sets of keys can be acquired from the Montonio Partner System. To generate access and secret keys for your Montonio account, go to the Stores page of the partner system. Click on your store and navigate to the API Keys tab.

= How to get started with new Montonio account? =

To get an account and access the partner system, please fill in the registration form on Montonio website: https://montonio.com/#contact-form

= Why this plugin is free? Does it track any payment data too? =

Currently connecting solutions related to accepting donations is either quite difficult for a person who does not have technical skills, or has a monthly fee, which may be inappropriate in cases where the collection is organized by a private person or an NGO that does not have regular donors. GiveWP is a great tool that helps thousands of organizations to start their campaign securely and for free.
We believe it is important to make organizing fundraisers in the Baltics, Finland and Poland a quick, convenient method for fundraisers.

Payment Gateway for Montonio on GiveWP plugin is just an intermediary that sends a request to Montonio with the account number, the name of the recipient, and the amount of the payment. The bank chosen by the user is responsible for the security of the transfer and all actions related to user authentication.

= Who can I turn to for technical assistance and other questions? =

Always happy to help you set up donations with Estonian, Latvian, Lithuanian, Polish and Finnish payment systems on your site. If you have further questions, write to: pavel[at]fleisher.ee

= I'd like to support you. How can I do it? =

Feel free to donate to a developer for a cup of coffee via Donationbox.ee service: https://donationbox.ee/donation?campaign_title=Buy%20me%20a%20hot%20choco.%20Support%20the%20project!&detail=Annetus+GiveWP&payee=Pavel+Flei%C5%A1er&iban=EE614204278622417401&pp=paflei&rev=pavelvtd

== Changelog ==

= 1.0.1 =
Fixed a critical bug for those not using the Polylang plugin.

= 1.0 =
* Initial release.