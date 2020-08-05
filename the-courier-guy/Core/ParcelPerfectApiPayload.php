<?php

/**
 * @author The Courier Guy
 * @package tcg/core
 * @version 1.0.1
 */
class ParcelPerfectApiPayload
{
    public $globalFactor = 50;

    /**
     * ParcelPerfectApiPayload constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param array $parameters
     *
     * @return array
     */
    private function getOriginPayload($parameters)
    {
        return [
            'accnum' => $parameters['account'],
            'reference' => '',
            'origperadd1' => $parameters['shopAddress1'],
            'origperadd2' => $parameters['shopAddress2'],
            'origperadd3' => $parameters['shopCity'],
            'origperadd4' => '',
            'origperphone' => $parameters['shopPhone'],
            'origpercell' => '',
            'origplace' => $parameters['shopArea'],
            'origtown' => $parameters['shopPlace'],
            'origpers' => $parameters['company_name'],
            'origpercontact' => $parameters['contact_name'],
            'origperpcode' => $parameters['shopPostalCode'],
            'notifyorigpers' => 1,
            'origperemail' => $parameters['shopEmail'],
        ];
    }

    /**
     * @param array $package
     *
     * @return array
     */
    private function getDestinationPayloadForQuote($package)
    {
        global $TCG_Plugin;
        $customer = WC()->customer;
        $customShippingProperties = $TCG_Plugin->getShippingCustomProperties();
        $address1 = $customer->get_billing_address_1();
        $address2 = $customer->get_billing_address_2();
        $city = $customer->get_billing_city();
        if (!empty($package['destination'])) {
            $destination = $package['destination'];
            if (!empty($package['destination'])) {
                $address1 = $destination['address'];
                $address2 = $destination['address_2'];
                $city = $destination['city'];
            }
        }
        $postCode = '';
        if (!empty($package['postcode'])) {
            $postCode = $package['postcode'];
        }

        return [
            'destperadd1' => $address1,
            'destperadd2' => $address2,
            'destperadd3' => $city,
            'destperadd4' => '',
            'destperphone' => $customer->get_billing_phone(),
            'destpercell' => '',
            'destplace' => $customShippingProperties['tcg_place_id'],
            'desttown' => $customShippingProperties['tcg_place_label'],
            'destpers'       => $customer->get_billing_company(),
            'destpercontact' => $customer->get_shipping_first_name() . ' ' . $customer->get_shipping_last_name(),
            'destperpcode' => $postCode,
            'notifydestpers' => 1,
            'destperemail' => $destination['email'] ?? '' ,
        ];
    }

    /**
     * @param WC_Order $order
     *
     * @return array
     */
    private function getDestinationPayloadForCollection($order)
    {
        return [
            'destperadd1' => $order->get_shipping_address_1(),
            'destperadd2' => $order->get_shipping_address_2(),
            'destperadd3' => $order->get_shipping_company(),
            'destperadd4' => '',
            'destperphone' => $order->get_billing_phone(),
            'destpercell' => '',
            'destplace' => $order->shipping_area,
            'desttown' => $order->shipping_place,
            'destpers' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
            'destpercontact' => $order->get_billing_phone(),
            'destperpcode' => $order->get_shipping_postcode(),
            'notifydestpers' => 1,
            'destperemail' => $order->get_billing_email(),
            'reference' => $order->get_id(),
        ];
    }

    /**
     * @return array
     */
    private function getInsurancePayloadForQuote()
    {
        global $TCG_Plugin;
        $result = [];
        $customShippingProperties = $TCG_Plugin->getShippingCustomProperties();
        $insurance = $customShippingProperties['tcg_insurance'];
        if ($insurance) {
            $result = [
                'insuranceflag' => 1,
                'declaredvalue' => WC()->cart->get_displayed_subtotal(),
            ];
        }

        return $result;
    }

    /**
     * @param WC_Order $order
     *
     * @return array
     */
    private function getInsurancePayloadForCollection($order)
    {
        $result = [];
        if (get_post_meta($order->get_id(), '_billing_insurance', true) || get_post_meta($order->get_id(), '_shipping_insurance', true)) {
            $result = [
                'insuranceflag' => 1,
            ];
        }

        return $result;
    }

