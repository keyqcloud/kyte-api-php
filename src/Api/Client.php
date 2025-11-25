<?php
/**
 * Kyte API PHP Client
 * Updated to match Kyte.JS v1.2.23
 *
 * Copyright 2020-2025 KeyQ, Inc.
 * MIT License
 */

namespace Kyte\Api;

class Client
{
    // Current API credentials
    private $public_key;
    private $private_key;
    private $kyte_account;
    private $kyte_identifier;
    private $kyte_endpoint;
    private $kyte_app_id;

    // Session tokens
    private $sessionToken = '0';
    private $transactionToken = '0';

    // Store initial values for API handoff reset
    private $initial_public_key;
    private $initial_kyte_account;
    private $initial_kyte_identifier;

    // Login field names (customizable)
    private $username_field = 'email';
    private $password_field = 'password';

    public function __construct($public_key, $private_key, $kyte_account, $kyte_identifier, $kyte_endpoint, $kyte_app_id = null)
    {
        $this->public_key = $public_key;
        $this->private_key = $private_key;
        $this->kyte_account = $kyte_account;
        $this->kyte_identifier = $kyte_identifier;
        $this->kyte_endpoint = rtrim($kyte_endpoint, '/');
        $this->kyte_app_id = $kyte_app_id;

        // Store initial values for potential reset (API handoff feature)
        $this->initial_public_key = $public_key;
        $this->initial_kyte_account = $kyte_account;
        $this->initial_kyte_identifier = $kyte_identifier;
    }

    private function getIdentity($timestamp)
    {
        $identityStr = $this->public_key . "%" . $this->sessionToken . "%" . $timestamp . "%" . $this->kyte_account;
        $identityStr = base64_encode($identityStr);
        return urlencode($identityStr);
    }

    private function getSignature($epoch)
    {
        // Use actual transaction token (matches JS behavior)
        $key1 = hash_hmac('sha256', $this->transactionToken, $this->private_key, true);
        $key2 = hash_hmac('sha256', $this->kyte_identifier, $key1, true);
        return hash_hmac('sha256', $epoch, $key2);
    }

    public function request($method, $model, $field = null, $value = null, $data = null, $headers = [])
    {
        $date = new \DateTime();
        $epoch = $date->getTimestamp();
        $timestamp = gmdate('D, d M Y H:i:s T', $epoch);
        $signature = $this->getSignature((string) $epoch);
        $identity = $this->getIdentity($timestamp);

        // Build endpoint URL with proper encoding (matches JS behavior)
        $endpoint = $this->kyte_endpoint . "/" . $model;
        if ($field !== null) {
            $endpoint .= "/" . urlencode($field);
        }
        if ($value !== null) {
            $endpoint .= "/" . urlencode($value);
        }

        // Prepare headers (matches JS sendData method)
        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json',
            'x-kyte-signature: ' . $signature,
            'x-kyte-identity: ' . $identity,
            'x-kyte-device: Kyte-PHP-Client/1.2.23'  // Added device header
        ];

        if ($this->kyte_app_id !== null) {
            $defaultHeaders[] = 'x-kyte-appid: ' . $this->kyte_app_id;
        }

        $headers = array_merge($defaultHeaders, $headers);

        $curl = curl_init();

        switch (strtoupper($method)) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case "GET":
                // Default is GET
                break;
            case "DELETE":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            default:
                throw new \Exception("Unknown method $method. Supported methods are POST, PUT, GET, and DELETE.");
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Improved error handling
        if ($result === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new \Exception("cURL error: $error");
        }

        curl_close($curl);

        $response = json_decode($result, true);

        // Handle HTTP error responses
        if ($httpCode >= 400) {
            $errorMsg = isset($response['error']) ? $response['error'] : "HTTP $httpCode error";
            throw new \Exception($errorMsg);
        }

        // Update tokens from response (matches JS behavior - lines 180-181, 234-235)
        if (isset($response['session'])) {
            $this->sessionToken = $response['session'];
        }
        if (isset($response['token'])) {
            $this->transactionToken = $response['token'];
        }

        // Handle API key handoff (matches JS lines 182-198, 236-252)
        if (isset($response['kyte_pub']) && isset($response['kyte_iden']) && isset($response['kyte_num'])) {
            // Update to handoff credentials
            $this->public_key = $response['kyte_pub'];
            $this->kyte_identifier = $response['kyte_iden'];
            $this->kyte_account = $response['kyte_num'];
        } else {
            // Reset to initial credentials if handoff not present
            $this->public_key = $this->initial_public_key;
            $this->kyte_identifier = $this->initial_kyte_identifier;
            $this->kyte_account = $this->initial_kyte_account;
        }

        return $response;
    }

    public function post($model, $data, $headers = [])
    {
        return $this->request('post', $model, null, null, $data, $headers);
    }

    public function put($model, $field, $value, $data, $headers = [])
    {
        return $this->request('put', $model, $field, $value, $data, $headers);
    }

    public function get($model, $field = null, $value = null, $headers = [])
    {
        return $this->request('get', $model, $field, $value, null, $headers);
    }

    public function delete($model, $field, $value, $headers = [])
    {
        return $this->request('delete', $model, $field, $value, null, $headers);
    }

    public function createSession($username, $password)
    {
        $result = $this->post('Session', [
            $this->username_field => $username,
            $this->password_field => $password
        ]);

        // Update tokens (matches JS sessionCreate - lines 425-438)
        if (isset($result['token'])) {
            $this->transactionToken = $result['token'];
        }
        if (isset($result['session'])) {
            $this->sessionToken = $result['session'];
        }

        // Handle API key handoff from login response
        if (isset($result['kyte_pub'])) {
            $this->public_key = $result['kyte_pub'];
        }
        if (isset($result['kyte_iden'])) {
            $this->kyte_identifier = $result['kyte_iden'];
        }
        if (isset($result['kyte_num'])) {
            $this->kyte_account = $result['kyte_num'];
        }

        return $result;
    }

    /**
     * Destroy session (logout)
     * Matches sessionDestroy() in Kyte.JS (lines 516-548)
     */
    public function destroySession()
    {
        try {
            $this->delete('Session', null, null);
        } catch (\Exception $e) {
            // Ignore errors during logout
        }

        // Reset tokens
        $this->sessionToken = '0';
        $this->transactionToken = '0';

        // Reset to initial API credentials
        $this->public_key = $this->initial_public_key;
        $this->kyte_identifier = $this->initial_kyte_identifier;
        $this->kyte_account = $this->initial_kyte_account;
    }

    /**
     * Get current session token
     */
    public function getSessionToken()
    {
        return $this->sessionToken;
    }

    /**
     * Get current transaction token
     */
    public function getTransactionToken()
    {
        return $this->transactionToken;
    }

    /**
     * Set session token (for restoring sessions)
     */
    public function setSessionToken($token)
    {
        $this->sessionToken = $token;
    }

    /**
     * Set transaction token (for restoring sessions)
     */
    public function setTransactionToken($token)
    {
        $this->transactionToken = $token;
    }
}

?>
