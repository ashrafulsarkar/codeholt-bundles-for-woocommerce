# Codeholt Bundles for WooCommerce

Create product bundles for WooCommerce — increase Average Order Value with fixed-price bundles, automatic savings calculation, stock sync, a Gutenberg block, an Elementor widget and built-in analytics.

- **Version:** 1.0.0
- **Requires:** WordPress 6.2+ (tested up to 7.0), WooCommerce 8.0+ (tested up to 10.0), PHP 7.4+
- **License:** GPL-2.0-or-later
- **Author:** [Ashraful Sarkar Naiem](https://profiles.wordpress.org/ashrafulsarkar/) · [Codeholt](https://codeholt.com/)
- **Text domain:** `codeholt-bundles-for-woocommerce`

## What it does

Adds a native **Bundle** product type to WooCommerce. Combine multiple products into one bundle with a fixed discounted price (or auto-calculated price), show customers exactly how much they save, and sell more per order — one cart line, correct stock handling, native price fields so sorting and filtering keep working.

Why bundles: increase Average Order Value, improve cross-sell/upsell, reduce cart abandonment with a clear savings message, and simplify how packaged/combo products are sold.

## Features

### Bundle builder
Search, add, remove and drag-and-drop reorder simple products inside the **Bundled Products** tab on the product edit screen. Each item has its own quantity and a "hide on frontend" toggle, with a live totals preview as you build.

### Pricing
- **Auto calculate** — bundle price is the sum of the bundled products' prices.
- **Fixed bundle price** — set a flat price and the plugin shows a "Save $X (Y%)" badge computed from the difference.
- Prices are written to WooCommerce's native price fields, so bundles sort, filter and appear in price-range widgets exactly like any other product.
- **Automatic price sync** — if a bundled product's price changes, the bundle's price/savings recalculate automatically (`BPFW_Sync`).

### Stock management
Validates and reduces bundled product stock on purchase, prevents overselling when a child is out of stock or a quantity exceeds availability, restores stock on order cancellation/refund, and keeps bundle availability live-synced to its children.

### Cart, checkout & orders
The whole bundle adds to cart as **one line item** showing its contents and total savings. Fully supported in both classic cart/checkout and the WooCommerce Cart & Checkout blocks. Bundled contents and savings also appear on the order-received page, order emails, and the admin order screen.

### Product page layouts
Two built-in layouts — **Table** and **Compact** — selectable as a global default (WooCommerce → Bundles → Settings) or overridden per bundle. The layout registry is extensible via filters, which is how the Pro add-on plugs in List, Grid, Inline and a Custom layout builder without touching free-plugin code.

### Settings (WooCommerce → Bundles → Settings)
- Default product-page layout and default card layout (card/list) for bundle listings.
- Custom heading text for the "included products" section.
- Toggle the savings badge on/off.

### Analytics overview (WooCommerce → Bundles)
An HPOS-aware, cached (15-minute transient) dashboard showing: total bundles sold, total revenue, total customer savings, the top-selling bundle, and a per-bundle sales table with revenue-share bars. The date range and available toolbar actions are filterable (`bpfw_overview_days`, `bpfw_overview_actions`), which is what lets the Pro add-on inject a period selector and CSV export into the same screen.

### Import / Export
- **Export** every bundle (published + draft) as a re-importable **JSON** file (matches bundled products by SKU) or as a **CSV** report.
- **Import** bundles back from a JSON file via a drag-and-drop dropzone UI.
- Two-card admin UI under the Import/Export tab.

### Display anywhere
- Shortcode: `[bundle id="123"]`
- **Product Bundle** Gutenberg block (dynamic/server-rendered) — pick the bundle, layout and display toggles (e.g. show image) right in the block editor.
- **Elementor widget** ("Product Bundle", under WooCommerce Elements) with its own style controls.

### SEO, accessibility & performance
- Enriches WooCommerce's structured data (`woocommerce_structured_data_product`) so bundle contents are exposed to search engines (`isRelatedTo` schema), filterable via `bpfw_structured_data`.
- ARIA labels on interactive frontend markup.
- Zero custom frontend JavaScript for display — assets are only enqueued conditionally on pages/blocks that actually use a bundle.
- Cached analytics queries; full HPOS (High-Performance Order Storage) compatibility.

## Compatibility

- WooCommerce HPOS (High-Performance Order Storage)
- WooCommerce Cart & Checkout Blocks
- Gutenberg block editor
- Elementor
- Any well-coded WooCommerce theme

## Installation

1. Upload the plugin to `/wp-content/plugins/` (or install via the Plugins screen) and activate it — WooCommerce must be active.
2. Go to **Products → Add New** and choose the **Bundle product** type.
3. Open the **Bundled Products** tab, add products, pick a pricing mode and publish.
4. Optionally visit **WooCommerce → Bundles → Settings** to set default layouts and the savings badge.

## Frequently Asked Questions

**Which products can be bundled?**
Published simple products. Variable product support is on the Pro roadmap.

**Does it work with the new WooCommerce cart and checkout blocks?**
Yes — bundle contents and savings display correctly in both classic and block-based cart/checkout.

**Can I override the templates?**
Yes. Copy any file from `templates/` into `yourtheme/codeholt-bundles-for-woocommerce/` and edit it.

**Does this plugin lock or hide any features behind an upsell?**
No. Every feature listed above is fully usable in the free plugin with no artificial limits, nag screens or disabled UI.

## For developers

The plugin is built to be extended — the Pro add-on uses only public hooks:

| Area | Hooks |
|---|---|
| Pricing | `bpfw_pricing_modes`, `bpfw_pricing_mode_options`, `bpfw_custom_mode_prices` |
| Children | `bpfw_allowed_child_types`, `bpfw_child_search_help` |
| Layouts | `bpfw_product_layouts`, `bpfw_layout_icon_cells`, `bpfw_bundle_layout`, `bpfw_inline_css` |
| Admin page | `bpfw_admin_tabs`, `bpfw_admin_tab_{slug}`, `bpfw_settings_sections`, `bpfw_save_settings`, `bpfw_overview_days`, `bpfw_overview_actions` |
| Data | `bpfw_export_bundle_data`, `bpfw_import_bundle`, `bpfw_save_bundled_items`, `bpfw_bundle_pricing` |
| SEO | `bpfw_structured_data` |

Templates can be overridden by copying files from `templates/` to `yourtheme/codeholt-bundles-for-woocommerce/`.

## Support

Report bugs or request features via the [WordPress.org support forum](https://wordpress.org/support/plugin/codeholt-bundles-for-woocommerce/) or [Codeholt](https://codeholt.com/).

## Changelog

### 1.0.0
Initial release.
