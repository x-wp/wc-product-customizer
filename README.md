# WooCommerce Product Customizer

This is a library that allows you to easily customize product types, options and tabs in WooCommerce.

## Installation
  
```bash
composer require x-wp/wc-product-customizer
```

## Usage

To use the customizer - you need to extends the base customizer class. 

### Customizing product types

```php
<?php

class My_Customizer extends \XWC\Product\Customizer_Base {
  public function custom_product_types( array $types ): array {
    $types['simple'] = array(
      'class' => My_Simple_Product::class,
    );

    $types['composite'] = array(
      'class'   => Composite_Product::class,     // Product classname to use - Mandatory for custom product types
      'name'    => 'Composite product',          // Name to be displayed in product type selectors
      'extends' => array( 'simple' ,'virtual' ), // Base product type to extend. Used to show hide / option tabs groups
      'tabs'    => array(
        array(
          'id'    => 'composite', // Tab ID.  Used in HTML generation, and as an action suffix
          'label' => 'Structure', // Tab label.
          'icon'  => 'woo:\f307', // Tab icon. Unicode strings will use dashicons, strings prefixed with woo: will use woocommerce icomoon font.
          'prio'  => 33,          // Tab priority.
        )
      )
    );

    return $types;
  }
}
```

This will create a new product type called `composite` that will show all the product options for `simple` and `virtual` products, and add a new tab called `Structure`.

### Customizing product options

```php
<?php

class My_Customizer extends \XWC\Product\Customizer_Base {
  public function custom_product_options( array $options ): array {
    $options['premium'] = array(
      'default' => false,                         // Default option value.
      'desc'    => 'Enable premium features',     // Option description.
      'for'     => array( 'simple', 'variable' ), // Product types this option is available for.
      'label'   => 'Premium',                     // Option label.
      'prop'    => true,                          // Whether this option is a product property.
      'tabs'    => array(),                       // Custom tabs to show when this option is enabled.
    );

    return $options;
  }
}
```

### Displaying options in custom tabs.

For each registered tab, we display all the needed panel data. You can add your custom options by using provided hooks.

```php
<?php

class My_Custom_Product_Options {
  public function __construct() {
    add_action( 'xwc_product_options_composite', array( $this, 'display_options' ), 99, 0 );
  }

  public function display_options(): void {
    global $product_object;

    echo '<div class="options_group">';

    woocommerce_wp_select(
      array(
        'id'      => '_my_data',
        'label'   => 'My Data',
        'default' => '',
        'class'   => 'wc-enhanced-select',
        'options' => array(
          '' => 'Select a value',
          'option1' => 'Option 1',
          'option2' => 'Option 2',
        ),
      )
    );

    echo '</div>';
  }
}
