=== The Courier Guy Shipping for WooCommerce ===
Contributors: appinlet
Tags: ecommerce, e-commerce, woocommerce, shipping, courier
Requires at least: 4.9.8
Tested up to: 5.4.2
Requires PHP: 7.2
Stable tag: 4.2.3
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

This is the official WooCommerce extension to ship product using The Courier Guy.

== Description ==

The Courier Guy extension for WooCommerce enables you to ship product using The Courier Guy.

= Why choose The Courier Guy? =

The Courier Guy has built a strong reputation through strong customer relations and effective personal service. Today The Courier Guy is trusted, recognised and the fastest growing courier company in South Africa.

== Installation ==

= MINIMUM REQUIREMENTS =
PHP 7.2 or greater is recommended
MySQL 5.6 or greater is recommended

WooCommerce 3.9.3 or greater is recommended
Visit the [WooCommerce Plugin](https://wordpress.org/plugins/woocommerce/) page for more details.

A Courier Guy account.
Please ensure that your Courier Guy account has credit, if there is no credit in your Courier Guy account then the plugin will not function correctly.
Visit the [The Courier Guy Website](https://www.thecourierguy.co.za/contact/) page for more details.

= AUTOMATIC INSTALLATION =
Automatic installation is the easiest option — WordPress will handle the file transfer, and you won’t need to leave your web browser. To do an automatic install of 'The Courier Guy Shipping for WooCommerce', log in to your WordPress dashboard, navigate to the Plugins menu, and click “Add New.”

In the search field type “The Courier Guy Shipping for WooCommerce,” then click “Search Plugins.” Once you’ve found us, you can view details about it such as the point release, rating, and description. Most importantly of course, you can install it by! Click “Install Now,” and WordPress will take it from there.

= MANUAL INSTALLATION =
Manual installation method requires downloading the 'The Courier Guy Shipping for WooCommerce' plugin and uploading it to your web server via your favourite FTP application. The WordPress codex contains instructions on how to do this here.

= UPDATING =
Automatic updates should work smoothly, but we still recommend you back up your site.

= CONFIGURATION =

To configure your shipping, log in to your WordPress dashboard, navigate to the Woocommerce -> Settings menu, click the Shipping tab, and click “Add shipping zone.”

Fill out the form as follows, please also see the attached screenshots:

**Zone name**
The Courier Guy

**Zone regions**
Select regions as desired

**Shipping methods**
Click the 'Add shipping method', select 'The Courier Guy' from the available options and click 'Add shipping method'.

Now you can edit the newly created Shipping Method.

Fill out the form as follows, please also see the attached screenshots:

**Title**
The Courier Guy

**Account number**
The account number supplied by The Courier Guy for integration purposes.

**Tax status**
VAT applies or not

**Username**
The username supplied by The Courier Guy for integration purposes.

**Password**
The password supplied by The Courier Guy for integration purposes.

**Company Name**
The name of your company.

**Contact Name**
The name of a contact at your company.

**Shop Address1**
The address used to calculate shipping, this is considered the collection point for the parcels being shipping.

**Shop Address2**
The address used to calculate shipping, this is considered the collection point for the parcels being shipping.

**Shop Postal Code**
The address used to calculate shipping, this is considered the collection point for the parcels being shipping.

**Shop Phone**
The telephone number to contact the shop, this may be used by the courier.

**Shop Area / Suburb**
The suburb used to calculate shipping, this is considered the collection point for the parcels being shipping.
It is important to note that you will need to save the Shipping Method, with the correct 'Account number', 'Username' and 'Password' in order for this setting to auto-complete and populate the 'Shop Area / Suburb' options from The Courier Guy.

**Shop Town / City**
The suburb used to calculate shipping, this is considered the collection point for the parcels being shipping. This is the town/city used as the origin in the waybill.

**Exclude Rates**
Select the rates that you wish to always be excluded from the available rates on the checkout page.

**Percentage Markup**
Percentage markup to be applied to each quote.

**Automatically Submit Collection Order**
This will determine whether or not the collection order is automatically submitted to The Courier Guy after checkout completion.

**Ship internationally using other carriers**
When enabled, this will hide The Courier Guy 'Suburb/Area' when changing countries on the checkout page and will not make the field 'required'. If unsure, leave this disabled.

**LOF Only Service**
This will determine whether to display ONLY the 'LOF: Local Overnight Flyer' service option on checkout, if the response from The Courier Guy quote contains the 'LOF: Local Overnight Flyer' service.

**Price Rate Override Per Service**
These prices will override The Courier Guy rates per service.
Select a service to add or remove price rate override.
Services with an overridden price will not use the 'Percentage Markup' setting.

**Label Override Per Service**
These labels will override The Courier Guy labels per service.
Select a service to add or remove label override.

**Product Quantity per Parcel**
This will allow for a single parcel to be allotted per the configured 'Product Quantity per Parcel' value.
PLEASE NOTE: Altering the 'Product Quantity Per Parcel' setting may cause quotes to be inaccurate and The Courier Guy will not be responsible for these inaccurate quotes.

**Length of Global Parcel**
Length of the global parcel - required if Product Quantity per Parcel is set

**Width of Global Parcel**
Width of the global parcel - required if Product Quantity per Parcel is set

**Height of Global Parcel**
Height of the global parcel - required if Product Quantity per Parcel is set

**Waybill PDF Paper Size**
This is the paper size used when generating Waybill print PDF.
This setting is used in conjunction with a custom Waybill print PDF template.
The Courier Guy cannot guarantee that the generic Waybill print PDF template will look good for all sizes.

**Waybill PDF Copy Quantity**
This is the number of copies generated per Waybill print PDF.
This setting is used in conjunction with a custom Waybill print PDF template.
The Courier Guy cannot guarantee that the generic Waybill print PDF template will look good for all copy amounts.

**Enable shipping insurance**
This will enable the shipping insurance field on the checkout page

**Enable free shipping**
This will enable free shipping over a specified amount

**Rates for free Shipping**
Select the rates that you wish to enable for free shipping

**Amount for free Shipping**
Enter the amount for free shipping when enabled

**Percentage for free Shipping**
Enter the percentage (shipping of product) that qualifies for free shipping when enabled. Zero to disable

**Enable free shipping from product setting**
This will enable free shipping if the product is included in the basket

**Suburb location**
Select the location of the Suburb field on checkout form.
The Suburb field will be displayed after the selected location.

**Suburb title**
Enter the title for the Suburb field.
This custom Suburb Title will be displayed on the checkout form.

**Frequently Asked Questions**

= Does this require a Courier Guy account? =

Yes! A Courier Guy account is required. To open an account please refer to [The Courier Guy](https://www.thecourierguy.co.za/contact/)

= Where can I get support or talk to other users? =

If you get stuck, you can ask for help in the Plugin Forum.

*Parcel Configuration in Products*
The section "The Courier Guy Settings" in a product configuration may be configured for parcels.
This allows for several items of a product to be shipped as a parcel. Examples might be shirts or computer RAM,
where from 1 to Product Quantity Per Parcel are shipped in a single parcel.
If the "Product Quantity per Parcel" is left blank this feature is disabled and the global parcel settings will
apply. Otherwise the positive value is used.
As an example, if the Product Quantity per Parcel is 10, an order of 1, 5 or 10 items will ship in one parcel, while an order of
14 items will ship in two parcels - one with 10 items and one with 4 items. In all cases the parcel size will be identical
but the parcel weight will depend on the number of items inside.

Parcel dimensions for a product specific parcel can be set here, and preferably should be if this feature is used.
If the dimensions are not set (left blank), an algorithm is used to calculate the parcel size based on the allowed quantity
and individual product dimensions.

== Changelog ==
= 4.2.3 - Aug 05, 2020
* Fix Call to undefined method ParcelPerfectApiPayload::factorise() if settings not configured.
* Improve ambiguous label "South Africa Only" -> "Ship internationally using other carriers".
* Waybill in email invalid if ‘collect from courier guy’ is not enabled.

= 4.2.2 - Jul 31, 2020
* Fix malformed number error.
* Use WC() session rather than $_SESSION to fix missing shipping information on orders.
* Fix null string issue.
* Add NFS service.
* Fix variable product calculations.
* Make shipping insurance on checkout optional.

= 4.2.1 - Jul 29, 2020
* Remove delivery date selection from checkout.

= 4.2.0 - Jul 25, 2020
* Add conditional free shipping feature.
* Add custom label and location for suburb area field.
* Fix shipment notifications.
* Fix parcel size, volume and weight calculations.
* Add parcel dimension configuration at both global and product levels.
* Add order id as WayBill reference.
* Add order notes for Parcel Perfect endpoint queries.
* Problem of variable products not calculating resolved with new methods.
* Adjust Waybill position and add clickable link in emails.
* Fix deprecated code warnings.
* Fix PHP missing index warnings.
* Fix collections submitted for the following day.
* Fix contact number is present where the name is supposed to go.
* Add option: If free shipping is active, remove all other shipping methods from checkout.
* Add option: Enable free shipping if selected products are in the cart.
* Add option: Enable free shipping if shipping total is a selected percentage of the total order value.
* Added VAT option for TCG shipping.