    /**
     * @param array $parameters
     * @param array $items
     *
     * @return array
     */
    private function getContentsPayload($parameters, $items)
    {
        /**
         * Logic for getting shipping items / parcels
         * If there is more than one product use global settings
         * If there is only one product use product settings
         */
        $globalParcelItems = $globalParcelLength = $globalParcelWidth = $globalParcelHeight = 0;
        $globalParcelItems = (int) $parameters['product_quantity_per_parcel'] > 0 ? (int) $parameters['product_quantity_per_parcel'] : $globalParcelItems;

        if ( $globalParcelItems > 0 ) {
            if (
                (int) $parameters['product_length_per_parcel'] > 0
                && (int) $parameters['product_width_per_parcel'] > 0
                && (int) $parameters['product_height_per_parcel'] > 0
            ) {
                $globalParcelDim[0] = (int) $parameters['product_length_per_parcel'];
                $globalParcelDim[1] = (int) $parameters['product_width_per_parcel'];
                $globalParcelDim[2] = (int) $parameters['product_height_per_parcel'];
                sort($globalParcelDim);
            } else {
                // Set default dimensions for backward compatibility with earlier version of plugin
                $globalParcelDim[0] = 50;
                $globalParcelDim[1] = 50;
                $globalParcelDim[2] = 50;
            }
        }

        $maxProductQuantityPerParcel = null;
        $r1                          = [];
        $k                           = 1;
        $j                           = 0;

        $entry = [];
        switch ( true ) {
            case ( count($items) > 1 && $globalParcelItems > 1 ):
                // Use global default and override individual product settings
                // Based on Skype discussion 2020-07-09 15:58
                $globalItemsCount   = 0;
                $parcels            = [];
                $titems             = [];
                foreach ( $items as $item_id => $value ) {
                    $globalItemsCount += $value['quantity'];
                    $titems[]         = $value;
                }
                $numberParcels = (int) ceil($globalItemsCount / $globalParcelItems);
                for ( $i = 0; $i < $numberParcels; $i ++ ) {
                    $ritems = [];
                    foreach ( $titems as $titem ) {
                        if ( $titem ) {
                            $ritems[] = $titem;
                        }
                    }
                    $titems             = $ritems;
                    $parcelContentCount = 0;
                    for ( $j = 0; $j < count($titems); $j ++ ) {
                        $itemCount     = (int) $titems[ $j ]['quantity'];
                        $itemAvailable = $globalParcelItems - $parcelContentCount;
                        if ( $itemCount <= $itemAvailable ) {
                            // Add whole item to the parcel
                            $parcels[ $i ][]    = $titems[ $j ];
                            $parcelContentCount += (int) $titems[ $j ]['quantity'];
                            $titems[ $j ]       = null;
                        } else {
                            // Add part of item to parcel
                            $titemsadd               = $titems[ $j ];
                            $titemsadd['quantity']   = $itemAvailable;
                            $titemscarry             = $titems[ $j ];
                            $titemscarry['quantity'] = $titems[ $j ]['quantity'] - $itemAvailable;
                            $parcelContentCount      += (int) $itemAvailable;
                            $titems[ $j ]            = $titemscarry;
                            $parcels[ $i ][]         = $titemsadd;
                        }
                        if ( $parcelContentCount == $globalParcelItems ) {
                            break;
                        }
                    }
                }

                $j = 0;
                foreach ( $parcels as $parcel ) {
                    $j ++;
                    $itemsCount    = 0;
                    $entry         = [];
                    $entry['item'] = $j;
                    $slug          = '';
                    $mass          = 0;
                    foreach ( $parcel as $content ) {
                        $itemsCount = (int)$content['quantity'];
                        if ($content['variation_id'] === 0) {
                            $product = new WC_Product($content['product_id']);
                        } else {
                            $product = new WC_Product_Variation($content['variation_id']);
                        }
                        $prod       = get_post($content['product_id']);
                        $slug       .= $slug != '' ? '_' . $prod->post_title : $prod->post_title;
                        if ( $product->has_weight() ) {
                            $mass += $itemsCount * $product->get_weight();
                        } else {
                            $mass += 1.0;
                        }
                        if ( isset($globalParcelDim) ) {
                            $entry['dim1'] = $globalParcelDim[0];
                            $entry['dim2'] = $globalParcelDim[1];
                            $entry['dim3'] = $globalParcelDim[2];
                        } else {
                            if ( $product->has_dimensions() ) {
                                $dim[0] = (int) $product->get_width();
                                $dim[1] = (int) $product->get_height();
                                $dim[2] = (int) $product->get_length();
                                sort($dim);
                                $entry['dim1'] = $dim[0] * $this->globalFactor;
                                $entry['dim2'] = $dim[1] * $this->globalFactor;
                                $entry['dim3'] = $dim[2] * $this->globalFactor;
                            } elseif ( empty($entry['dim1']) ) {
                                $entry['dim1'] = 1;
                                $entry['dim2'] = 1;
                                $entry['dim3'] = 1;
                            }
                        }
                    }
                    $entry['description']    = $slug;
                    $entry['actmass'] = $mass;
                    $entry['pieces']  = 1;
                    $r1[]             = $entry;
                }
                break;
            case ( count($items) == 1 ):
            case ( count($items) > 1 && $globalParcelItems < 2 ):
                // Use product spec
                foreach ( $items as $item_id => $values ) {
                    $productQuantityPerParcel = (int) get_post_meta($values['product_id'], 'product_quantity_per_parcel', true);
                    switch ( $productQuantityPerParcel ) {
                        case 0:
                            $maxProductQuantityPerParcel = $globalParcelItems > 1 ? $globalParcelItems : 1;
                            break;
                        case 1:
                            $maxProductQuantityPerParcel = 1;
                            break;
                        default:
                            $maxProductQuantityPerParcel = $productQuantityPerParcel;
                    }

                    $itemsCount = $values['quantity'];
                    if ( $productQuantityPerParcel > 1 ) {
                        // Parcels based on product config
                        $productParcelLength = (int) get_post_meta($values['product_id'], 'product_length_per_parcel', true);
                        $productParcelWidth  = (int) get_post_meta($values['product_id'], 'product_width_per_parcel', true);
                        $productParcelHeight = (int) get_post_meta($values['product_id'], 'product_height_per_parcel', true);
                        if ( $productParcelLength > 0 && $productParcelWidth > 0 && $productParcelHeight > 0 ) {
                            $productParcelDim[0] = $productParcelLength;
                            $productParcelDim[1] = $productParcelWidth;
                            $productParcelDim[2] = $productParcelHeight;
                            sort($productParcelDim);
                        }

                        while ( $itemsCount >= 0 ) {
                            $j ++;
                            $parcelCount     = min($itemsCount, $maxProductQuantityPerParcel);
                            if($values['variation_id'] === 0) {
                                $product = new WC_Product($values['product_id']);
                            } else {
                                $product = new WC_Product_Variation($values['variation_id']);
                            }
                            $prod            = get_post($values['product_id']);
                            $slug            = $prod->post_title;
                            $entry = [];
                            $entry['item']   = $j;
                            $entry['description']   = $slug;
                            $entry['pieces'] = 1;
                            if ( isset($productParcelDim) ) {
                                $entry['dim1'] = $productParcelDim[0];
                                $entry['dim2'] = $productParcelDim[1];
                                $entry['dim3'] = $productParcelDim[2];
                            } else {
                                if ( $product->has_dimensions() ) {
                                    $dim[0] = (int) $product->get_width();
                                    $dim[1] = (int) $product->get_height();
                                    $dim[2] = (int) $product->get_length();
                                    sort($dim);
                                    $entry['dim1'] = $dim[0] * $this->globalFactor;
                                    $entry['dim2'] = $dim[1] * $this->globalFactor;
                                    $entry['dim3'] = $dim[2] * $this->globalFactor;
                                } elseif ( empty($entry['dim1']) ) {
                                    $entry['dim1'] = 1;
                                    $entry['dim2'] = 1;
                                    $entry['dim3'] = 1;
                                }
                            }

                            if ( $product->has_weight() ) {
                                $entry['actmass'] = $parcelCount * $product->get_weight();
                            } else {
                                $entry['actmass'] = 1.0;
                            }
                            $r1[]       = $entry;
                            $itemsCount -= $maxProductQuantityPerParcel;
                        }
                    } elseif ( $globalParcelItems > 1 && $productQuantityPerParcel != 1 ) {
                        // Parcels based on global config
                        while ( $itemsCount >= 0 ) {
                            $j ++;
                            $parcelCount     = min($itemsCount, $globalParcelItems);
                            if($values['variation_id'] === 0) {
                                $product = new WC_Product($values['product_id']);
                            } else {
                                $product = new WC_Product_Variation($values['variation_id']);
                            }
                            $prod            = get_post($values['product_id']);
                            $slug            = $prod->post_title;
                            $entry['item']   = $j;
                            $entry['description']   = $slug;
                            $entry['pieces'] = 1;
                            if ( isset($globalParcelDim) ) {
                                $entry['dim1'] = $globalParcelDim[0];
                                $entry['dim2'] = $globalParcelDim[1];
                                $entry['dim3'] = $globalParcelDim[2];
                            } else {
                                if ( $product->has_dimensions() ) {
                                    $dim[0] = (int) $product->get_width();
                                    $dim[1] = (int) $product->get_height();
                                    $dim[2] = (int) $product->get_length();
                                    sort($dim);
                                    $entry['dim1'] = $dim[0] * $this->globalFactor;
                                    $entry['dim2'] = $dim[1] * $this->globalFactor;
                                    $entry['dim3'] = $dim[2] * $this->globalFactor;
                                } elseif ( empty($entry['dim1']) ) {
                                    $entry['dim1'] = 1;
                                    $entry['dim2'] = 1;
                                    $entry['dim3'] = 1;
                                }
                            }

                            if ( $product->has_weight() ) {
                                $entry['actmass'] = $parcelCount * $product->get_weight();
                            } else {
                                $entry['actmass'] = 1.0;
                            }
                            $r1[]       = $entry;
                            $itemsCount -= $globalParcelItems;
                        }
                    } else {
                        // Only single items - no parcels
                        $j ++;
                        if($values['variation_id'] === 0) {
                            $product = new WC_Product($values['product_id']);
                        } else {
                            $product = new WC_Product_Variation(($values['variation_id']));
                        }
                        $prod            = get_post($values['product_id']);
                        $slug            = $prod->post_title;
                        $entry = [];
                        $entry['item']   = $j;
                        $entry['description']   = $slug;
                        $entry['pieces'] = $itemsCount;

                        if ( $product->has_dimensions() ) {
                            $dim['dim1'] = (int) $product->get_width();
                            $dim['dim2'] = (int) $product->get_height();
                            $dim['dim3'] = (int) $product->get_length();
                            sort($dim);
                            $entry['dim1'] = $dim[0];
                            $entry['dim2'] = $dim[1];
                            $entry['dim3'] = $dim[2];
                        } elseif ( empty($entry['dim1']) ) {
                            $entry['dim1'] = 1;
                            $entry['dim2'] = 1;
                            $entry['dim3'] = 1;
                        }
                        if ( $product->has_weight() ) {
                            $entry['actmass'] = $itemsCount * $product->get_weight();
                        } else {
                            $entry['actmass'] = 1.0;
                        }
                        $r1[] = $entry;
                    }
                    $k ++;
                }
                break;
        }

        return array_values($r1);
    }

