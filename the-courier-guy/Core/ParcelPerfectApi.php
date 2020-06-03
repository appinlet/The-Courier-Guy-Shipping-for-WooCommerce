<?php

/**
 * @author  Clint Lynch
 * @package tcg/core
 * @version 1.0.0
 */
class ParcelPerfectApi
{

    private $emailAddress = '';
    private $password = '';
    private $apiToken = '';
    private $authorizationAttemptCount = 0;

    /**
     * ParcelPerfectApi constructor.
     * @param $emailAddress
     * @param $password
     */
    public function __construct($emailAddress, $password)
    {
        $this->setEmailAddress($emailAddress);
        $this->setPassword($password);
    }

    /**
     * @param array $payloadData
     * @return string
     */
    public function getPlacesByName($payloadData)
    {
        return $this->executeSecureOperation('api-pp-get-places-by-name', $payloadData);
    }

    /**
     * @param array $payloadData
     * @return string
     */
    public function setService($payloadData)
    {
        return $this->executeSecureOperation('api-pp-set-service', $payloadData);
    }

    /**
     * @param array $payloadData
     * @return string
     */
    public function setCollection($payloadData)
    {
        return $this->executeSecureOperation('api-pp-set-collection', $payloadData);
    }

    /**
     * @param array $payloadData
     * @return string
     */
    public function getQuote($payloadData)
    {
        return $this->executeSecureOperation('api-pp-get-quote', $payloadData);
    }

    /**
     *
     */
    private function authorizeApiConnection()
    {
        $apiToken = $this->getApiToken();
        if (empty($apiToken)) {
            $this->incrementAuthorizationAttemptCount();
            $emailAddress = $this->getEmailAddress();
            $payloadData = $this->formatPayloadData([
                'email' => $emailAddress,
            ]);
            $apiSalt = $this->getApiSalt($payloadData);
            if (!empty($apiSalt)) {
                $password = $this->getPassword();
                $passwordHash = md5($password . $apiSalt);
                $payloadData = $this->formatPayloadData([
                    'email' => $emailAddress,
                    'password' => $passwordHash,
                ]);
                $response = $this->executeOperation('api-pp-get-token', $payloadData);
                if (!empty($response)) {
                    $this->setApiToken($response[0]['token_id']);
                }
            }
        }
    }

    /**
     * @param array $payloadData
     * @return string
     */
    private function getApiSalt($payloadData)
    {
        $result = '';
        if (!empty($payloadData)) {
            $response = $this->executeOperation('api-pp-get-salt', $payloadData);
            if (!empty($response)) {
                $result = $response[0]['salt'];
            }
        }
        return $result;
    }

    private function getResponseResults($response, $operationIdentifier, $payloadData)
    {
        $results = [];
        $responseBody = $response['body'];
        if ($responseBody) {
            $responseBody = json_decode($responseBody, true);
            if ($responseBody['errorcode'] == 0) {
                $results = $responseBody['results'];
            } else {
                $this->setApiToken('');
                /*if ($responseBody['errormessage'] == 'Invalid security token') {
                    $authorizationAttemptCount = $this->getAuthorizationAttemptCount();
                    if ($authorizationAttemptCount < 2) {
                        $this->authorizeApiConnection();
                        //@internal Note that we don't call executeSecureOperation, as the $payloadData params variable has already been formatted and the token has already been added, if required.
                        $this->executeOperation($operationIdentifier, $payloadData);
                    }
                }*/
            }
        }
        return $results;
    }

    /**
     * @param array $payloadData
     * @return array
     */
    private function formatPayloadData($payloadData)
    {
        $payloadData = [
            'params' => json_encode($payloadData),
        ];
        return $payloadData;
    }

    /**
     * @param string $operationIdentifier
     * @param array $payloadData
     * @return string
     */
    private function executeSecureOperation($operationIdentifier, $payloadData)
    {
        $result = '';
        if (!empty($payloadData)) {
            $this->authorizeApiConnection();
            $apiToken = $this->getApiToken();
            if (!empty($apiToken)) {
                $payloadData = $this->formatPayloadData($payloadData);
                $payloadData['token_id'] = $apiToken;
                $result = $this->executeOperation($operationIdentifier, $payloadData);
            }
        }
        return $result;
    }

    /**
     * @param string $operationIdentifier
     * @param array $payloadData
     * @return string
     */
    private function executeOperation($operationIdentifier, $payloadData)
    {
        $result = '';
        if (!empty($operationIdentifier) && !empty($payloadData)) {
            global $curlControllers;
            $result = $curlControllers[$operationIdentifier]->execute($payloadData);
        }
        return $this->getResponseResults($result, $operationIdentifier, $payloadData);
    }

    /**
     * @return string
     */
    private function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @param string $emailAddress
     */
    private function setEmailAddress($emailAddress)
    {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @return string
     */
    private function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    private function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    private function getApiToken()
    {
        if (empty($this->apiToken)) {
            $storedApiToken = get_option('tcg_pp_api_token', '');
            if (!empty($storedApiToken)) {
                $this->apiToken = $storedApiToken;
            }
        }
        return $this->apiToken;
    }

    /**
     * @param string $apiToken
     */
    private function setApiToken($apiToken)
    {
        $this->apiToken = $apiToken;
        //@internal This must persist on a DB level, as all previous apiTokens expire when a new token is generated, despite the documentation saying otherwise.
        //I would have liked to have requested a new token every session, but alas, this is not to be.
        update_option('tcg_pp_api_token', $this->apiToken);
    }

    /**
     * @return int
     */
    private function getAuthorizationAttemptCount()
    {
        return $this->authorizationAttemptCount;
    }

    /**
     * @param int $authorizationAttemptCount
     */
    private function setAuthorizationAttemptCount($authorizationAttemptCount)
    {
        $this->authorizationAttemptCount = $authorizationAttemptCount;
    }

    /**
     *
     */
    private function incrementAuthorizationAttemptCount()
    {
        $authorizationAttemptCount = $this->getAuthorizationAttemptCount();
        $this->setAuthorizationAttemptCount(($authorizationAttemptCount + 1));
    }
}
