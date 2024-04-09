<?php
namespace Kyte\Api;

class Client
{
    private $public_key;
    private $private_key;
    private $kyte_account;
    private $kyte_identifier;
    private $kyte_endpoint;
    private $kyte_app_id;
    private $sessionToken = '0';
    private $transactionToken = '0';
    private $username_field = 'email';
    private $password_field = 'password';

    public function __construct($public_key, $private_key, $kyte_account, $kyte_identifier, $kyte_endpoint, $kyte_app_id = null)
    {
        $this->public_key = $public_key;
        $this->private_key = $private_key;
        $this->kyte_account = $kyte_account;
        $this->kyte_identifier = $kyte_identifier;
        $this->kyte_endpoint = $kyte_endpoint;
        $this->kyte_app_id = $kyte_app_id;
    }

    private function getIdentity($timestamp)
    {
        $identityStr = $this->public_key . "%" . $this->sessionToken . "%" . $timestamp . "%" . $this->kyte_account;
        $identityStr = base64_encode($identityStr);
        return urlencode($identityStr);
    }

    private function getSignature($epoch)
    {
        $txToken = '0';
        $key1 = hash_hmac('sha256', $txToken, $this->private_key, true);
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

        $endpoint = $this->kyte_endpoint . "/" . $model;
        if ($field !== null && $value !== null) {
            $endpoint .= "/" . $field . "/" . $value;
        }

        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json',
            'x-kyte-signature: ' . $signature,
            'x-kyte-identity: ' . $identity,
        ];

        if ($this->kyte_app_id !== null) {
            array_push($defaultHeaders, 'x-kyte-appid: ' . $this->kyte_app_id);
        }

        $headers = array_merge($defaultHeaders, $headers);

        $curl = curl_init();

        switch (strtolower($method)) {
            case "post":
                curl_setopt($curl, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case "put":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case "get":
                // Default is GET
                break;
            case "delete":
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

        if ($result === false || $httpCode != 200) {
            // Handle error; omitted for brevity
        }

        curl_close($curl);

        $response = json_decode($result, true);

        $this->sessionToken = $response['session'] ?? $this->sessionToken;
        $this->transactionToken = $response['token'] ?? $this->transactionToken;

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
        $result = $this->post('Session', [$this->username_field => $username, $this->password_field => $password], []);
        if (isset($result['sessionToken']) && isset($result['txToken'])) {
            $this->sessionToken = $result['sessionToken'];
            $this->transactionToken = $result['txToken'];
        }
        return $result;
    }
}

?>
