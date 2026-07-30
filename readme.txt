=== Codeholt Bundles for WooCommerce ===
Contributors: ashrafulsarkar, codeholt
Tags: woocommerce, product bundle, bundle, upsell, cross-sell
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create product bundles for WooCommerce with fixed pricing, automatic savings, stock sync, Gutenberg block, Elementor widget and analytics.

== Description ==

**Codeholt Bundles for WooCommerce** adds a lightweight, native "Bundle" product type to your store. Combine multiple products into one bundle with a fixed discounted price (or auto-calculated price), show customers exactly how much they save, and sell more per order — one cart line, correct stock handling, and native WooCommerce price fields so sorting and filtering keep working.

= Why bundles? =

* Increase Average Order Value (AOV)
* Improve cross-selling and upselling
* Reduce cart abandonment with clear savings
* Simplify product packaging

= Bundle builder =

Search, add, remove and drag & drop reorder simple products right on the product edit screen's **Bundled Products** tab. Each item gets its own quantity and an optional "hide on frontend" toggle, with a live totals preview while you build.

= Flexible pricing =

* **Auto calculate** — the bundle price is the sum of its products' prices.
* **Fixed bundle price** — set a flat price and the plugin shows an automatic "Save $X (Y%)" badge.
* **Automatic price sync** — the bundle's price and savings recalculate whenever a bundled product's own price changes.
* Prices live in WooCommerce's native price fields, so bundles sort, filter and show up in price-range widgets like any other product.

= Stock management =

Validates and reduces bundled product stock on purchase, prevents overselling when a child product runs low or out of stock, restores stock on cancellation/refund, and keeps bundle availability live-synced to its children.

= Cart, checkout & orders =

The whole bundle adds to cart as **one line item**, with its contents and total savings shown — fully working in both the classic cart/checkout and the WooCommerce Cart & Checkout blocks. The same details carry through to the order-received page, order emails, and the admin order screen.

= Product page layouts =

Two built-in layouts, **Table** and **Compact**, selectable as a site-wide default or overridden per bundle from **WooCommerce → Bundles → Settings**.

= Settings =

Set the default product-page layout and bundle-listing card style, customize the "included products" section heading, and toggle the savings badge on or off — all from **WooCommerce → Bundles → Settings**.

= Analytics overview =

A cached, HPOS-aware dashboard under **WooCommerce → Bundles** showing total bundles sold, total revenue, total customer savings, the top-selling bundle, and a per-bundle sales table with revenue-share bars.

= Import / Export =

Export every bundle as a re-importable **JSON** file (matches bundled products by SKU) or as a **CSV** report, and import bundles back in through a simple drag-and-drop dropzone.

= Display anywhere =

* **Shortcode** — `[bundle id="123"]`
* **Gutenberg block** — "Product Bundle", pick the bundle, layout and display options right in the block editor
* **Elementor widget** — "Product Bundle" widget with its own style controls

= SEO, accessibility & performance =

* Enriched structured data for bundles (bundle contents exposed to search engines)
* ARIA labels on interactive frontend markup
* Conditional asset loading — no bundle assets load on pages that don't need them
* Cached analytics queries and full HPOS (High-Performance Order Storage) compatibility

= Developer friendly =

Actions and filters throughout (pricing, layouts, admin tabs, import/export, settings) and fully overridable templates — see the "Other Notes" / readme on the plugin's GitHub/support page for the full hook list.

= Compatibility =

* WooCommerce HPOS (High-Performance Order Storage)
* WooCommerce Cart & Checkout Blocks
* Gutenberg and Elementor
* Any well-coded theme

== Installation ==

1. Upload the plugin to `/wp-content/plugins/` or install via the Plugins screen.
2. Activate the plugin. WooCommerce must be active.
3. Go to **Products → Add New**, choose the **Bundle product** type.
4. Open the **Bundled Products** tab, add products, choose a pricing mode and publish.
5. Optionally visit **WooCommerce → Bundles → Settings** to set default layouts and the savings badge.

== Frequently Asked Questions ==

= Which products can be bundled? =

Published simple products. Variable product support is on the roadmap.

= Does it work with the new WooCommerce cart and checkout blocks? =

Yes — bundle contents and savings display correctly in both classic and block-based cart/checkout.

= Can I override the templates? =

Yes. Copy any file from `templates/` into `yourtheme/codeholt-bundles-for-woocommerce/` and edit it.

= Does the bundle price update automatically? =

Yes. If you use auto-calculated pricing, the bundle's price and savings recalculate whenever a bundled product's price changes, with no manual resave needed.

= Is this compatible with High-Performance Order Storage (HPOS)? =

Yes, the plugin declares and is fully tested with HPOS enabled.

= Does this plugin hide or lock any features behind an upsell? =

No. Every feature listed above is fully usable with no artificial limits, nag screens or disabled UI.

== Changelog ==

= 1.0.0 =
* Initial release.
