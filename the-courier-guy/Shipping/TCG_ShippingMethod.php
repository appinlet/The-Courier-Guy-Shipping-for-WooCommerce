<?php

use Dompdf\Adapter\CPDF;

/**
 * @author The Courier Guy
 * @package tcg/shipping
 * @version 1.0.0
 */
class TCG_Shipping_Method extends WC_Shipping_Method
{
    /**
     * TCG_Shipping_Method constructor.
     *
     * @param int $instance_id
     */
    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);
        /*
         * These variables must be overridden on classes that extend WC_Shipping_Method.
         */
        $this->id = 'the_courier_guy';
        $title = 'The Courier Guy';
        if (!empty($instance_id)) {
            $title = $this->get_instance_option('title', 'The Courier Guy');
        }
        $this->title = $title;
        $this->supports = [
            'shipping-zones',
            'instance-settings',
        ];
        $this->tax_status = false;
        $this->method_title = __('The Courier Guy');
        $this->method_description = __('The Official Courier Guy shipping method.');
        $this->overrideFormFieldsVariable();
        //This action hook must be added to trigger the 'process_admin_options' method on parent class WC_Shipping_Method.
        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
        add_filter('woocommerce_shipping_' . $this->id . '_instance_settings_values', [$this, 'setParcelPerfectApiCredentials'], 10, 2);
    }

    /**
     * @return array
     */
    public function getShippingProperties()
    {
        return $this->instance_settings;
    }

    /**
     * @param array $settings
     * @param mixed $obj
     *
     * @return mixed
     */
    public function setParcelPerfectApiCredentials($settings, $obj)
    {
        update_option('tcg_username', $settings['username']);
        update_option('tcg_password', $settings['password']);

        return $settings;
    }

    /**
     * Called to calculate shipping rates for this shipping method. Rates can be added using the add_rate() method.
     * This method must be overridden as it is called by the parent class WC_Shipping_Method.
     *
     * @param array $package Shipping package.
     *
     * @uses WC_Shipping_Method::add_rate()
     */
    public function calculate_shipping($package = [])
    {
        global $TCG_Plugin;
        $parameters = $this->getShippingProperties();
        $result = $TCG_Plugin->getQuote($package, $parameters);
        if (!empty($result) && !empty($result[0]) && !empty($result[0]['quoteno'])) {
            $quoteNumber = $result[0]['quoteno'];
            $rates = $result[0]['rates'];
            if (!empty($rates)) {
                $addedRates = $this->addRates($rates, $package);
                if (!empty($addedRates)) {
                    $TCG_Plugin->setService($quoteNumber);
                }
                //The id variable must be changed back, as this is changed in addRate method on this class.
                //@see TCG_Shipping_Method::addRate()
                //@todo This logic is legacy from an older version of the plugin, there must be a better way, no time now.
                $this->id = 'the_courier_guy';
            }
        }
    }

    /**
     * @param array $rates
     *
     * @return array
     */
    private function sortRatesByTotalValueAscending($rates)
    {
        if (is_array($rates)) {
            usort($rates, function ($x, $y) {
                return $x['total'] > $y['total'];
            });
        }

        return $rates;
    }

    /**
     * @param array $rates
     * @param array $package
     *
     * @return array
     */
    private function addRates($rates, $package)
    {
        $addedRates = [];
        $rates = $this->filterRates($rates);
        $rates = $this->sortRatesByTotalValueAscending($rates);
        $percentageMarkup = $this->get_instance_option('percentage_markup');
        $priceRateOverrides = json_decode($this->get_instance_option('price_rate_override_per_service'), true);
        $labelOverrides = json_decode($this->get_instance_option('label_override_per_service'), true);
        if (!empty($rates) && is_array($rates)) {
            $finalRates = [];
            foreach ($rates as $rate) {
                $addedRates[] = $rate;
                $finalRates[] = $this->addRate($rate, $package, $percentageMarkup, $priceRateOverrides, $labelOverrides);
            }
        }

        $hasFreeShipping = false;
        foreach ( $finalRates as $finalRate ) {
            if ( $finalRate['free'] ) {
                $hasFreeShipping = true;
            }
        }
        foreach ( $finalRates as $finalRate ) {
            if ( $hasFreeShipping && $finalRate['free'] ) {
                $this->add_rate($finalRate['rate']);
            } elseif( !$hasFreeShipping ) {
                $this->add_rate($finalRate['rate']);
            }
        }

        return $addedRates;
    }

    private function filterRates($rates)
    {
        $excludes = $this->get_instance_option('excludes');
        if (empty($excludes)) {
            $excludes = [];
        }
        $filteredRates = array_filter($rates, function ($rate) use ($excludes) {
            return (!in_array($rate['service'], $excludes));
        });
        $lofServices = [];
        $lofOnlyService = $this->get_instance_option('lof_only_service');
        if ((!empty($lofOnlyService) && $lofOnlyService == 'yes')) {
            array_walk($filteredRates, function ($rate) use (&$lofServices) {
                if (empty($lofServices) && in_array('LOF', $rate)) {
                    $lofServices[] = $rate;
                }
            });
        }
        if (!empty($lofServices)) {
            $filteredRates = $lofServices;
        }

        return $filteredRates;
    }

    /**
     * @param array $rate
     * @param array $package
     * @param int $percentageMarkup
     * @param array $priceRateOverrides
     * @param array $labelOverrides
     */
    private function addRate($rate, $package, $percentageMarkup, $priceRateOverrides, $labelOverrides)
    {
        // Get tax rates
        $tax     = WC_Tax::get_rates_for_tax_class('');
        $taxRate = 0.0;
        if ( $this->get_instance_option('tax_status') === 'taxable' ) {
            $taxRate = $tax ? $tax[1]->tax_rate / 100.0 : 0.0;
        }

        // Free shipping global settings
        $free_ship = $this->get_instance_option('free_shipping');
        $amount_for_free_shipping = $this->get_instance_option('amount_for_free_shipping');
        $rates_for_free_shipping = $this->get_instance_option('rates_for_free_shipping');
        $percentage_for_free_shipping = 0.0;
        if ( ! empty($this->get_instance_option('percentage_for_free_shipping')) ) {
            $percentage_for_free_shipping = (float) $this->get_instance_option('percentage_for_free_shipping') / 100.0;
        }

        // Free shipping product settings
        $product_free_shipping = $this->isProductFreeShipping($package);

        // Does qualify for free shipping based on total value
        $global_amount_free_shipping = false;
        if ( $package['cart_subtotal'] >= $amount_for_free_shipping ) {
            $global_amount_free_shipping = true;
        }

        $rateTotal = $rate['total'];
        if ($rateTotal > 0) {
            $rateService = $rate['service'];
            $totalPrice = $rateTotal;
            if (!empty($priceRateOverrides[$rateService])) {
                $totalPrice = number_format($priceRateOverrides[$rateService], 2, '.', '');
            } else {
                if (!empty($percentageMarkup)) {
                    $totalPrice = ($rateTotal + ($rateTotal * $percentageMarkup / 100));
                    $totalPrice = number_format($totalPrice, 2, '.', '');
                }
            }
            $rateLabel = $rate['name'];
            if (!empty($labelOverrides[$rateService])) {
                $rateLabel = $labelOverrides[$rateService];
            }

            // Does qualify for free shipping based on percentage
            $global_percentage_free_shipping = false;
            if ( $percentage_for_free_shipping > 0 && (float) ( $totalPrice / $package['cart_subtotal'] ) >= $percentage_for_free_shipping ) {
                $global_percentage_free_shipping = true;
            }

            $cost  = round((float)$totalPrice / ( 1 + $taxRate ), 2);
            $taxes = $totalPrice - $cost;

            $shippingMethodId = 'the_courier_guy' . ':' . $rateService . ':' . $this->instance_id;
            $args = [
                'id' => $shippingMethodId,
                'label' => $rateLabel,
                'cost'     => $cost,
                'taxes'    => [1 => $taxes],
                'calc_tax' => 'per_order',
                'package' => $package
            ];

            //Check if free shipping is required
            if ( $free_ship == 'yes' ) {
                global $woocommerce;

                if ( ( $product_free_shipping || $global_amount_free_shipping || $global_percentage_free_shipping ) && in_array($rate['service'], $rates_for_free_shipping) ) {
                    $args['label'] = $rateLabel . ': Free Shipping';
                    $args['cost']  = 0;

            //The id variable must be changed, as this is used in the 'add_rate' method on the parent class WC_Shipping_Method.
            //@todo This logic is legacy from an older version of the plugin, there must be a better way, no time now.
            $this->id = $shippingMethodId;

                    return array(
                        'id'   => $this->id,
                        'free' => true,
                        'rate' => $args,
                    );
                    $this->add_rate($args);
                } elseif ( ( $product_free_shipping || $global_amount_free_shipping || $global_percentage_free_shipping ) && ! in_array($rate['service'], $rates_for_free_shipping) ) {
                    $this->id = $shippingMethodId;

                    return array(
                        'id'   => $this->id,
                        'free' => false,
                        'rate' => $args,
                    );
            $this->add_rate($args);
                } elseif ( ! ( $product_free_shipping || $global_amount_free_shipping || $global_percentage_free_shipping ) ) {
                    $this->id = $shippingMethodId;

                    return array(
                        'id'   => $this->id,
                        'free' => false,
                        'rate' => $args,
                    );
                    $this->add_rate($args);
                }
            } else {
                $this->id = $shippingMethodId;

                $this->add_rate($args);

                return array(
                    'id'   => $this->id,
                    'free' => false,
                    'rate' => $args,
                );
            }
        }
    }

    private function isProductFreeShipping( $package )
    {
        // Check product package for free shipping products
        if ( $this->get_instance_option('free_shipping') == 'yes' ) {
            foreach ( $package['contents'] as $pack ) {
                $pfs = get_post_meta($pack['product_id'], 'product_free_shipping');
                if ( isset($pfs) && count($pfs) > 0 && $pfs[0] == 'on' ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     *
     */
    private function overrideFormFieldsVariable()
    {
        //@todo The contents of this method is legacy code from an older version of the plugin.
        $fields = [
            'title' => [
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'label' => __('Method Title', 'woocommerce'),
                'default' => 'The Courier Guy'
            ],
            'account' => [
                'title' => __('Account number', 'woocommerce'),
                'type' => 'text',
                'description' => __('The account number supplied by The Courier Guy for integration purposes.', 'woocommerce'),
                'default' => __('', 'woocommerce')
            ],
            'tax_status'                            => [
                'title'       => __('Tax status', 'woocommerce'),
                'type'        => 'select',
                'options'     => [ 'taxable' => 'Taxable', 'none' => 'None' ],
                'description' => __('VAT applies or not', 'woocommerce'),
                'default'     => __('taxable', 'woocommerce')
            ],
            'username' => [
                'title' => __('Username', 'woocommerce'),
                'type' => 'text',
                'description' => __('The username supplied by The Courier Guy for integration purposes.', 'woocommerce'),
                'default' => __('', 'woocommerce')
            ],
            'password' => [
                'title' => __('Password', 'woocommerce'),
                'type' => 'password',
                'description' => __('The password supplied by The Courier Guy for integration purposes.', 'woocommerce'),
                'default' => __('', 'woocommerce')
            ],
            'company_name' => [
                'title' => __('Company Name', 'woocommerce'),
                'type' => 'text',
                'description' => __('The name of your company.', 'woocommerce'),
                'default' => '',
            ],
            'contact_name' => [
                'title' => __('Contact Name', 'woocommerce'),
                'type' => 'text',
                'description' => __('The name of a contact at your company.', 'woocommerce'),
                'default' => '',
            ],
            'shopAddress1' => [
                'title' => __('Shop Address1', 'woocommerce'),
                'type' => 'text',
                'description' => __('The address used to calculate shipping, this is considered the collection point for the parcels being shipping.', 'woocommerce'),
                'default' => '',
            ],
            'shopAddress2' => [
                'title' => __('Shop Address2', 'woocommerce'),
                'type' => 'text',
                'description' => __('The address used to calculate shipping, this is considered the collection point for the parcels being shipping.', 'woocommerce'),
                'default' => '',
            ],
            'shopPostalCode' => [
                'title' => __('Shop Postal Code', 'woocommerce'),
                'type' => 'text',
                'description' => __('The address used to calculate shipping, this is considered the collection point for the parcels being shipping.', 'woocommerce'),
                'default' => '',
            ],
            'shopPhone' => [
                'title' => __('Shop Phone', 'woocommerce'),
                'type' => 'text',
                'description' => __('The telephone number to contact the shop, this may be used by the courier.', 'woocommerce'),
                'default' => '',
            ],
            'shopArea' => [
                'title' => __('Shop Area / Suburb', 'woocommerce'),
                'type' => 'tcg_shop_area',
                'description' => __('The suburb used to calculate shipping, this is considered the collection point for the parcels being shipping.', 'woocommerce') . '<br/>' . __('It is important to note that you will need to save the Shipping Method, with the correct \'Account number\', \'Username\' and \'Password\' in order for this setting to auto-complete and populate the \'Shop Area / Suburb\' options from The Courier Guy.', 'woocommerce'),
                'default' => '',
                'class' => 'tcg-suburb-field',
            ],
            'shopPlace' => [
                'type' => 'hidden',
                'default' => '',
            ],
            'shopCity' => [
                'title' => __('Shop Town / City', 'woocommerce'),
                'type' => 'text',
                'description' => __('The suburb used to calculate shipping, this is considered the collection point for the parcels being shipping. This is the town/city used as the origin in the waybill.', 'woocommerce'),
                'default' => '',
            ],
            'excludes' => [
                'title' => __('Exclude Rates', 'woocommerce'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'width: 450px;',
                'description' => __('Select the rates that you wish to always be excluded from the available rates on the checkout page.', 'woocommerce'),
                'default' => '',
                'options' => $this->getRateOptions(),
                'custom_attributes' => [
                    'data-placeholder' => __('Select the rates you would like to exclude', 'woocommerce')
                ]
            ],
            'percentage_markup' => [
                'title' => __('Percentage Markup', 'woocommerce'),
                'type' => 'tcg_percentage',
                'description' => __('Percentage markup to be applied to each quote.', 'woocommerce'),
                'default' => ''
            ],
            'automatically_submit_collection_order' => [
                'title' => __('Automatically Submit Collection Order', 'woocommerce'),
                'type' => 'checkbox',
                'description' => __('This will determine whether or not the collection order is automatically submitted to The Courier Guy after checkout completion.', 'woocommerce'),
                'default' => ''
            ],
            'south_africa_only' => [
                'title' => __('Ship internationally using other carriers', 'woocommerce'),
                'type' => 'checkbox',
                'description' => __('When enabled, this will hide The Courier Guy \'Suburb/Area\' when changing countries on the checkout page and will not make the field \'required\'. If unsure, leave this disabled.', 'woocommerce'),
                'default' => 'no'
            ],
            'lof_only_service' => [
                'title' => __('LOF Only Service', 'woocommerce'),
                'type' => 'checkbox',
                'description' => __('This will determine whether to display ONLY the \'LOF: Local Overnight Flyer\' service option on checkout, if the response from The Courier Guy quote contains the \'LOF: Local Overnight Flyer\' service.', 'woocommerce'),
                'default' => ''
            ],
            'price_rate_override_per_service' => [
                'title' => __('Price Rate Override Per Service', 'woocommerce'),
                'type' => 'tcg_override_per_service',
                'description' => __('These prices will override The Courier Guy rates per service.', 'woocommerce') . '<br />' . __('Select a service to add or remove price rate override.', 'woocommerce') . '<br />' . __('Services with an overridden price will not use the \'Percentage Markup\' setting.', 'woocommerce'),
                'options' => $this->getRateOptions(),
                'default' => '',
                'class' => 'tcg-override-per-service',
            ],
            'label_override_per_service' => [
                'title' => __('Label Override Per Service', 'woocommerce'),
                'type' => 'tcg_override_per_service',
                'description' => __('These labels will override The Courier Guy labels per service.', 'woocommerce') . '<br />' . __('Select a service to add or remove label override.', 'woocommerce'),
                'options' => $this->getRateOptions(),
                'default' => '',
                'class' => 'tcg-override-per-service',
            ],
            'product_quantity_per_parcel' => [
                'title' => __('Product Quantity per Parcel', 'woocommerce'),
                'type' => 'tcg_text_with_disclaimer',
                'description' => __('This will allow for a single parcel to be allotted per the configured \'Product Quantity per Parcel\' value.', 'woocommerce'),
                'default' => '',
                'placeholder' => 'none',
                'disclaimer_description' => 'I accept that altering the \'Product Quantity Per Parcel\' setting may cause quotes to be inaccurate and The Courier Guy will not be responsible for these inaccurate quotes.'
            ],
            'product_length_per_parcel' => [
                'title' => __('Length of Global Parcel', 'woocommerce'),
                'type' => 'text',
                'description' => __('Length of the global parcel - required if Product Quantity per Parcel is set', 'woocommerce'),
                'default' => '50',
                'placeholder' => 'none',
            ],
            'product_width_per_parcel' => [
                'title' => __('Width of Global Parcel', 'woocommerce'),
                'type' => 'text',
                'description' => __('Width of the global parcel - required if Product Quantity per Parcel is set', 'woocommerce'),
                'default' => '50',
                'placeholder' => 'none',
            ],
            'product_height_per_parcel' => [
                'title' => __('Height of Global Parcel', 'woocommerce'),
                'type' => 'text',
                'description' => __('Height of the global parcel - required if Product Quantity per Parcel is set', 'woocommerce'),
                'default' => '50',
                'placeholder' => 'none',
            ],
            'order_waybill_pdf_paper_size' => [
                'title' => __('Waybill PDF Paper Size', 'woocommerce'),
                'type' => 'tcg_pdf_paper_size',
                'description' => __('This is the paper size used when generating Waybill print PDF.', 'woocommerce') . '<br />' . __('This setting is used in conjunction with a custom Waybill print PDF template.', 'woocommerce') . '<br />' . __('The Courier Guy cannot guarantee that the generic Waybill print PDF template will look good for all sizes.', 'woocommerce'),
                'default' => 'a4',
                'placeholder' => 'a4',
            ],
            'order_waybill_pdf_copy_quantity' => [
                'title' => __('Waybill PDF Copy Quantity', 'woocommerce'),
                'type' => 'number',
                'description' => __('This is the number of copies generated per Waybill print PDF.', 'woocommerce') . '<br />' . __('This setting is used in conjunction with a custom Waybill print PDF template.', 'woocommerce') . '<br />' . __('The Courier Guy cannot guarantee that the generic Waybill print PDF template will look good for all copy amounts.', 'woocommerce'),
                'default' => '4',
                'placeholder' => '4',
            ],
            'billing_insurance'                         => [
                'title'       => __('Enable shipping insurance ', 'woocommerce'),
                'type'        => 'checkbox',
                'description' => __('This will enable the shipping insurance field on the checkout page', 'woocommerce'),
                'default'     => 'no'
            ],
            'free_shipping' => [
                'title' => __('Enable free shipping ', 'woocommerce'),
                'type' => 'checkbox',
                'description' => __('This will enable free shipping over a specified amount', 'woocommerce'),
                'default' => ''
            ],
            'rates_for_free_shipping' => [
                'title' => __('Rates for free Shipping', 'woocommerce'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'width: 450px;',
                'description' => __('Select the rates that you wish to enable for free shipping', 'woocommerce'),
                'default' => '',
                'options' => $this->getRateOptions(),
                'custom_attributes' => [
                    'data-placeholder' => __('Select the rates you would like to enable for free shipping', 'woocommerce')
                ]
            ],
            'amount_for_free_shipping' => [
                'title' => __('Amount for free Shipping', 'woocommerce'),
                'type' => 'number',
                'description' => __('Enter the amount for free shipping when enabled', 'woocommerce'),
                'default' => '1000',
                'custom_attributes' => [
                    'min' => '0'
                ]

            ],
            'percentage_for_free_shipping'          => [
                'title'             => __('Percentage for free Shipping', 'woocommerce'),
                'type'              => 'number',
                'description'       => __('Enter the percentage (shipping of product) that qualifies for free shipping when enabled. Zero to disable', 'woocommerce'),
                'default'           => '0',
                'custom_attributes' => [
                    'min' => '0'
                ]
            ],
            'product_free_shipping'                 => [
                'title'       => __('Enable free shipping from product setting', 'woocommerce'),
                'type'        => 'checkbox',
                'description' => __('This will enable free shipping if the product is included in the basket', 'woocommerce'),
                'default'     => ''
            ],
            'Suburb_location' => [
                'title' => __('Suburb location', 'woocommerce'),
                'type' => 'select',
                'css' => 'width: 450px;',
                'description' => __('Select the location of the Suburb field on checkout form.', 'woocommerce').'<br />'.__('The Suburb field will be displayed after the selected location.', 'woocommerce'),
                'default' => '',
                'options' => $this->getSuburbLocationOptions()
            ],
            'Suburb_title' => [
                'title' => __('Suburb title', 'woocommerce'),
                'type' => 'text',
                'description' => __('Enter the title for the Suburb field.', 'woocommerce').'<br />'.__('This custom Suburb Title will be displayed on the checkout form.', 'woocommerce'),
                'default' => 'Area/Suburb',

            ],


        ];
        $this->instance_form_fields = $fields;
    }

    /**
     * @return array|mixed|object
     */
    private function getRateOptions()
    {
        //@todo The contents of this method is legacy code from an older version of the plugin.
        $rateOptions = new stdClass();
        $rateOptions->AIR = "AIR: Airfreight";
        $rateOptions->ECO = "ECO: Economy (Domestic Road Freight)";
        $rateOptions->LLS = "LLS: Local Late Sameday";
        $rateOptions->LOF = "LOF: Local Overnight Flyer";
        $rateOptions->LOX = "LOX: Local Overnight Parcels";
        $rateOptions->LSE = "LSE: Local Sameday Economy";
        $rateOptions->LSF = "LSF: Local Sameday Flyer";
        $rateOptions->LSX = "LSX: Local Sameday Express";
        $rateOptions->NFS = "NFS National Flyer Service";
        $rateOptions->OVN = "OVN: Overnight Courier";
        $rateOptions->SDX = "SDX: Express Sameday";

        return $rateOptions;
    }
    /**
     *getSuburbLocationOptions() -> returns an array of locations from the checkout form
     * @return array|mixed|object
     */
    private function getSuburbLocationOptions()
    {
        return json_decode('{"_country":"Country "," _state":"Province","_city":"City/Town","_address_2":"Street Address","_postcode":"Postcode/ZIP"}');
    }

    /**
     * This method is called to build the UI for custom shipping setting of type 'tcg_override_per_service'.
     * This method must be overridden as it is called by the parent class WC_Settings_API.
     *
     * @param $key
     * @param $data
     *
     * @return string
     * @uses WC_Settings_API::get_custom_attribute_html()
     * @uses WC_Shipping_Method::get_option()
     * @uses WC_Settings_API::get_field_key()
     * @uses WC_Settings_API::get_tooltip_html()
     * @uses WC_Settings_API::get_description_html()
     */
    public function generate_tcg_override_per_service_html($key, $data)
    {
        $field_key = $this->get_field_key($key);
        $defaults = array(
            'title' => '',
            'disabled' => false,
            'class' => '',
            'css' => '',
            'placeholder' => '',
            'type' => 'text',
            'desc_tip' => false,
            'description' => '',
            'custom_attributes' => array(),
            'options' => array(),
        );
        $data = wp_parse_args($data, $defaults);
        $overrideValue = $this->get_option($key);
        $overrideValues = json_decode($overrideValue, true);
        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($field_key); ?>_select"><?php echo wp_kses_post($data['title']); ?><?php echo $this->get_tooltip_html($data); // WPCS: XSS ok.
                    ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span></legend>
                    <select class="select <?php echo esc_attr($data['class']); ?>" style="<?php echo esc_attr($data['css']); ?>" <?php disabled($data['disabled'], true); ?> <?php echo $this->get_custom_attribute_html($data); // WPCS: XSS ok.
                    ?>>
                        <option value="">Select a Service</option>
                        <?php
                        $prefix = ' - ';
                        if ($field_key == 'woocommerce_the_courier_guy_price_rate_override_per_service') {
                            $prefix = ' - R ';
                        }
                        ?>
                        <?php foreach ((array)$data['options'] as $option_key => $option_value) : ?>
                            <option value="<?php echo esc_attr($option_key); ?>" data-service-label="<?php echo esc_attr($option_value); ?>"><?php echo esc_attr($option_value); ?><?= (!empty($overrideValues[$option_key])) ? $prefix . $overrideValues[$option_key] : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php foreach ((array)$data['options'] as $option_key => $option_value) : ?>
                        <span style="display:none;" class="<?php echo esc_attr($data['class']); ?>-span-<?= $option_key; ?>">
                            <?php
                            $class = '';
                            $style = '';
                            if ($field_key == 'woocommerce_the_courier_guy_price_rate_override_per_service') {
                                $class = 'wc_input_price ';
                                $style = ' style="width: 90px !important;" ';
                                ?>
                                <span style="position:relative; top:8px; padding:0 0 0 10px;">R </span>
                                <?php
                            }
                            ?>
                            <input data-service-id="<?php echo esc_attr($option_key); ?>" class="<?= $class; ?> input-text regular-input <?php echo esc_attr($data['class']); ?>-input" type="text"<?= $style; ?> value="<?= isset($overrideValues[$option_key]) ? $overrideValues[$option_key] : ''; ?>"/>
                        </span>
                    <?php endforeach; ?>
                    <?php echo $this->get_description_html($data); // WPCS: XSS ok.
                    ?>
                    <input type="hidden" name="<?php echo esc_attr($field_key); ?>" value="<?= esc_attr($overrideValue); ?>"/>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * This method is called to build the UI for custom shipping setting of type 'tcg_shop_area'.
     * This method must be overridden as it is called by the parent class WC_Settings_API.
     * @uses WC_Settings_API::get_field_key()
     * @uses WC_Settings_API::get_tooltip_html()
     * @uses WC_Settings_API::get_description_html()
     * @uses WC_Settings_API::get_custom_attribute_html()
     * @uses WC_Shipping_Method::get_option()
     * @param $key
     * @param $data
     * @return string
     */
    public function generate_tcg_pdf_paper_size_html($key, $data)
    {
        //@todo The contents of this method is legacy code from an older version of the plugin.
        $field_key = $this->get_field_key($key);
        $defaults = [
            'title' => '',
            'disabled' => false,
            'class' => '',
            'css' => '',
            'placeholder' => '',
            'type' => 'text',
            'desc_tip' => false,
            'description' => '',
            'custom_attributes' => [],
        ];
        $data = wp_parse_args($data, $defaults);
        $data['options'] = array_keys(CPDF::$PAPER_SIZES);
        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <?php echo $this->get_tooltip_html($data); ?>
                <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span>
                    </legend>
                    <select class="select <?php echo esc_attr($data['class']); ?>" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" style="<?php echo esc_attr($data['css']); ?>" <?php disabled($data['disabled'], true); ?> <?php echo $this->get_custom_attribute_html($data); ?>>
                        <?php foreach ((array)$data['options'] as $option_key => $option_value) : ?>
                            <option value="<?php echo esc_attr($option_value); ?>" <?php selected($option_value, esc_attr($this->get_option($key))); ?>><?php echo esc_attr($option_value); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php echo $this->get_description_html($data); ?>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * This method is called to build the UI for custom shipping setting of type 'tcg_shop_area'.
     * This method must be overridden as it is called by the parent class WC_Settings_API.
     * @uses WC_Settings_API::get_field_key()
     * @uses WC_Settings_API::get_tooltip_html()
     * @uses WC_Settings_API::get_description_html()
     * @uses WC_Settings_API::get_custom_attribute_html()
     * @uses WC_Shipping_Method::get_option()
     * @param $key
     * @param $data
     * @return string
     */
    public function generate_tcg_shop_area_html($key, $data)
    {
        //@todo The contents of this method is legacy code from an older version of the plugin.
        $field_key = $this->get_field_key($key);
        $defaults = [
            'title' => '',
            'disabled' => false,
            'class' => '',
            'css' => '',
            'placeholder' => '',
            'type' => 'text',
            'desc_tip' => false,
            'description' => '',
            'custom_attributes' => [],
            'options' => [],
        ];
        $data = wp_parse_args($data, $defaults);
        $name = esc_attr($this->get_option('shopPlace'));
        $id = esc_attr($this->get_option($key));
        $data['options'] = [
            $id => $name
        ];
        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <?php echo $this->get_tooltip_html($data); ?>
                <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span>
                    </legend>
                    <select class="select <?php echo esc_attr($data['class']); ?>" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" style="<?php echo esc_attr($data['css']); ?>" <?php disabled($data['disabled'], true); ?> <?php echo $this->get_custom_attribute_html($data); ?>>
                        <?php foreach ((array)$data['options'] as $option_key => $option_value) : ?>
                            <option value="<?php echo esc_attr($option_key); ?>" <?php selected($option_key, esc_attr($this->get_option($key))); ?>><?php echo esc_attr($option_value); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php echo $this->get_description_html($data); ?>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * This method is called to build the UI for custom shipping setting of type 'tcg_percentage'.
     * This method must be overridden as it is called by the parent class WC_Settings_API.
     * @uses WC_Settings_API::get_field_key()
     * @uses WC_Settings_API::get_tooltip_html()
     * @uses WC_Settings_API::get_description_html()
     * @uses WC_Settings_API::get_custom_attribute_html()
     * @uses WC_Shipping_Method::get_option()
     * @param $key
     * @param $data
     * @return string
     */
    public function generate_tcg_percentage_html($key, $data)
    {
        //@todo The contents of this method is legacy code from an older version of the plugin.
        $field_key = $this->get_field_key($key);
        $defaults = [
            'title' => '',
            'disabled' => false,
            'class' => '',
            'css' => '',
            'placeholder' => '',
            'type' => 'text',
            'desc_tip' => false,
            'description' => '',
            'custom_attributes' => [],
        ];
        $data = wp_parse_args($data, $defaults);
        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?><?php echo $this->get_tooltip_html($data); // WPCS: XSS ok.
                    ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span>
                    </legend>
                    <input class="wc_input_decimal input-text regular-input <?php echo esc_attr($data['class']); ?>" type="text" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" style="<?php echo esc_attr($data['css']); ?> width: 50px !important;" value="<?php echo esc_attr(wc_format_localized_decimal($this->get_option($key))); ?>" placeholder="<?php echo esc_attr($data['placeholder']); ?>" <?php disabled($data['disabled'], true); ?> <?php echo $this->get_custom_attribute_html($data); // WPCS: XSS ok.
                    ?> /><span style="vertical-align: -webkit-baseline-middle;padding: 6px;">%</span>
                    <?php echo $this->get_description_html($data); // WPCS: XSS ok.
                    ?>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * This method is called to validate the custom shipping setting of type 'tcg_percentage'.
     * This method must be overridden as it is called by the parent class WC_Settings_API.
     * @param $key
     * @param $value
     * @return string
     */
    public function validate_tcg_percentage_field($key, $value)
    {
        $value = is_null($value) ? '' : $value;
        return ('' === $value) ? '' : wc_format_decimal(trim(stripslashes($value)));
    }

    /**
     * This method is called to build the UI for custom shipping setting of type 'tcg_text_with_disclaimer'.
     * This method must be overridden as it is called by the parent class WC_Settings_API.
     * @uses WC_Settings_API::get_field_key()
     * @uses WC_Settings_API::get_tooltip_html()
     * @uses WC_Settings_API::get_description_html()
     * @uses WC_Settings_API::get_custom_attribute_html()
     * @uses WC_Shipping_Method::get_option()
     *
     * @param $key
     * @param $data
     * @return string
     */
    public function generate_tcg_text_with_disclaimer_html($key, $data)
    {
        //@todo The contents of this method is legacy code from an older version of the plugin.
        $field_key = $this->get_field_key($key);
        $defaults = [
            'title' => '',
            'disabled' => false,
            'class' => '',
            'css' => '',
            'placeholder' => '',
            'type' => 'text',
            'desc_tip' => false,
            'description' => '',
            'custom_attributes' => [],
        ];
        $data = wp_parse_args($data, $defaults);
        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?><?php echo $this->get_tooltip_html($data); // WPCS: XSS ok.
                    ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post($data['title']); ?></span>
                    </legend>
                    <input class="wc_input_decimal wc_input_tcg_text_with_disclaimer input-text regular-input <?php echo esc_attr($data['class']); ?>" type="text" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" style="<?php echo esc_attr($data['css']); ?> width: 50px !important;" value="<?php echo esc_attr(wc_format_localized_decimal($this->get_option($key))); ?>" placeholder="<?php echo esc_attr($data['placeholder']); ?>" <?php disabled($data['disabled'], true); ?> <?php echo $this->get_custom_attribute_html($data); // WPCS: XSS ok.
                    ?> />
                    <?php echo $this->get_description_html($data); // WPCS: XSS ok.
                    ?>
                    <input class="<?php echo esc_attr($data['class']); ?>" type="checkbox" name="<?php echo esc_attr($field_key); ?>_disclaimer" id="<?php echo esc_attr($field_key); ?>_disclaimer" value="1" <?php checked($this->get_option($key), 'yes'); ?> />
                    <label for="<?php echo esc_attr($field_key); ?>_disclaimer"><?php echo wp_kses_post($data['disclaimer_description']); ?></label>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }
}
