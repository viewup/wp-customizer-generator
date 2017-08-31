# wp-customizer-generator

A Wordpress Customizer Generator that simplify the customizer usage

## Installing

You can install this as a plugin or as a include on your theme

### As a Plugin

 1. [Install Kirki Plugin](https://br.wordpress.org/plugins/kirki/) and activate it.
 2. [Download](https://github.com/viewup/wp-customizer-generator/archive/master.zip) the Project ZIP.
 3. In WordPress > Plugins > Add New, Upload the Plugin Zip. Install and activate it.

### As a Include in Theme


 1. Download the project:
	 -  Clone the project using `git clone --recursive -j8 https://github.com/viewup/wp-customizer-generator.git`
	 - Download the Project ZIP and download Kirki ZIP. unzip the project and unzip kirki on `/kirki` folder.
 2. Place the project in the desired theme folder (`/inc/customizer-generator`)
 3. Include the main PHP file:  `require_once __DIR__ . '/inc/customizer-generator/wp-customizer-generator.php';` on `functions.php` or in another PHP file


## Examples

Some usage usage example:

### PHP

```php
<?php

// Base include
include_once __DIR__ . '/customizer-generator/wp-customizer-generator.php';

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

## Support

Currently, all Kirki and WP Fields are supported, but not optimized. The fields below are optimized for easy insertion:

 - [x] image (field, background, HTML - need more options)
 - [x] color (field, background, text and custom)
 - [x] text (field, HTML)
 - [x] textarea (field, HTML)
 - [x] color-palette (field)
 - [x] multicheck (field)
 - [x] palette (field)
 - [x] radio-buttonset (field)
 - [x] radio-image (field)
 - [x] radio (field)
 - [x] select (field)
 - [x] sortable (field)
 - [x] checkbox (field)
 - [x] switch (field)
 - [x] toggle (field)
 - [x] code (field)
 - [x] number (field)
 - [x] dashicons (field)
 - [x] dimension (field) - Can be Improved
 - [x] dropdown-pages (field)
 - [x] multicolor (field)
 - [x] slider (field)
 - [x] spacing (field) - Can be Improved
 - [x] typography (field) - Can be Improved
 - [x] upload (field)
 - [x] custom
 - [ ] repeater (see below)

(field): Field Register

(HTML): Field Register and render

Is planned a good support for Repeater, making the field and repeater field insertion the same (signature).
