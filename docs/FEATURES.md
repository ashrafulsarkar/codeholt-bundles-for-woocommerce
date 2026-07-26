# Bundle Product for WooCommerce — Feature Documentation

> **Version:** 1.1.0 · **Last updated:** 2026-07-26
> This document tracks every feature of the free plugin. It is updated with each development change.

**Status legend:** ✅ Done · 🔄 In progress · 📅 Planned

---

## Free Version Feature Status

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| 1 | Bundle Product Type | ✅ | Native WooCommerce product type `bundle` |
| 2 | Bundle Builder | ✅ | Search, add, remove, drag & drop reorder (simple products) |
| 3 | Fixed Bundle Price | ✅ | Auto Calculate / Fixed Price modes |
| 4 | Auto Price Calculation | ✅ | Regular price, sale price and savings computed automatically |
| 5 | Stock Management | ✅ | Validate, reduce, restore, prevent overselling, live sync |
| 6 | Product Visibility | ✅ | Per-item "Hide" toggle for the bundle page |
| 7 | Add Bundle to Cart | ✅ | One click, one cart line for the whole bundle |
| 8 | Cart Display | ✅ | Bundle name, product list and savings (classic + blocks cart) |
| 9 | Order Support | ✅ | Bundled products shown in orders, emails and admin |
| 10 | Product Page Layouts | ✅ | List / Grid / Compact — global default + per-bundle override |
| 11 | Bundle Shortcode | ✅ | `[bundle id="123"]` (+ `[bpfw_bundle]` alias) |
| 12 | Gutenberg Block | ✅ | Bundle select, layout, image/items toggles, live preview |
| 13 | Elementor Widget | ✅ | Bundle select, layout, color style controls |
| 14 | Responsive Layout | ✅ | Mobile-first CSS, works on desktop/tablet/mobile |
| 15 | Basic Analytics | ✅ | Bundles sold, revenue, savings given, top bundle (30 days) |
| 16 | Import / Export | ✅ | JSON + CSV export, JSON import (SKU matching) |
| 17 | SEO Ready | ✅ | Enriched Product structured data for bundles |
| 18 | Accessibility | ✅ | ARIA labels, keyboard-friendly forms, semantic markup |
| 19 | Performance | ✅ | Conditional assets, cached analytics, HPOS compatible, no frontend JS |
| 20 | Developer Hooks | ✅ | Actions/filters throughout + overridable templates |
| 21 | Settings Panel | ✅ | Layout defaults + design options (WooCommerce → Bundles → Settings) |
| 22 | Design Options | ✅ | Accent/savings/border colors + corner radius via CSS variables |

---

## Feature Details

### 1. Bundle Product Type
A new **Bundle product** type appears in the product type dropdown (Products → Add New). It behaves natively — pricing, tax, shipping, and AJAX add-to-cart all work like standard WooCommerce products.

- Class: `BPFW_Product_Bundle` (extends `WC_Product_Simple`)
- Type slug: `bundle`

### 2. Bundle Builder
Found in the **Bundled Products** tab of the product data panel.

- **Search products** — selectWoo search restricted to published simple products
- **Add / Remove** — click to add, × to remove
- **Reorder** — drag rows by the handle (jQuery UI sortable)
- **Quantity per item** — each bundled product has its own quantity
- **Live totals preview** — products total and savings update as you edit

### 3–4. Pricing (Fixed / Auto)
Two pricing modes in the Bundled Products tab:

- **Auto Calculate** — bundle price = sum of bundled product prices. If any child is on sale, the bundle automatically shows regular vs sale price.
- **Fixed Price** — you set one bundle price. The plugin sets the regular price to the products total, so WooCommerce shows the strikethrough + savings natively.

Example: products total $120, fixed price $99 → customers see ~~$120~~ **$99** and "Save $21 (18%)".

Prices are stored in standard WooCommerce fields, so **sorting, filtering and price-based queries work everywhere**.

### 5. Stock Management
- Add-to-cart and cart/checkout validation checks every bundled product's stock, **including quantities already used by other cart items**
- Order stock reduction also reduces bundled product stock (with an order note trail); cancelling/refunding restores it
- Bundle stock status auto-syncs when a child product's stock or price changes (`BPFW_Sync`)
- A bundle can also manage its own stock (bundle-level stock) via the Inventory tab

### 6. Product Visibility
Each bundled item has a **Hide** checkbox — hidden items are still sold and stock-managed but not displayed on the product page or bundle cards.

