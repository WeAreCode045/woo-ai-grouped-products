# WooCommerce AI Grouped Products

A WordPress plugin that automatically groups similar WooCommerce products into variable products based on title, category, and brand attributes.

## Features

- Automatically analyzes product titles, categories, and brand attributes to find similar products
- Groups similar products into variable products with variations
- Creates variations for color and size attributes
- Preserves product data including price, SKU, EAN, and stock information
- Simple admin interface to start the grouping process
- Handles large product catalogs with batch processing

## Requirements

- WordPress 5.6 or later
- WooCommerce 5.0 or later
- PHP 7.4 or later

## Installation

1. Upload the `woo-ai-grouped-products` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce → AI Grouped Products to start using the plugin

## Usage

1. Navigate to WooCommerce → AI Grouped Products in your WordPress admin
2. Click the "Start Grouping Products" button
3. The plugin will analyze your products and group similar ones together
4. Progress will be shown on the screen
5. Once complete, you'll see a list of created variable products with links to edit them

## How It Works

The plugin uses the following criteria to determine if products should be grouped:

1. **Title Similarity**: Product titles must be at least 85% similar
2. **Category**: Products must share at least one category
3. **Brand**: Products must have the same brand (checks for brand attribute in multiple languages)

For each group of similar products, the plugin will:

1. Create a new variable product
2. Set the common title, description, and other product data
3. Create variations for each original product
4. Copy over all relevant product data (price, SKU, EAN, stock, etc.)
5. Set the original products to draft status

## Customization

### Hooks

The plugin provides several filters for customization:

- `wc_ai_grouped_products_min_similarity`: Change the minimum title similarity percentage (default: 85)
- `wc_ai_grouped_products_brand_attributes`: Modify the list of brand attribute names to check
- `wc_ai_grouped_products_variation_attributes`: Customize which attributes are used for variations

### Example: Change Minimum Similarity

```php
add_filter('wc_ai_grouped_products_min_similarity', function() {
    return 90; // Increase to 90% similarity
});
```

## Frequently Asked Questions

### Will this affect my existing products?

The plugin will create new variable products and set the original simple products to draft status. Your original products will not be deleted.

### Can I undo the grouping?

Yes, you can:
1. Delete the created variable products
2. Change the status of the original products from draft back to published

### How can I improve the grouping accuracy?

1. Make sure your product titles are consistent
2. Ensure products have the correct categories assigned
3. Use the brand attribute consistently across products

## Support

For support, please open an issue on the [GitHub repository](https://github.com/yourusername/woo-ai-grouped-products).

## License

GPL-2.0+

## Changelog

### 1.0.0
* Initial release
