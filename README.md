# wp-customizer-generator

A Wordpress Customizer Generator that simplify the customizer usage

## Example:

### PHP

```php
<?php

// Base include
require_once __DIR__ . '/customizer-generator/wp-customizer-generator.php';

// creating the instance
$customizer = new WPCG_Customizer_Generator();

$customizer
            // Add new Panel (All sections below will be in this panel)
           ->add_panel( 'Theme panel' )
           // Add New section  (Fields Below will be on this section)
           ->add_section( 'Labels' )
           // Add new text field with a default
           ->add_text( 'custom-title', array('Section Title', 'Default Title') )
           // Add new Textarea field
           ->add_textarea( 'custom-text', 'Section Text' )
           // New Section (the fields below a new section will be inside it)
           ->add_section( 'Images' )
           // An image Fields
           ->add_image( 'custom-logo', 'Brand Logo' )
           ->add_image( 'custom-banner', 'Banner' )
           // New Section
           ->add_section( 'Colors' )
           // Text Color With default (automatically add css)
           ->add_color_text( 'custom-text-color', array('Text Color', '#F00') )
           // Background Color (automatically add css)
           ->add_color_background( 'custom-text-background', 'Background Color' )
           // Background Image (automatically add css)
           ->add_image_background( 'custom-image-background' , 'Background Image')
```

### HTML
````html
<!--The background Image and Color on the same element-->
<section data-wp-setting="custom-text-background custom-image-background">

    <!--The Title Field-->
    <h1 data-wp-setting="custom-title"><? $customizer->the_setting( 'custom-title' ) ?></h1>

    <!--The Textarea Field and Text Color-->
    <p data-wp-setting="custom-text custom-text-color"><? $customizer->the_setting( 'custom-text' ) ?></p>

    <!--The Custom Logo-->
    <a href="/" data-wp-setting="custom-logo"><? $customizer->the_setting( 'custom-logo' ) ?></a>

    <!--The Custom Banner-->
    <figure data-wp-setting="custom-banner"><? $customizer->the_setting( 'custom-banner' ) ?></figure>
</section>
````

On the example, some fields are added and is showed how use them.

Sections and Panels automatically Define Contexts.
When you add one, all fields and sections below it will be automatically wrapped.
