<?php
/**
 * @author  Clint Lynch
 * @package tcg/core
 * @version 1.0.0
 */
$apiEndpoint = 'http://tcgweb16931.pperfect.com/ecomService/v7/Json/';
global $curlControllers;
$curlControllers = [
    'api-pp-get-salt' => new CurlController($apiEndpoint . '?class=Auth&method=getSalt'),
    'api-pp-get-token' => new CurlController($apiEndpoint . '?class=Auth&method=getSecureToken'),
    'api-pp-get-places-by-name' => new CurlController($apiEndpoint . '?class=Quote&method=getPlacesByName'),
    'api-pp-get-quote' => new CurlController($apiEndpoint . '?class=Quote&method=requestQuote'),
    'api-pp-set-service' => new CurlController($apiEndpoint . '?class=Quote&method=updateService'),
    'api-pp-set-collection' => new CurlController($apiEndpoint . '?class=Collection&method=submitCollection'),
];
