<?php

/**
 * @author  Clint Lynch
 * @package tcg/core
 * @version 1.0.0
 */
class ParcelPerfectApiPayload
{

    /**
     * ParcelPerfectApiPayload constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param array $parameters
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
        ];
    }

    /**
     * @param array $package
     * @return array
     */
    private function getDestinationPayloadForQuote($package)
    {
        global $TCG_Plugin;
        $customer = WC()->customer;
        $customShippingProperties = $TCG_Plugin->getShippingCustomProperties();
        $address1 = '';
        $address2 = '';
        $city = '';
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
            'destpers' => $customer->get_shipping_first_name() . ' ' . $customer->get_shipping_last_name(),
            'destpercontact' => $customer->get_billing_phone(),
            'destperpcode' => $postCode,
        ];
    }

    /**
     * @param WC_Order $order
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
     * @return array
     */
    private function getContentsPayload($parameters, $items)
    {
        $result = [];
        if (!empty($items)) {
            $itemsByProductQuantityPerParcel = [];
            foreach ($items as $item_id => $values) {
                $productQuantityPerParcel = (int)get_post_meta($values['product_id'], 'product_quantity_per_parcel', true);
                if (empty($productQuantityPerParcel)) {
                    $productQuantityPerParcel = (int)$parameters['product_quantity_per_parcel'];
                    if (empty($productQuantityPerParcel)) {
                        $productQuantityPerParcel = 1;
                    }
                }
                $item[$item_id] = $values;
                if (array_key_exists($productQuantityPerParcel, $itemsByProductQuantityPerParcel)) {
                    $itemByProductQuantityPerParcel = $itemsByProductQuantityPerParcel[$productQuantityPerParcel];
                    unset($itemsByProductQuantityPerParcel[$productQuantityPerParcel]);
                } else {
                    $itemByProductQuantityPerParcel = [];
                }
                $itemByProductQuantityPerParcel[$item_id] = $values;
                $itemsByProductQuantityPerParcel[$productQuantityPerParcel] = $itemByProductQuantityPerParcel;
            }
            $k = 1;
            foreach ($itemsByProductQuantityPerParcel as $productQuantityPerParcel => $items) {
                $i = 1;
                foreach ($items as $item_id => $values) {
                    $_product = new WC_Product($values['product_id']);
                    $prod = get_post($values['product_id']);
                    $slug = $prod->post_title;
                    if ($i > 1 && $i <= $productQuantityPerParcel) {
                        $entry = $result[($k - 1)];
                        $entry['desc'] = $entry['desc'] . ', ' . $slug;
                        $entry['pieces'] = ((int)$entry['pieces'] + (int)$values['quantity']);
                        if ($_product->has_dimensions()) {
                            $width = (int)$_product->get_width();
                            $height = (int)$_product->get_height();
                            $length = (int)$_product->get_length();
                            $entry['dim1'] = (int)$entry['dim1'] + (int)$length;
                            $entry['dim2'] = (int)$entry['dim2'] + (int)$width;
                            $entry['dim3'] = (int)$entry['dim3'] + (int)$height;
                        } else {
                            $entry['dim1'] = (int)$entry['dim1'] + 1;
                            $entry['dim2'] = (int)$entry['dim2'] + 1;
                            $entry['dim3'] = (int)$entry['dim3'] + 1;
                        }
                        $weight = (float)$_product->get_weight();
                        if ($_product->has_weight() && $weight > 0) {
                            $entry['actmass'] = (int)$entry['actmass'] + ((float)$weight * (int)$entry['pieces']);
                        } else {
                            $entry['actmass'] = (int)$entry['actmass'] + (1 * (int)$entry['pieces']);
                        }
                        if ($entry['actmass'] <= 0) {
                            $entry['actmass'] = 1;
                        }
                        $result[($k - 1)] = $entry;
                    } else {
                        $entry = [];
                        $entry['item'] = $k;
                        $entry['desc'] = $slug;
                        $entry['pieces'] = (int)$values['quantity'];
                        if ($_product->has_dimensions()) {
                            $width = (int)$_product->get_width();
                            $height = (int)$_product->get_height();
                            $length = (int)$_product->get_length();
                            $entry['dim1'] = (int)$length;
                            $entry['dim2'] = (int)$width;
                            $entry['dim3'] = (int)$height;
                        } else {
                            $entry['dim1'] = 1; //Centimeters
                            $entry['dim2'] = 1;
                            $entry['dim3'] = 1;
                        }
                        $weight = (float)$_product->get_weight();
                        if ($_product->has_weight() && $weight > 0) {
                            $entry['actmass'] = (float)$weight * (int)$entry['pieces'];
                        } else {
                            $entry['actmass'] = 1 * (int)$entry['pieces'];
                        }
                        if ($entry['actmass'] <= 0) {
                            $entry['actmass'] = 1;
                        }
                        $result[$k] = $entry;
                        ++$k;
                    }
                    if ($i == $productQuantityPerParcel) {
                        $i = 1;
                    } else {
                        ++$i;
                    }
                }
                foreach ($result as $index => $quoteParamsContentItem) {
                    $quoteParamsContentItem['pieces'] = ceil($quoteParamsContentItem['pieces'] / $productQuantityPerParcel);
                    $result[$index] = $quoteParamsContentItem;
                }
            }
            $result = array_values($result);
        }
        return $result;
    }

    /**
     * @param array $package
     * @param array $parameters
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
     * @return mixed
     */
    private function getServiceIdentifierFromShippingItem($shippingItem)
    {
        $method = $shippingItem['method_id'];
        $methodParts = explode(':', $method);
        return $methodParts[1];
    }
}
