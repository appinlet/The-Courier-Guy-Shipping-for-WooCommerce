<?php

/**
 * @author  Clint Lynch
 * @package ls-framework/core
 * @version 1.0.0
 */
class CurlController
{

    private $endPoint = '';
    private $options = [];
    private $credentials = [];

    /**
     * CurlController constructor.
     * @param string $endPoint
     * @param array $options
     * @param array $credentials
     */
    public function __construct($endPoint, $options = [], $credentials = [])
    {
        $this->setEndPoint($endPoint);
        $this->setOptions($options);
        $this->setCredentials($credentials);
    }

    /**
     * @param array $payloadData
     * @return mixed
     */
    public function execute($payloadData = [])
    {
        global $TCG_Plugin;
        $parameters = [
            'headers' => [],
            'timeout' => 30,
        ];
        $options = $this->getOptions();
        $credentials = $this->getCredentials();
        if (!empty($credentials)) {
            $encodedCredentials = base64_encode($credentials['username'] . ':' . $credentials['password']);
            $parameters['headers']['Authorization'] = 'Basic ' . $encodedCredentials;
        }
        $endPoint = $this->getEndPoint();
        if (!empty($options['method']) && $options['method'] == 'post') {
            $parameters['headers']['Content-Type'] = 'application/json';
            if (!empty($payloadData)) {
                $parameters['body'] = json_encode($payloadData);
            }
            $result = wp_remote_post($endPoint, $parameters);
        } else {
            if (!empty($payloadData)) {
                $endPoint = $endPoint . '&' . http_build_query($payloadData, null, '&');
            }
            $result = wp_remote_get($endPoint, $parameters);
        }
        $uploadsDirectory = $TCG_Plugin->getPluginUploadPath();
        $logFilePath = $uploadsDirectory . '/log.txt';
        file_put_contents($logFilePath, date('Y-m-d H:i:s') . ' - ' . $endPoint . ' - ' . json_encode($result) . PHP_EOL, FILE_APPEND);
        return $result;
    }

    /**
     * @return mixed
     */
    public function getEndPoint()
    {
        return $this->endPoint;
    }

    /**
     * @param string $endPoint
     */
    public function setEndPoint($endPoint)
    {
        $this->endPoint = $endPoint;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * @param array $credentials
     */
    public function setCredentials(array $credentials)
    {
        $this->credentials = $credentials;
    }
}