### 7. Add Bundle to Cart
The whole bundle is added as **one cart line** with a snapshot of its contents. Works via the product page, archive AJAX buttons, shortcode/block/widget cards, and `?add-to-cart=ID` URLs.

### 8. Cart Display
Under the cart item name customers see:
- **Includes:** 2 × Product A, 1 × Product B
- **You save:** $21

Works in the classic cart/checkout **and** Cart & Checkout Blocks (Store API).

### 9. Order Support
Order line items carry:
- Visible **Includes** meta (shows in order details, admin order screen, and all emails)
- Hidden `_bpfw_bundled_items` + `_bpfw_savings` meta (used for analytics and stock handling)

### 10. Product Page Layouts
The bundle product page shows an included-products list (heading text is configurable), a total/savings summary bar, stock status, quantity input, and the add-to-cart button. Template: `templates/single-product/add-to-cart/bundle.php`.

Three layouts are available:
- **List** (default) — horizontal rows with thumbnail, name, price and quantity pill
- **Grid** — bundled products as tiles with a centered image and a corner quantity badge
- **Compact** — dense rows without images

The default layout is set in **WooCommerce → Bundles → Settings**; each bundle can override it from the **Bundled Products** tab (visual layout picker: Default / List / Grid / Compact). Meta key: `_bpfw_layout` (empty = follow settings).

### 11. Shortcode
```
[bundle id="123" layout="card" show_image="yes" show_items="yes"]
```
`layout`: `card` | `list` — when omitted, the default comes from the **Bundle card layout** setting. Alias: `[bpfw_bundle]`.

### 12. Gutenberg Block
**Product Bundle** block (WooCommerce category): bundle selector, layout, image and items toggles, server-side rendered live preview.

### 13. Elementor Widget
**Product Bundle** widget: bundle select2, layout select, and style controls (accent color, button background/text color via CSS variables).

### 14. Responsive Layout
Mobile-first CSS with CSS variables (`--bpfw-*`) so themes/agencies can restyle without overriding rules.

### 15. Basic Analytics
**WooCommerce → Bundles**: active bundles, bundles sold, bundle revenue, customer savings given, and top bundle for the last 30 days, plus a per-bundle sales table and a **Quick start** panel with shortcuts (create a bundle, open settings). HPOS-aware SQL, cached for 15 minutes.

### 16. Import / Export
**WooCommerce → Bundles → Import/Export**:
- Export all bundles as **JSON** (portable, re-importable) or **CSV**
- Import from JSON — bundled products matched by **SKU first**, then product ID

### 17. SEO
Bundle structured data is enriched with the bundled products (`isRelatedTo`), on top of WooCommerce's native Product schema with correct offer pricing.

### 18. Accessibility
ARIA labels on bundle sections and controls, keyboard-accessible forms, semantic list markup.

### 19. Performance
- **Zero frontend JavaScript** in the free version
- CSS loaded **only** on bundle pages / pages using the shortcode, block or widget
- Analytics cached via transients; child→bundle lookups use an indexed meta key (`_bpfw_contains`)
- HPOS + Cart/Checkout Blocks compatibility declared

### 20. Developer Hooks

**Filters**
| Hook | Purpose |
|------|---------|
| `bpfw_bundled_products` | Modify resolved bundle items |
| `bpfw_bundle_pricing` | Modify computed pricing/savings |
| `bpfw_bundle_card_html` | Filter rendered card HTML |
| `bpfw_save_bundled_items` | Filter items before saving |
| `bpfw_show_savings_badge` | Toggle the savings badge (default = plugin setting) |
| `bpfw_structured_data` | Filter bundle schema markup |
| `bpfw_default_settings` | Filter the default plugin settings |
| `bpfw_settings` | Filter resolved settings |
| `bpfw_save_settings` | Filter settings before they are saved |
| `bpfw_bundle_layout` | Filter the resolved product page layout |
| `bpfw_inline_css` | Filter the CSS variable overrides |

**Actions**
| Hook | Purpose |
|------|---------|
| `bpfw_loaded` | Plugin fully loaded |
| `bpfw_before_bundled_items` / `bpfw_after_bundled_items` | Around the product page items list |
| `bpfw_after_builder_panel` | End of the admin builder panel |
| `bpfw_bundle_synced` | After a bundle resyncs with children |
| `bpfw_order_line_item_created` | After bundle meta is added to an order item |

**Templates** — override by copying to `yourtheme/bundle-product-for-woocommerce/`:
- `single-product/add-to-cart/bundle.php`
- `bundle-card.php`

