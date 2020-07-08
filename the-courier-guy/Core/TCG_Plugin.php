<?php
Dompdf\Autoloader::register();

use Dompdf\Dompdf;
use Dompdf\Options;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * @author The Courier Guy
 * @package tcg/core
 * @version 1.0.0
 */
class TCG_Plugin extends CustomPlugin
{

    private $parcelPerfectApi;
    private $parcelPerfectApiPayload;

    /**
     * TCG_Plugin constructor.
     * @param $file
     */
    public function __construct($file)
    {
        if (session_status() == 1) {
            session_start();
        }
        parent::__construct($file);
        $this->registerCurlControllers();
        $this->initializeParcelPerfectApi();
        $this->initializeParcelPerfectApiPayload();
        $this->registerShippingMethod();

        add_action('wp_enqueue_scripts', [$this, 'registerJavascriptResources']);
        add_action('wp_enqueue_scripts', [$this, 'registerCSSResources']);
        add_action('wp_enqueue_scripts', [$this, 'localizeJSVariables']);
        add_action('admin_enqueue_scripts', [$this, 'registerJavascriptResources']);
        add_action('admin_enqueue_scripts', [$this, 'localizeJSVariables']);
        add_action('login_enqueue_scripts', [$this, 'localizeJSVariables']);
        add_action('wp_ajax_wc_tcg_get_places', [$this, 'getSuburbs']);
        add_action('wp_ajax_nopriv_wc_tcg_get_places', [$this, 'getSuburbs']);
        add_action('woocommerce_checkout_update_order_review', [$this, 'updateShippingPropertiesFromCheckout']);
        add_action('woocommerce_checkout_update_order_review', [$this, 'removeCachedShippingPackages']);
        add_filter('woocommerce_checkout_fields', [$this, 'overrideAddressFields'], 999, 1);
        add_filter('woocommerce_form_field_tcg_place_lookup', [$this, 'getSuburbFormFieldMarkUp'], 1, 4);
        add_filter('woocommerce_after_shipping_calculator', [$this, 'addSuburbSelectToCart'], 10, 1);
        add_action('woocommerce_calculated_shipping', [$this, 'saveSuburbSelectFromCart']);
        add_filter('woocommerce_email_order_meta_keys', [$this, 'addExtraEmailFields']);

        add_action('woocommerce_order_actions', [$this, 'addSendCollectionActionToOrderMetaBox'], 10, 1);
        add_action('woocommerce_order_actions', [$this, 'addPrintWayBillActionToOrderMetaBox'], 10, 1);
        add_action('admin_post_print_waybill', [$this, 'printWaybillFromOrder'], 10, 0);
        add_action('woocommerce_order_action_tcg_print_waybill', [$this, 'redirectToPrintWaybillUrl'], 10, 1);
        add_filter('woocommerce_admin_shipping_fields', [$this, 'addShippingMetaToOrder'], 10, 1);

        add_action('woocommerce_order_action_tcg_send_collection', [$this, 'setCollectionFromOrder'], 10, 1);
        add_action('woocommerce_order_status_processing', [$this, 'setCollectionOnOrderProcessing']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'updateShippingPropertiesOnOrder'], 10, 2);
    }

    /**
     * @param int $orderId
     * @param array $data
     */
    public function updateShippingPropertiesOnOrder($orderId, $data)
    {
        $customShippingProperties = $this->getShippingCustomProperties();
        $placeId = $customShippingProperties['tcg_place_id'];
        $placeLabel = $customShippingProperties['tcg_place_label'];
        //@todo The setting of these additional billing and shipping properties is legacy from an older version of the plugin.
        update_post_meta($orderId, '_billing_area', $placeId);
        update_post_meta($orderId, '_shipping_place', $placeLabel);
        update_post_meta($orderId, '_shipping_area', $placeId);
        update_post_meta($orderId, '_shipping_place', $placeLabel);

        if ( isset($_SESSION['cachedQuoteResponse']) && $_SESSION['cachedQuoteResponse'] != '' ) {
            $quoteno = json_decode($_SESSION['cachedQuoteResponse'])[0]->quoteno;
            update_post_meta($orderId, '_shipping_quote', $quoteno);
        }
        $this->clearShippingCustomProperties();
    }

    /**
     * @param string $postData
     */
    public function updateShippingPropertiesFromCheckout($postData)
    {
        parse_str($postData, $parameters);
        $addressPrefix = 'shipping_';
        if (!isset($parameters['ship_to_different_address']) || $parameters['ship_to_different_address'] != true) {
            $addressPrefix = 'billing_';
        }
        $insurance = false;
        if (!empty($parameters[$addressPrefix . 'insurance']) && $parameters[$addressPrefix . 'insurance'] == '1') {
            $insurance = true;
        }
        $customProperties = [
            'tcg_place_id' => sanitize_text_field($parameters[$addressPrefix . 'tcg_place_lookup_place_id']),
            'tcg_place_label' => sanitize_text_field($parameters[$addressPrefix . 'tcg_place_lookup_place_label']),
            'tcg_insurance' => $insurance,
        ];

        if ( isset($_SESSION['cachedQuoteResponse']) ) {
            $customProperties['tcg_quoteno'] = json_decode($_SESSION['cachedQuoteResponse'])[0]->quoteno;
        }

        $this->setShippingCustomProperties($customProperties);
    }

    /**
     * @return array
     */
    public function getShippingCustomProperties( $order = null )
    {
        $result = [];
        if ( isset ($order) ) {
            $result['tcg_place_id']    = get_user_meta($order->get_customer_id(), 'tcg_place_id', true);
            $result['tcg_place_label'] = get_user_meta($order->get_customer_id(), 'tcg_place_label', true);
            $result['tcg_insurance']   = get_user_meta($order->get_customer_id(), 'tcg_insurance', true);
            $result['tcg_quoteno']     = get_user_meta($order->get_customer_id(), 'tcg_quoteno', true);
        } elseif ( is_user_logged_in() ) {
            $customer = WC()->customer;
            $result['tcg_place_id'] = get_user_meta($customer->get_id(), 'tcg_place_id', true);
            $result['tcg_place_label'] = get_user_meta($customer->get_id(), 'tcg_place_label', true);
            $result['tcg_insurance'] = get_user_meta($customer->get_id(), 'tcg_insurance', true);
            $result['tcg_quoteno']     = get_user_meta($customer->get_id(), 'tcg_quoteno', true);
        } else {
            $result['tcg_place_id'] = $_SESSION['tcg_place_id'];
            $result['tcg_place_label'] = $_SESSION['tcg_place_label'];
            $result['tcg_insurance'] = $_SESSION['tcg_insurance'];
            $result['tcg_quoteno']     = $_SESSION['tcg_quoteno'];
        }

        return $result;
    }

    /**
     * @param array $keys
     *
     * @return mixed
     */
    public function addExtraEmailFields($keys)
    {
        //@todo This naming of this post meta data is legacy from an older version of the plugin.
        $keys['Waybill'] = 'dawpro_waybill';

        return $keys;
    }

    /**
     * @param WC_Order $order
     */
    public function setCollectionFromOrder($order)
    {
        $forceCollectionSending = true;
        $this->setCollection($order, $forceCollectionSending);
    }

    /**
     * @param int $orderId
     */
    public function setCollectionOnOrderProcessing($orderId)
    {
        $order = new WC_Order($orderId);
        $this->setCollection($order);
    }

    /**
     * @param array $adminShippingFields
     *
     * @return array
     */
    public function addShippingMetaToOrder($adminShippingFields = [])
    {
        $tcgAdminShippingFields = [
            /*'insurance' => [
                'label' => __('Courier Guy Insurance'),
                'class' => 'wide',
                'show' => true,
                'readonly' => true,
                'type', 'checkbox'
            ],*/
            'area' => [
                'label' => __('Courier Guy Shipping Area Code'),
                'wrapper_class' => 'form-field-wide',
                'show' => true,
                'custom_attributes' => [
                    'disabled' => 'disabled'
                ]
            ],
            'place' => [
                'label' => __('Courier Guy Shipping Area Description'),
                'wrapper_class' => 'form-field-wide',
                'show' => true,
                'custom_attributes' => [
                    'disabled' => 'disabled'
                ]
            ]
        ];

        return array_merge($adminShippingFields, $tcgAdminShippingFields);
    }

    /**
     * @param WC_Order $order
     */
    public function redirectToPrintWaybillUrl($order)
    {
        wp_redirect('/wp-admin/admin-post.php?action=print_waybill&order_id=' . $order->get_id());
        exit;
    }

    /**
     *
     */
    public function printWaybillFromOrder()
    {
        $uploadsDirectory = $this->getPluginUploadPath();
        if (!empty($uploadsDirectory)) {
            $orderId = sanitize_text_field($_GET['order_id']);
            $order = wc_get_order($orderId);
            //@todo This naming of this post meta data is legacy from an older version of the plugin.
            $waybillNumber = get_post_meta($order->get_id(), 'dawpro_waybill', true);
            $pdfFilePath   = $uploadsDirectory . '/' . $waybillNumber . '.pdf';

            if ( file_exists($pdfFilePath) ) {
                $this->sendPdf($pdfFilePath);
            } else {
            $barcodePath = $this->getBarcodeImagePath($waybillNumber);
            $parcelPerfectApiPayload = $this->getParcelPerfectApiPayload();
            $shippingItems = $order->get_items('shipping');
            $printWaybillMarkup = '';
            $pdfPageSize = 'a4';
            array_walk($shippingItems, function ($shippingItem) use (&$printWaybillMarkup, $parcelPerfectApiPayload, $order, $waybillNumber, $barcodePath, &$pdfPageSize) {
                $shippingInstanceId = $shippingItem->get_meta('instance_id', true);
                $parameters = get_option('woocommerce_the_courier_guy_' . $shippingInstanceId . '_settings');
                $pdfPageSize = $parameters['order_waybill_pdf_paper_size'];
                $collectionParams = $parcelPerfectApiPayload->getCollectionPayload($order, $shippingItem, $parameters);
                $printWaybillMarkup = $printWaybillMarkup . $this->getPrintWaybillMarkup($order, $collectionParams, $parameters, $waybillNumber, $barcodePath);
            });
            $this->generatePdf($pdfFilePath, $printWaybillMarkup, $pdfPageSize);
            $this->sendPdf($pdfFilePath);
        }
    }
    }

    /**
     * @param array $actions
     *
     * @return mixed
     */
    public function addPrintWayBillActionToOrderMetaBox( $actions )
    {
        $orderId           = sanitize_text_field($_GET['post']);
        $order             = wc_get_order($orderId);
        $hasShippingMethod = $this->hasTcgShippingMethod($order);
        if ( $hasShippingMethod ) {
            $actions['tcg_print_waybill'] = __('Print Waybill', 'woocommerce');
        }

        return $actions;
    }

    /**
     * @param array $actions
     *
     * @return mixed
     */
    public function addSendCollectionActionToOrderMetaBox( $actions )
    {
        $orderId           = sanitize_text_field($_GET['post']);
        $order             = wc_get_order($orderId);
        $hasShippingMethod = $this->hasTcgShippingMethod($order);
        if ( $hasShippingMethod ) {
            $actions['tcg_send_collection'] = __('Send Order to Courier Guy', 'woocommerce');
        }

        return $actions;
    }

    /**
     * @param $field
     * @param $key
     * @param $args
     * @param $value
     *
     * @return string
     */
    public function getSuburbFormFieldMarkUp( $field, $key, $args, $value )
    {
        //@todo The contents of this method is legacy code from an older version of the plugin.
        if ( $args['required'] ) {
            $args['class'][] = 'validate-required';
            $required        = ' <abbr class="required" title="' . esc_attr__('required', 'woocommerce') . '">*</abbr>';
        } else {
            $required = '';
        }
        $options                  = $field = '';
        $label_id                 = $args['id'];
        $sort                     = $args['priority'] ? $args['priority'] : '';
        $field_container          = '<p class="form-row %1$s" id="%2$s" data-sort="' . esc_attr($sort) . '">%3$s</p>';
        $customShippingProperties = $this->getShippingCustomProperties();
        $option_key               = $customShippingProperties['tcg_place_id'];
        $option_text              = $customShippingProperties['tcg_place_label'];
        $options                  .= '<option value="' . esc_attr($option_key) . '" ' . selected($value, $option_key, false) . '>' . esc_attr($option_text) . '</option>';
        $field                    .= '<input type="hidden" name="' . esc_attr($key) . '_place_id" value="' . $option_key . '"/>';
        $field                    .= '<input type="hidden" name="' . esc_attr($key) . '_place_label" value="' . $option_text . '"/>';
        $field                    .= '<select id="' . esc_attr($args['id']) . '" name="' . esc_attr($args['id']) . '" class="select ' . esc_attr(implode(' ', $args['input_class'])) . '" ' . ' data-placeholder="' . esc_attr($args['placeholder']) . '">
							' . $options . '
						</select>';
        if ( ! empty($field) ) {
            $field_html = '';
            if ( $args['label'] && 'checkbox' != $args['type'] ) {
                $field_html .= '<label for="' . esc_attr($label_id) . '" class="' . esc_attr(implode(' ', $args['label_class'])) . '">' . $args['label'] . $required . '</label>';
            }
            $field_html .= $field;
            if ( $args['description'] ) {
                $field_html .= '<span class="description">' . esc_html($args['description']) . '</span>';
            }
            $container_class = esc_attr(implode(' ', $args['class']));
            $container_id    = esc_attr($args['id']) . '_field';
            $field           = sprintf($field_container, $container_class, $container_id, $field_html);
        }

        return $field;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function overrideAddressFields( $fields )
    {
        $fields = $this->addAddressFields('billing', $fields);
        $fields = $this->addAddressFields('shipping', $fields);

        return $fields;
    }

    /**
     *
     */
    public function removeCachedShippingPackages()
    {
        //@todo The contents of this method is legacy code from an older version of the plugin.
        $packages = WC()->cart->get_shipping_packages();
        foreach ( $packages as $key => $value ) {
            $shipping_session = "shipping_for_package_$key";
            unset(WC()->session->$shipping_session);
        }
    }

    /**
     *
     */
    public function getSuburbs()
    {
        //@todo The contents of this method is legacy code from an older version of the plugin.
        $term        = sanitize_text_field($_GET['q']['term']);
        $dp_areas    = [];
        $payloadData = [
            'name' => $term,
        ];
        $d           = $this->getPlacesByName($payloadData);
        foreach ( $d as $result ) {
            $suggestion = [
                'suburb_value' => $result['town'],
                'suburb_key'   => $result['place']
            ];
            $dp_areas[] = $suggestion;
        }
        echo json_encode($dp_areas);
        exit;
    }

    /**
     *
     */
    public function localizeJSVariables()
    {
        //@todo The contents of this method is legacy code from an older version of the plugin, however slightly refactored.
        $southAfricaOnly        = false;
        $shippingMethodSettings = $this->getShippingMethodSettings();
        if ( ! empty($shippingMethodSettings) && ! empty($shippingMethodSettings['south_africa_only']) && $shippingMethodSettings['south_africa_only'] == 'yes' ) {
            $southAfricaOnly = true;
        }
        $translation_array = [
            'url'             => get_admin_url(null, 'admin-ajax.php'),
            'southAfricaOnly' => ( $southAfricaOnly ) ? 'true' : 'false',
        ];
        wp_localize_script($this->getPluginTextDomain() . '-main.js', 'theCourierGuy', $translation_array);
    }

    /**
     *
     */
    public function registerJavascriptResources()
    {
        $this->registerJavascriptResource('main.js', [ 'jquery' ]);
    }

    /**
     *
     */
    public function registerCSSResources()
    {
        $this->registerCSSResource('main.css');
    }

    /**
     *
     */
    public function addSuburbSelectToCart()
    {
        $customShippingProperties = $this->getShippingCustomProperties();
        $placeId                  = $customShippingProperties['tcg_place_id'];
        $placeLabel               = $customShippingProperties['tcg_place_label'];
        $options                  = [
            $placeId => $placeLabel
        ];
        ob_start();
        ?>
        <p class="form-row form-row-wide validate-required tcg-suburb-field" style="display: none;"
           id="tcg-cart-shipping-area-panel">
            <input type="hidden" name="tcg_place_id"/>
            <input type="hidden" name="tcg_place_label"/>
            <select class="select form-row-wide" style="width:100%;">
                <?php foreach ( (array) $options as $option_key => $option_value ) : ?>
                    <option value="<?php echo esc_attr($option_key); ?>" <?php selected($option_key, esc_attr('4509')); ?>><?php echo esc_attr($option_value); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
        echo ob_get_clean();
    }

    /**
     *
     */
    public function saveSuburbSelectFromCart()
    {
        if ( ! empty($_POST['tcg_place_id']) && ! empty($_POST['tcg_place_label']) && ! empty($_POST['woocommerce-shipping-calculator-nonce']) && wp_verify_nonce($_POST['woocommerce-shipping-calculator-nonce'], 'woocommerce-shipping-calculator') ) {
            $customProperties = [
                'tcg_place_id'    => sanitize_text_field($_POST['tcg_place_id']),
                'tcg_place_label' => sanitize_text_field($_POST['tcg_place_label']),
            ];
            $this->setShippingCustomProperties($customProperties);
            $this->removeCachedShippingPackages();
        }
    }

    /**
     * @param array $package
     * @param array $parameters
     *
     * @return array|mixed|object
     */
    public function getQuote( $package, $parameters )
    {
        $result                  = [];
        $parcelPerfectApiPayload = $this->getParcelPerfectApiPayload();
        $quoteParams             = $parcelPerfectApiPayload->getQuotePayload($package, $parameters);
        $payloadData             = apply_filters('thecourierguy_before_request_quote', $quoteParams, $package);
        if ( ! empty($payloadData) && ! empty($payloadData['details']) && ! empty($payloadData['details']['origplace']) && ! empty($payloadData['details']['origtown']) && ! empty($payloadData['details']['destplace']) && ! empty($payloadData['details']['desttown']) ) {
            if ( ! $this->compareCachedQuoteRequest($payloadData) ) {
                $parcelPerfectApi = $this->getParcelPerfectApi();
                $result           = $parcelPerfectApi->getQuote($payloadData);
                if ( ! empty($result) ) {
                    $this->updateCachedQuoteRequest($payloadData);
                    $this->updateCachedQuoteResponse($result);
                }
            } else {
                $result = json_decode($this->getCachedQuoteResponse(), true);
            }
        } else {
            $this->clearCachedQuote();
        }

        return $result;
    }

    /**
     * @param string $quoteNumber
     */
    public function setService( $quoteNumber )
    {
        if ( ! empty($quoteNumber) ) {
            $parcelPerfectApi = $this->getParcelPerfectApi();
            $chosenMethod     = WC()->session->get('chosen_shipping_methods');
            if ( ! empty($chosenMethod) && ! empty($chosenMethod[0]) ) {
                $serviceType = $chosenMethod[0];
                if ( ! empty($serviceType) ) {
                    $methodParts = explode(':', $serviceType);
                    if ( ! empty($methodParts) && ! empty($methodParts[0]) && ! empty($methodParts[1]) && $methodParts[0] == 'the_courier_guy' ) {
                        $service = $methodParts[1];
                        if ( ! empty($service) ) {
                            $payloadData = [
                                'quoteno' => $quoteNumber,
                                'service' => $service,
                            ];
                            $parcelPerfectApi->setService($payloadData);
                        }
                    }
                }
            }
        }
    }

    /**
     *
     */
    protected function registerModel()
    {
        require_once( $this->getPluginPath() . 'Model/Product.php' );
    }

    /**
     *
     */
    private function clearShippingCustomProperties()
    {
        if ( is_user_logged_in() ) {
            $customer = WC()->customer;
            update_user_meta($customer->get_id(), 'tcg_place_id', sanitize_text_field(''));
            update_user_meta($customer->get_id(), 'tcg_place_label', sanitize_text_field(''));
            update_user_meta($customer->get_id(), 'tcg_insurance', sanitize_text_field(''));
        }
        $_SESSION['tcg_place_id']    = sanitize_text_field('');
        $_SESSION['tcg_place_label'] = sanitize_text_field('');
        $_SESSION['tcg_insurance']   = sanitize_text_field('');
    }

    /**
     * @param array $customProperties
     */
    private function setShippingCustomProperties( $customProperties )
    {
        if ( is_user_logged_in() ) {
            $customer = WC()->customer;
            update_user_meta($customer->get_id(), 'tcg_place_id', sanitize_text_field($customProperties['tcg_place_id']));
            update_user_meta($customer->get_id(), 'tcg_place_label', sanitize_text_field($customProperties['tcg_place_label']));
            update_user_meta($customer->get_id(), 'tcg_insurance', sanitize_text_field($customProperties['tcg_insurance']));
            update_user_meta($customer->get_id(), 'tcg_quoteno', sanitize_text_field($customProperties['tcg_quoteno']));
        } else {
            $_SESSION['tcg_place_id']    = sanitize_text_field($customProperties['tcg_place_id']);
            $_SESSION['tcg_place_label'] = sanitize_text_field($customProperties['tcg_place_label']);
            $_SESSION['tcg_insurance']   = sanitize_text_field($customProperties['tcg_insurance']);
            $_SESSION['tcg_quote']       = sanitize_text_field($customProperties['tcg_quote']);
        }
    }

    /**
     * @param string $filePath
     */
    private function sendPdf($filePath)
    {
        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($filePath));
        header('Accept-Ranges: bytes');
        @readfile($filePath);
        exit;
    }

    /**
     * @param string $filePath
     * @param string $markup
     * @param string $pageSize
     */
    private function generatePdf($filePath, $markup, $pageSize)
    {
        $domPdf = new Dompdf(new Options());
        $domPdf->loadHtml($markup);
        $domPdf->setPaper($pageSize, 'portrait');
        $domPdf->render();
        $output = $domPdf->output();
        file_put_contents($filePath, $output);
    }

    /**
     * @param WC_Order $order
     * @param array $collectionParams
     * @param array $shippingSettings
     * @param string $waybillNumber
     * @param string $barcodePath
     *
     * @return string
     */
    private function getPrintWaybillMarkup($order, $collectionParams, $shippingSettings, $waybillNumber, $barcodePath)
    {
        $printWaybillTemplateFile = $this->getPrintWaybillTemplateFile();
        ob_start();
        include($printWaybillTemplateFile);
        $result = ob_get_clean();
        ob_end_clean();

        return $result;
    }

    /**
     * @return string
     */
    private function getPrintWaybillTemplateFile()
    {
        $result = get_template_directory() . '/waybill.print.php';
        if (!file_exists($result)) {
            $result = $this->getPluginPath() . '/Templates/waybill.print.php';
        }

        return $result;
    }

    /**
     * @param string $barcodeNumber
     *
     * @return string
     */
    private function getBarcodeImagePath($barcodeNumber)
    {
        $result = '';
        $generator = new BarcodeGeneratorPNG();
        $uploadsDirectory = $this->getPluginUploadPath();
        if (!empty($uploadsDirectory)) {
            $fileName = $barcodeNumber . '.png';
            file_put_contents($uploadsDirectory . '/' . $fileName, $generator->getBarcode($barcodeNumber, $generator::TYPE_CODE_128, 2, 50));
            $result = $uploadsDirectory . '/' . $fileName;
        }
        return $result;
    }

    private function getShippingMethodSettings()
    {
        $shippingMethodSettings = [];
        $existingZones = WC_Shipping_Zones::get_zones();
        foreach ($existingZones as $zone) {
            $shippingMethods = $zone['shipping_methods'];
            foreach ($shippingMethods as $shippingMethod) {
                if ($shippingMethod->id == 'the_courier_guy') {
                    $courierGuyShippingMethod = $shippingMethod;
                }
            }
        }
        if (!empty($courierGuyShippingMethod)) {
            $shippingMethodSettings = $courierGuyShippingMethod->instance_settings;
        }
        return $shippingMethodSettings;
    }

    /**
     * @param array $source
     * @param array $target
     * @param string $positionIndex
     *
     * @return array
     */
    private function insertValueAfterPosition($source, $target, $positionIndex)
    {
        $result = [];
        foreach ($source as $sourceKey => $sourceValue) {
            $result[$sourceKey] = $sourceValue;
            if ($sourceKey == $positionIndex) {
                foreach ($target as $targetKey => $targetValue) {
                    $result[$targetKey] = $targetValue;
                }
            }
        }
        return $result;
    }

    private function getSurburbLabel()
    {
        $shippingMethodSettings = $this->getShippingMethodSettings();
        if (!empty($shippingMethodSettings)) {
            if(!empty($shippingMethodSettings['Suburb_title'])){
                return $shippingMethodSettings['Suburb_title'];
            } else {
                return 'Area / Suburb';
            }
        }

        return 'Area / Suburb';
    }

    private function getSurburblocation()
    {
        $shippingMethodSettings = $this->getShippingMethodSettings();
        if (!empty($shippingMethodSettings)) {
            if(!empty($shippingMethodSettings['Suburb_location'])){
                return $shippingMethodSettings['Suburb_location'];
            } else {
                return '_country';
            }
        }

        return '_country';
    }

    /**
     * @param string $addressType
     * @param array $fields
     *
     * @return array
     */
    private function addAddressFields($addressType, $fields)
    {
        $addressFields = $fields[$addressType];
        $required = true;
        $shippingMethodSettings = $this->getShippingMethodSettings();
        if (!empty($shippingMethodSettings) && !empty($shippingMethodSettings['south_africa_only']) && $shippingMethodSettings['south_africa_only'] == 'yes') {
            $required = false;
        }
        $addressFields = $this->insertValueAfterPosition($addressFields, [
            $addressType . '_tcg_place_lookup' => [
                'type' => 'tcg_place_lookup',
                'label' => $this->getSurburbLabel(),
                'options' => ['Search Suburb...'],
                'required' => $required,
                'placeholder' => $this->getSurburbLabel(),
                'class' => ['form-row-wide', 'address-field', 'tcg-suburb-field'],
            ],
        ], $addressType . $this->getSurburblocation());
        $addressFields = array_merge($addressFields, [
            $addressType . '_postcode' => [
                'type' => 'text',
                'label' => 'Postcode',
                'required' => true,
                'class' => ['form-row-last'],
            ]
        ]);
        $addressFields[$addressType . '_insurance'] = [
            'type' => 'checkbox',
            'label' => 'Would you like to include Shipping Insurance',
            'required' => false,
            'class' => ['form-row-wide', 'tcg-insurance'],
            'priority' => 90,
        ];
        $addressFields[ $addressType . '_tcg_quoteno' ] = [
            'type'     => 'text',
            'label'    => 'TCG Quote Number',
            'required' => false,
            'class'    => [ 'form-row-wide', 'tcg-quoteno' ],
            'priority' => 90,
        ];
        $legacyFieldProperties = [
            'type' => 'hidden',
            'required' => false,
        ];
        //@todo The setting of these additional billing and shipping properties is legacy from an older version of the plugin. This is to override legacy properties to invalidate cached required validation.
        $addressFields[$addressType . '_area'] = $legacyFieldProperties;
        $addressFields[$addressType . '_place'] = $legacyFieldProperties;
        $fields[$addressType] = $addressFields;

        return $fields;
    }

    /**
     * @return mixed
     */
    private function getParcelPerfectApi()
    {
        return $this->parcelPerfectApi;
    }

    /**
     * @param mixed $parcelPerfectApi
     */
    private function setParcelPerfectApi($parcelPerfectApi)
    {
        $this->parcelPerfectApi = $parcelPerfectApi;
    }

    /**
     *
     */
    private function initializeParcelPerfectApi()
    {
        $emailAddress = $this->getEmailAddress();
        $password = $this->getPassword();
        require_once($this->getPluginPath() . 'Core/ParcelPerfectApi.php');
        $parcelPerfectApi = new ParcelPerfectApi($emailAddress, $password);
        $this->setParcelPerfectApi($parcelPerfectApi);
    }

    /**
     * @return mixed
     */
    private function getEmailAddress()
    {
        return get_option('tcg_username');
    }

    /**
     * @return mixed
     */
    private function getPassword()
    {
        return get_option('tcg_password');
    }

    /**
     * @return mixed
     */
    private function getParcelPerfectApiPayload()
    {
        return $this->parcelPerfectApiPayload;
    }

    /**
     * @param mixed $parcelPerfectApiPayload
     */
    private function setParcelPerfectApiPayload($parcelPerfectApiPayload)
    {
        $this->parcelPerfectApiPayload = $parcelPerfectApiPayload;
    }

    /**
     *
     */
    private function initializeParcelPerfectApiPayload()
    {
        require_once($this->getPluginPath() . 'Core/ParcelPerfectApiPayload.php');
        $parcelPerfectApiPayload = new ParcelPerfectApiPayload();
        $this->setParcelPerfectApiPayload($parcelPerfectApiPayload);
    }

    /**
     *
     */
    private function registerCurlControllers()
    {
        require_once($this->getPluginPath() . 'Core/CurlControllers.php');
    }

    /**
     *
     */
    private function registerShippingMethod()
    {
        require_once($this->getPluginPath() . 'Shipping/TCG_ShippingMethod.php');
        add_filter('woocommerce_shipping_methods', function ($methods) {
            $methods['the_courier_guy'] = 'TCG_Shipping_Method';

            return $methods;
        });
    }

    /**
     * @param array $payloadData
     * @return mixed
     */
    private function getPlacesByName($payloadData)
    {
        $parcelPerfectApi = $this->getParcelPerfectApi();

        return $parcelPerfectApi->getPlacesByName($payloadData);
    }

    /**
     * @param WC_Order $order
     * @param bool $forceCollectionSending
     */
    private function setCollection($order, $forceCollectionSending = false)
    {
        $shippingCustomProperties = $this->getShippingCustomProperties($order);
        $parcelPerfectApi = $this->getParcelPerfectApi();
        $shippingItems = $order->get_items('shipping');
        $quoteno                  = get_post_meta($order->get_id(), '_shipping_quote')[0];

        $payloadData['quoteno']             = $quoteno;
        $payloadData['quoteCollectionDate'] = ( new DateTime() )->add(new DateInterval('P1D'))->format('d.m.Y');
        $payloadData['starttime']           = ( new DateTime() )->add(new DateInterval('PT1H'))->format('H:i:s');
        $payloadData['endtime']             = '18:00:00';
        $payloadData['printWaybill']        = 1;

        array_walk($shippingItems, function ( $shippingItem ) use ( $parcelPerfectApi, $order, $forceCollectionSending, $payloadData ) {
            $shippingInstanceId = $shippingItem->get_meta('instance_id', true);
            $shippingInstanceMethod = $shippingItem->get_meta('method_id', true);
            if (strstr($shippingInstanceMethod, 'the_courier_guy')) {
                $parameters = get_option('woocommerce_the_courier_guy_' . $shippingInstanceId . '_settings');
                if ($forceCollectionSending || (!empty($parameters['automatically_submit_collection_order']) && $parameters['automatically_submit_collection_order'] == 'yes')) {
                    $result = $parcelPerfectApi->setCollection($payloadData);
                    $this->updateOrderWaybill($order, $result[0]['waybillno']);
                    $this->updateOrderCollectionNumber($order, $result[0]['collectno']);
                    $this->savePdfWaybill( $result[0]['waybillno'], $result[0]['waybillBase64']);
                }
            }
        });
    }

    private function savePdfWaybill( $collectno, $base64 )
    {
        $uploadsDirectory = $this->getPluginUploadPath();
        $pdfFilePath      = $uploadsDirectory . '/' . $collectno . '.pdf';
        try {
            $f = fopen($pdfFilePath, 'wb');
            fwrite($f, base64_decode($base64));
            fclose($f);
        } catch ( Exception $e ) {

        }
    }

    private function hasTcgShippingMethod($order)
    {
        $result = false;
        if (!empty($order)) {
            $shippingItems = $order->get_items('shipping');
            array_walk($shippingItems, function ($shippingItem) use (&$result) {
                $shippingInstanceMethod = $shippingItem->get_meta('method_id', true);
                if (strstr($shippingInstanceMethod, 'the_courier_guy')) {
                    $result = true;
                }
            });
        }

        return $result;
    }

    /**
     * @param WC_Order $order
     * @param $waybill
     */
    private function updateOrderWaybill($order, $waybill)
    {
        //@todo This naming of this post meta data is legacy from an older version of the plugin.
        if (!empty($order) && !empty($waybill)) {
            $currentWaybill = get_post_meta($order->get_id(), 'dawpro_waybill', true);
            if (!empty($currentWaybill)) {
                $currentWaybill = $currentWaybill . ',';
            }
            update_post_meta($order->get_id(), 'dawpro_waybill', $currentWaybill . $waybill);
        }
    }

    /**
     * @param WC_Order $order
     * @param $collectionNumber
     */
    private function updateOrderCollectionNumber($order, $collectionNumber)
    {
        //@todo This naming of this post meta data is legacy from an older version of the plugin.
        $currentCollectionNumber = get_post_meta($order->get_id(), 'dawpro_collectno', true);
        if (!empty($currentCollectionNumber)) {
            $currentCollectionNumber = $currentCollectionNumber . ',';
        }
        update_post_meta($order->get_id(), 'dawpro_collectno', $currentCollectionNumber . $collectionNumber);
    }

    private function clearCachedQuote()
    {
        unset($_SESSION['cachedQuoteRequest']);
        unset($_SESSION['cachedQuoteResponse']);
    }

    /**
     * @param array $quoteResponse
     */
    private function updateCachedQuoteResponse($quoteResponse)
    {
        $_SESSION['cachedQuoteResponse'] = json_encode($quoteResponse);
    }

    /**
     * @return mixed
     */
    private function getCachedQuoteResponse()
    {
        return $_SESSION['cachedQuoteResponse'];
    }

    /**
     * @param array $quoteParams
     */
    private function updateCachedQuoteRequest($quoteParams)
    {
        if (null !== WC()->session) {
            $wcSession = WC()->session;
            $_SESSION['cachedQuoteRequest'] = hash('md5', json_encode($quoteParams) . $wcSession->_customer_id);
        }
    }

    /**
     * @param array $quoteParams
     *
     * @return bool
     */
    private function compareCachedQuoteRequest($quoteParams)
    {
        $result = false;
        if (!empty($_SESSION['cachedQuoteRequest'])) {
            $wcSession = WC()->session;
            $cachedQuoteHash = $_SESSION['cachedQuoteRequest'];
            $compareQuoteHash = hash('md5', json_encode($quoteParams, true) . $wcSession->_customer_id);
            if (!empty($cachedQuoteHash) && ($compareQuoteHash == $cachedQuoteHash)) {
                $result = true;
            }
        }

        return $result;
    }
}
