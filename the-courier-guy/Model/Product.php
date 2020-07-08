<?php
/**
 * @author The Courier Guy
 * @package tcg/model
 * @version 1.0.0
 */
$customPostType = new CustomPostType('product');
$customPostType->addMetaBox('The Courier Guy Settings',
    [
        'form_fields' => [
            'product_quantity_per_parcel' => [
                'display_name' => 'Product Quantity per Parcel',
                'property_type' => 'text-with-disclaimer',
                'description' => 'This will allow for a single parcel to be allotted per the configured \'Product Quantity per Parcel\' value.',
                'disclaimer_description' => 'I accept that altering the \'Product Quantity Per Parcel\' setting may cause quotes to be inaccurate and The Courier Guy will not be responsible for these inaccurate quotes.',
                'placeholder' => '1',
            ],
            'product_length_per_parcel'   => [
                'display_name'  => 'Parcel Length',
                'property_type' => 'text',
                'description'   => 'Length of a parcel',
                'placeholder'   => '1',
            ],
            'product_width_per_parcel'    => [
                'display_name'  => 'Parcel Width',
                'property_type' => 'text',
                'description'   => 'Width of a parcel',
                'placeholder'   => '1',
            ],
            'product_height_per_parcel'   => [
                'display_name'  => 'Parcel Height',
                'property_type' => 'text',
                'description'   => 'Height of a parcel',
                'placeholder'   => '1',
            ],
        ]
    ]
);
