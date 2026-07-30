# Codeholt Bundles for WooCommerce

Create product bundles for WooCommerce — increase Average Order Value with fixed-price bundles, automatic savings calculation, stock sync, a Gutenberg block, an Elementor widget and built-in analytics.

- **Version:** 1.0.0
- **Requires:** WordPress 6.2+, WooCommerce 8.0+, PHP 7.4+
- **License:** GPL-2.0-or-later
- **Author:** [Ashraful Sarkar Naiem](https://profiles.wordpress.org/ashrafulsarkar/) · [Codeholt](https://codeholt.com/)

## What it does

Adds a native **Bundle** product type to WooCommerce. Combine multiple products into one bundle with a fixed discounted price (or auto-calculated price), show customers exactly how much they save, and sell more per order — one cart line, correct stock handling, native price fields so sorting and filtering keep working.

## Features

- **Bundle builder** — search, add, remove and drag-and-drop reorder simple products; per-item quantity and hide toggle; live totals preview
- **Pricing modes** — Auto calculate (sum of products) or Fixed bundle price with automatic "Save $X (Y%)" savings
- **Stock management** — validates and reduces bundled product stock, prevents overselling, restores on cancel/refund, live child sync
- **Cart & orders** — one cart line with contents and savings shown in classic and block cart/checkout, orders, emails and admin
- **Product page layouts** — Table or Compact; global default plus a per-bundle override (extensible registry for add-ons — Pro adds List, Grid, Inline and Custom)
- **Settings panel** — WooCommerce → Bundles → Settings: layout defaults, included-products heading, savings badge toggle
- **Overview analytics** — bundles sold, revenue, customer savings, top bundle, per-bundle sales table with revenue-share bars (HPOS-aware, cached)
- **Import / Export** — JSON (re-importable, SKU matching) and CSV exports with a clean two-card UI
- **Display anywhere** — `[bundle id="123"]` shortcode, Product Bundle Gutenberg block, Elementor widget
- **SEO, a11y, performance** — enriched structured data, ARIA labels, zero frontend JavaScript, conditional asset loading

Full feature documentation: [docs/FEATURES.md](docs/FEATURES.md)

## Installation

1. Upload the plugin to `/wp-content/plugins/` (or install via the Plugins screen) and activate it — WooCommerce must be active.
2. Go to **Products → Add New** and choose the **Bundle product** type.
3. Open the **Bundled Products** tab, add products, pick a pricing mode and publish.

## For developers

The plugin is built to be extended — the Pro add-on uses only public hooks:

- Pricing: `bpfw_pricing_modes`, `bpfw_pricing_mode_options`, `bpfw_custom_mode_prices`
- Children: `bpfw_allowed_child_types`, `bpfw_child_search_help`
- Layouts: `bpfw_product_layouts`, `bpfw_layout_icon_cells`, `bpfw_bundle_layout`, `bpfw_inline_css`
- Admin page: `bpfw_admin_tabs`, `bpfw_admin_tab_{slug}`, `bpfw_settings_sections`, `bpfw_save_settings`, `bpfw_overview_days`, `bpfw_overview_actions`
- Data: `bpfw_export_bundle_data`, `bpfw_import_bundle`, `bpfw_save_bundled_items`, `bpfw_bundle_pricing`

Templates can be overridden by copying files from `templates/` to `yourtheme/codeholt-bundles-for-woocommerce/`.
