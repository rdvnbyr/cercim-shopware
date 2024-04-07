# 1.12.0
- Added saving of custom fields to order addresses for shops below 6.4.14.0
- Added integration with Billie for handling B2B orders
- Added synchronization of the Klarna billing address to Shopware when finishing the order. Changes to the billing address in Shopware won't be synchronized with Klarna anymore. 

# 1.11.0
- Added the handling of the authorization callback
- Fixed the restriction to one payment method when using Klarna Express
- Changed the release process for building one plugin version that is compatible with Shopware 6.4 and Shopware 6.5

# 1.10.0
- Added check for selected Klarna Express before preselecting payment method of Klarna
- Fixed the rounding of amounts
- Fixed the overwriting of the payment description
- Fixed the display of a custom image for a payment method
- Fixed the addition of the cookies in the required group
- Fixed the handling when changing the payment method of an order
- Changed usage of custom fields on orders to dedicated EntityExtension to avoid issues with other plugins
- Changed synchronization with Klarna so order positions won't be synced with Klarna once the order is captured

# 1.9.0
- Added compatibility with Shopware 6.5.0.0
- Adjusted the display of the payment names in the checkout

# 1.8.0
- Fixed translation of error message when adjusting order in administration
- Added product variant information in capture/refund modal
- Fixed error that occurred by closing the order history detail modal
- Optimize code to reduce possible errors
- Injected environment variable `APP_SECRET` via Symfony service definition in respective services to fix LogicException in checkout.
- Added compatibility for USA
- Fixed overwrite of administration component `sw-order-detail-base` for other plugins

# 1.7.0
- Added Klarna Express Login functionality to the checkout
- Added session update request to reduce session creation with Klarna
- Fixed validation of tos checkbox in checkout

# 1.6.3
- Added a check if terms and conditions were accepted in the checkout

# 1.6.2
- Added compatibility with CSRF mode "ajax"

# 1.6.1
- Added compatibility with Shopware 6.4.10.0

# 1.6.0
- Added NetiNextEasyCoupon compatibility. Vouchers will be transferred to Klarna as gift cards.
- Updated minimum version to Shopware 6.3.0.0
- Added notice for inheritance of plugin configuration for Shopware versions lower 6.3.4.0
- Fixed the de- and activation of the button for order completion with Klarna payment methods
- Fixed transition from `authorized` to `paid` status with manual full capture
- Removed Rule installer due to overwriting of custom rules
- Removed the explicit conditions for payment methods
- Fixed the cover URL for order requests to the Klarna API
- Added the product URL for order requests to the Klarna API
- Fixed the available payment methods depending on the response from the Klarna API 

# 1.5.0
- Moved language files to new location
- Added Ireland to available countries
- Optimized the display of inherited values in the plugin configuration
- Set order payment status to authorized if payment is authorized by Klarna
- Use selected shipping method name as order position for Klarna order
- Added SwagCustomizedProducts compatibility

# 1.4.4
- Fix usage of vouchers with multiple tax rates
- Fix handling of the payment finish modal, when being closed
- The Klarna widget is now displayed above the position table in the checkout if at least Shopware 6.4 is used
- Fix the button for order completion when closing the Klarna modal
- Fix eslint error message with Shopware 6.4.5.x

# 1.4.3
- Fix uninstall of plugin when user data should not be kept

# 1.4.2
- Added support for PHP 8
- Update cart hash logic for verification of order editing

# 1.4.1
- Added compatibility with Shopware 6.4

# 1.4.0
- Removed instant Shopping.
- Added translations for payment methods.
- Fix error in Internet Explorer that prevents order completion

# 1.3.8
- Removed Klarna payments rule from Rule-Builder.
- Added Italy as a supported country. Note: Instant Shopping is not supported for Italy.
- Fix rare error for invalid values when saving plugin configuration.  
- Automatic captures will only be executed if the fraud status is accepted.
- Automatic capture for delivery and order status open is now possible.

# 1.3.7
- Fix plugin configuration value errors
- Please note: There are known erroneous configuration behaviours with sales channel inheritance in the documentation

# 1.3.6
- Improve error handling for Instant Shopping
- Fix missing labels in plugin configuration
- Added note in documentation (https://klarna.kellerkinder.de/en/1-3/index.html#h-translation-of-the-payment-methods) about translation of payment methods.

# 1.3.5
- Add default name for payment methods for the installation with a different system language

# 1.3.4
- Fix handling of promotions in cart

# 1.3.3
- Fix automatic package of zip archive to include compiled files again
- Fix use of wrong exception class

# 1.3.2
- Fix styling issues of the Instant Shopping button
- Fix error on updating order when order has been captured
- Improve performance in checkout and order update

# 1.3.1
- Fix error when selecting an order from the search results

# 1.3.0
- Add locale and currency to the rule for available Klarna payment methods in the checkout
- Send shipping tracking information to the Klarna merchant portal  
- Fix error during installation if a language could not be found
- Fix Instant Shopping for guests with activated required registration settings
- Optimize validation of API credentials

# 1.2.1
- Fix delivery information display on detail pages
- Fix administration order updates for non-Klarna orders

# 1.2.0
- Added compatibility with Shopware 6.2

# 1.1.0
- Implementation of Klarna instant shopping
- Add support for net prices (starting from Shopware 6.2.0)

# 1.0.4
- Fix checkout confirmation button for non-Klarna payment methods

# 1.0.3
- Fix customer name for Klarna Payment session

# 1.0.2
- Added combined Pay Now payment method and Credit Card
- Order changes in the administration are verified with Klarna before persisting the changes
- Changed address structure
- Payment method categories can now be disabled from the plugin configuration
- Skip verification of order changes during order process before Klarna order is created

# 1.0.1
- Fixed a rounding issue for Klarna calls

# 1.0.0
- First version of the Klarna Payment integration for Shopware 6.1
