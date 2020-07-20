(function ($) {
    const shippingMethods = $('input.shipping_method');
    let showTcgDate = false;
    $.each(shippingMethods, function (index, shippingMethod) {
        if (shippingMethod.checked && shippingMethod.value.startsWith('the_courier_guy')) {
            showTcgDate = true;
        }
    });

    if(showTcgDate) {
        $('div.tcgDeliveryDate').removeClass('tcgDeliveryDateFieldHidden');
        $('div.tcgDeliveryDate').addClass('tcgDeliveryDateFieldShown');
    } else {
        $('div.tcgDeliveryDate').addClass('tcgDeliveryDateFieldHidden');
        $('div.tcgDeliveryDate').removeClass('tcgDeliveryDateFieldShown');
    }

    $('body').on('click', 'input.shipping_method', function () {
        if (this.value.startsWith('the_courier_guy')) {
            $('div.tcgDeliveryDate').removeClass('tcgDeliveryDateFieldHidden');
            $('div.tcgDeliveryDate').addClass('tcgDeliveryDateFieldShown');
        } else {
            $('div.tcgDeliveryDate').addClass('tcgDeliveryDateFieldHidden');
            $('div.tcgDeliveryDate').removeClass('tcgDeliveryDateFieldShown');
        }
    });

    $(function () {
        //cart
        $('body').on('click', '.shipping-calculator-button', function () {
            var cartShippingAreaPanel = $('#tcg-cart-shipping-area-panel');
            var shippingForm = cartShippingAreaPanel.prev('.woocommerce-shipping-calculator');
            var updateShippingButton = shippingForm.find('button[name=calc_shipping]');
            updateShippingButton.parent('p').before(cartShippingAreaPanel);
            cartShippingAreaPanel.show();
        });

        function select2LocationSelect() {
            var suburbSelect = $('.tcg-suburb-field select');
            if (suburbSelect.length > 0) {
                $('.tcg-suburb-field select').select2({
                    placeholder: "Start typing Suburb name...",
                    minimumInputLength: 3,
                    ajax: {
                        url: theCourierGuy.url,
                        dataType: 'json',
                        delay: 350,
                        data: function (term) {
                            return {
                                q: term, // search term
                                action: 'wc_tcg_get_places'
                            };
                        },
                        closeOnSelect: true,
                        processResults: function (data) {
                            var results = [];
                            $.each(data, function (index, item) {
                                results.push({
                                    id: item.suburb_key,
                                    text: item.suburb_value
                                });
                            });
                            return {
                                results: results,
                            };
                        },
                        cache: false
                    }
                }).on("change", function (evt) {
                    var select = $(this);
                    var placeLabelInput = select.prev('input');
                    var placeIdInput = placeLabelInput.prev('input');
                    $(this).children().each(function () {
                        var option = $(this);
                        if (option.val() !== '') {
                            placeIdInput.val(option.val());
                            placeLabelInput.val(option.text());
                        }
                    });
                    $('body').trigger('update_checkout');
                });
            }
        }

        $(document.body).on('updated_cart_totals', function () {
            select2LocationSelect();
        });

        select2LocationSelect();
        $('.tcg-insurance').on('change', function () {
            $('.tcg-suburb-field select').trigger('change');
        });

        /*$('#billing_autocomplete').on('change', function (event) {
            event.preventDefault();
            var select2 = $('.tcg-suburb-field select');
            select2.trigger('open');
            var search = select2.data('select2').dropdown.$search || select2.data('select2').selection.$search;
            search.val($(this).val());
            search.trigger('keyup');
        });*/

        function clearPlaceSelects() {
            var placeSelects = $('.tcg-suburb-field').find('select');
            placeSelects.children('option').remove();
            placeSelects.val(null).trigger('change');
            $('input[name=billing_tcg_place_lookup_place_id]').val('');
            $('input[name=billing_tcg_place_lookup_place_label]').val('');
            $('input[name=shipping_tcg_place_lookup_place_id]').val('');
            $('input[name=shipping_tcg_place_lookup_place_label]').val('');
        }

        function toggleSuburbPanelDisplay(tcgSuburbSelect, tcgSuburbSelectPanel) {
            if (tcgSuburbSelect.val() === 'ZA') {
                if (theCourierGuy.southAfricaOnly === 'true') {
                    tcgSuburbSelectPanel.show();
                }
            } else {
                if (theCourierGuy.southAfricaOnly === 'true') {
                    tcgSuburbSelectPanel.hide();
                }
                clearPlaceSelects();
            }
        }

        $('#billing_country').on('change', function (event) {
            var tcgSuburbSelectPanel = $('#billing_tcg_place_lookup_field');
            toggleSuburbPanelDisplay($(this), tcgSuburbSelectPanel);
        });
        $('#shipping_country').on('change', function (event) {
            var tcgSuburbSelectPanel = $('#shipping_tcg_place_lookup_field');
            toggleSuburbPanelDisplay($(this), tcgSuburbSelectPanel);
        });
        if (theCourierGuy.southAfricaOnly === 'true') {
            var billingCountry = $('#billing_country');
            if (billingCountry.length > 0) {
                billingCountry.trigger('change');
            }
            var shippingCountry = $('#shipping_country');
            if (shippingCountry.length > 0) {
                shippingCountry.trigger('change');
            }
        }

        function triggerPlaceSelect(targetSelect, sourceSelect) {
            var placeLabelInput = sourceSelect.prev('input');
            var placeIdInput = placeLabelInput.prev('input');
            var newOption = new Option(placeLabelInput.val(), placeIdInput.val(), true, true);
            targetSelect.append(newOption).trigger('change');
            targetSelect.val(placeIdInput.val());
            targetSelect.trigger('change');
        }

        $('#ship-to-different-address-checkbox').on('change', function () {
            var shipToDifferentAddressValue = $(this).prop('checked');
            var billingInsuranceElement = $('#billing_insurance');
            var shippingInsuranceElement = $('#shipping_insurance');

            var shippingPlaceSelect = $('#shipping_tcg_place_lookup');
            var billingPlaceSelect = $('#billing_tcg_place_lookup');

            if (shipToDifferentAddressValue === true) {
                $('#billing_insurance_field').hide();
                $('#billing_tcg_place_lookup_field').hide();
                triggerPlaceSelect(shippingPlaceSelect, billingPlaceSelect);
                if (billingInsuranceElement.prop('checked') === true) {
                    shippingInsuranceElement.prop('checked', true).attr('checked', 'checked').trigger('change');
                } else {
                    shippingInsuranceElement.prop('checked', false).removeAttr('checked').trigger('change');
                }
            } else {
                $('#billing_insurance_field').show();
                if ($('#billing_country').val() === 'ZA' || theCourierGuy.southAfricaOnly === 'false') {
                    $('#billing_tcg_place_lookup_field').show();
                }
                triggerPlaceSelect(billingPlaceSelect, shippingPlaceSelect);
                if (shippingInsuranceElement.prop('checked') === true) {
                    billingInsuranceElement.prop('checked', true).attr('checked', 'checked').trigger('change');
                } else {
                    billingInsuranceElement.prop('checked', false).removeAttr('checked').trigger('change');
                }
            }
        });

        //Admin
        var suburbAdminSelect = $('select.tcg-suburb-field');
        if (suburbAdminSelect.length > 0) {
            suburbAdminSelect.select2({
                placeholder: "Start typing Suburb name...",
                minimumInputLength: 3,
                ajax: {
                    url: theCourierGuy.url,
                    dataType: 'json',
                    delay: 350,
                    data: function (term) {
                        return {
                            q: term, // search term
                            action: 'wc_tcg_get_places'
                        };
                    },
                    closeOnSelect: true,
                    processResults: function (data) {
                        var results = [];
                        $.each(data, function (index, item) {
                            results.push({
                                id: item.suburb_key,
                                text: item.suburb_value
                            });
                        });
                        return {
                            results: results,
                        };
                    },
                    cache: false
                }
            }).on("change", function (evt) {
                $(this).children().each(function (k, v) {
                    if (k == 1) {
                        $('#woocommerce_the_courier_guy_shopPlace').val($(v).text());
                    }
                });
            });
        }
        if (typeof woocommerce_admin !== 'undefined') {
            woocommerce_admin['i18n_disclaimer_error'] = 'Please accept this disclaimer.';
            woocommerce_admin['i18n_dimension_required'] = 'Please enter a dimension here';

            $(document.body).on('submit', '.woocommerce #mainform', function (event) {
                var preventFormSubmission = false;
                var productQuantityPerParcelElements = $('.wc_input_tcg_text_with_disclaimer');
                $.each(productQuantityPerParcelElements, function (index, item) {
                    var itemElement = $(item);
                    var itemDisclaimerElement = $('#' + itemElement.attr('id') + '_disclaimer');
                    var itemValue = itemElement.val();
                    var itemPlaceholder = itemElement.attr('placeholder');
                    if (itemValue !== '' && itemValue !== itemPlaceholder && itemDisclaimerElement.prop('checked') !== true) {
                        preventFormSubmission = true;
                        $(document.body).triggerHandler('wc_add_error_tip', [itemDisclaimerElement, 'i18n_disclaimer_error']);
                    }
                });
                var productQuantityPerParcelElement = parseInt(productQuantityPerParcelElements[0].value);
                if (productQuantityPerParcelElement > 1) {
                    var globalParcelDimensions = [];
                    var globalParcelDimensionLength = $('input[name$="length_per_parcel"]');
                    globalParcelDimensions.push(globalParcelDimensionLength);
                    var globalParcelDimensionWidth = $('input[name$="width_per_parcel"]');
                    globalParcelDimensions.push(globalParcelDimensionWidth);
                    var globalParcelDimensionHeight = $('input[name$="height_per_parcel"]');
                    globalParcelDimensions.push(globalParcelDimensionHeight);
                    $.each(globalParcelDimensions, function (index, item) {
                        if (!parseInt(item[0].value) > 0) {
                            preventFormSubmission = true;
                            item[0].value = 'This field is required';
                            $(document.body).triggerHandler('wc_add_error_tip', [item, 'i18n_dimension_required']);
                        }
                    });
                }
                if (preventFormSubmission === true) {
                    event.preventDefault();
                }
            });
        }

        var overrideSelects = $('.tcg-override-per-service');
        var overrideInputs = $('.tcg-override-per-service-input');
        overrideSelects.on('change', function () {
            var selectedOptionValue = $(this).children('option:selected').val();
            if (selectedOptionValue !== '') {
                $(this).nextAll('span').hide();
                $(this).nextAll('span.tcg-override-per-service-span-' + selectedOptionValue).show();
            }
        });
        overrideInputs.on('blur', function () {
            var overrideSelect = $(this).parent('span').prevAll('select.tcg-override-per-service');
            var overrideValues = {};
            overrideSelect.nextAll('span').each(function () {
                var input = $(this).children('input');
                var serviceId = input.data('service-id');
                var overrideSelectOption = overrideSelect.find('option[value="' + serviceId + '"]');
                var serviceLabel = overrideSelectOption.data('service-label');
                var overrideValue = input.val();
                if (overrideValue !== '') {
                    var prefix = ' - ';
                    if (input.hasClass('wc_input_price')) {
                        prefix = ' - R ';
                        input.val(parseFloat(overrideValue).toFixed(2));
                        overrideValue = input.val();
                    }
                    overrideValues[serviceId] = overrideValue;
                    serviceLabel = serviceLabel + prefix + overrideValue;
                }
                overrideSelectOption.html(serviceLabel);
            });
            if (Object.keys(overrideValues).length > 0) {
                overrideSelect.nextAll('input').val(JSON.stringify(overrideValues));
            } else {
                overrideSelect.nextAll('input').val('');
            }
        });
    });
})(jQuery);