    /**
     * @param array $package
     * @param array $parameters
     *
     * @return array
     */
    public function getQuotePayload($package, $parameters)
    {
        /** @var TCG_Plugin $TCG_Plugin */
        global $TCG_Plugin;
        $result = [];
        $customShippingProperties = $TCG_Plugin->getShippingCustomProperties();
        if (!empty($parameters) && !empty($customShippingProperties['tcg_place_id']) && !empty($customShippingProperties['tcg_place_label'])) {
            $originPayload = $this->getOriginPayload($parameters);
            $destinationPayload = $this->getDestinationPayloadForQuote($package);
            $insurancePayload = $this->getInsurancePayloadForQuote();
            $detailsPayload = array_merge($originPayload, ['reference' => $destinationPayload['destpers'],], $destinationPayload, $insurancePayload);
            $result['details'] = $detailsPayload;
            $contentsPayload = $this->getContentsPayload($parameters, $package['contents']);
            $result['contents'] = $contentsPayload;
        }

        return $result;
    }

    /**
     * @param WC_Order $order
     * @param array $shippingItem
     * @param array $parameters
     *
     * @return array
     */
    public function getCollectionPayload($order, $shippingItem, $parameters)
    {
        $result = [];
        if (!empty($order) && !empty($parameters)) {
            $originPayload = $this->getOriginPayload($parameters);
            $originPayload['notes'] = $order->get_customer_note();
            $destinationPayload = $this->getDestinationPayloadForCollection($order);
            $insurancePayload = $this->getInsurancePayloadForCollection($order);
            $detailsPayload = array_merge($originPayload, $destinationPayload, $insurancePayload);
            $detailsPayload['service'] = $this->getServiceIdentifierFromShippingItem($shippingItem);
            $detailsPayload['collectiondate'] = current_time('d.m.Y');
            $detailsPayload['starttime'] = current_time('H:i:s');
            $detailsPayload['endtime'] = '18:00:00';
            $result['details'] = $detailsPayload;
            $orderItems = $order->get_items('line_item');
            $contentsPayload = $this->getContentsPayload($parameters, $orderItems);
            $result['contents'] = $contentsPayload;
        }

        return $result;
    }

    /**
     * @param array $shippingItem
     *
     * @return mixed
     */
    private function getServiceIdentifierFromShippingItem($shippingItem)
    {
        $method = $shippingItem['method_id'];
        $methodParts = explode(':', $method);

        return $methodParts[1];
    }
}