### 21. Settings Panel
**WooCommerce → Bundles → Settings** (also linked from the Plugins screen row):

- **Product page layout** — visual picker: List / Grid / Compact (global default)
- **Bundle card layout** — Card / Wide (default for shortcode, block and Elementor widget)
- **Included products heading** — the text above the bundled products list (default *"What's included"*)
- **Savings badge toggle** — show/hide the "Save $X (Y%)" badge storewide
- **Save / Reset to defaults** buttons; settings stored in one `bpfw_settings` option

### 22. Design Options
Limited, safe styling controls in the same Settings tab (WordPress color pickers):

- **Accent color** — quantity badges and the bundle card button
- **Savings color** — savings badge and savings text
- **Border color** and **corner radius** (0–40 px)

Values are output as CSS variable overrides (`--bpfw-accent`, `--bpfw-savings`, `--bpfw-border`, `--bpfw-radius`) inline with the frontend stylesheet, so themes and the Elementor widget's own style controls still win where they set the same variables.

---

## File Structure

```
bundle-product-for-woocommerce/
├── bundle-product-for-woocommerce.php   Main file (constants, autoloader, HPOS declare)
├── uninstall.php                        Cleanup (options + transients only)
├── readme.txt                           WordPress.org readme
├── docs/FEATURES.md                     This document
├── includes/
│   ├── class-bpfw-plugin.php            Loader
│   ├── bpfw-functions.php               Helpers
│   ├── class-bpfw-product-type.php      Type registration
│   ├── class-bpfw-product-bundle.php    Product class
│   ├── class-bpfw-sync.php              Price/stock sync engine
│   ├── class-bpfw-ajax.php              Builder AJAX
│   ├── class-bpfw-cart.php              Cart validation + display
│   ├── class-bpfw-order.php             Order meta + stock reduce/restore
│   ├── class-bpfw-frontend.php          Assets + product page + savings badge
│   ├── class-bpfw-shortcode.php         [bundle] shortcode
│   ├── class-bpfw-block.php             Gutenberg block
│   ├── class-bpfw-seo.php               Structured data
│   ├── admin/
│   │   ├── class-bpfw-admin.php         Builder panel + save
│   │   ├── class-bpfw-analytics.php     Analytics page (Overview + tabs)
│   │   ├── class-bpfw-settings.php      Settings tab (layout + design)
│   │   └── class-bpfw-import-export.php Import/Export
│   └── compat/
│       └── class-bpfw-elementor-widget.php
├── assets/
│   ├── css/admin.css · css/frontend.css
│   └── js/admin.js · js/block.js
└── templates/
    ├── bundle-card.php
    └── single-product/add-to-cart/bundle.php
```

---

## Pro Roadmap (upgrade path)

Mix & Match · Variable product support · Quantity rules · Dynamic/tiered discounts · Live price calculator · Bundle templates · Frequently Bought Together · AI recommendations · Conditional bundles · Scheduler · Inventory modes · Badges · Custom layouts · Shipping modes · Gift bundles · Bundle coupons · Advanced analytics & reports · White label · Role-based pricing · Composite bundles · Multi-vendor · Multi-currency · Marketing integrations · REST API CRUD · WP-CLI · A/B testing

---

## Changelog

### 1.1.0 — 2026-07-26
- **New: Settings panel** — WooCommerce → Bundles → **Settings** tab with two sections:
  - *Layout*: default product page layout (List / Grid / Compact), default bundle card layout (Card / Wide), included-products heading text, savings badge on/off
  - *Design*: accent color, savings color, border color, corner radius — output as CSS variable overrides (`bpfw_get_inline_css()`), theme keeps control of everything else
- **New: Product page layouts** — List, Grid and Compact; per-bundle override via a visual layout picker in the Bundled Products tab (`_bpfw_layout` meta)
- **New: Overview tab redesign** — Active bundles stat card, color-coded stat cards, Quick start panel with shortcuts
- **New:** "Settings" action link on the Plugins screen row
- **Improved:** bundle builder panel design (styled table, hover states, highlighted totals row)
- **Improved:** accent color now drives quantity badges and the bundle card button
- Settings stored in a single `bpfw_settings` option (removed on uninstall); shortcode/card default layout follows settings
- Verified: 21 new CLI assertions + all 31 original smoke tests passing (52/52)

### 1.0.0 — 2026-07-26
- Initial release: all 20 free features implemented (see table above).
