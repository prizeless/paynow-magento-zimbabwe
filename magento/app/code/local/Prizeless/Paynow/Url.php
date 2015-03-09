<?php

class Prizeless_Paynow_Url
{

    private $url = null;

    private $forceSsl = null;

    private $curl = null;

    public function __construct($url, $forceSsl = false)
    {
        $this->forceSsl = $forceSsl;

        $this->url = $url;

        if (empty($this->url) === true) {
            throw new \RuntimeException('Url can not be bank');
        }

        $this->getCurl();
    }

    public function encodeUrl($url)
    {
        $cleanUrl = $this->isValidUrl($url);

        return $cleanUrl == false ? false : urlencode($cleanUrl);
    }

    public function decodeUrl($url)
    {
        $cleanUrl = $this->isValidUrl($url);

        return $cleanUrl == false ? false : urldecode($cleanUrl);
    }

    public function isValidUrl()
    {
        $cleanUrl = filter_var($this->url, FILTER_SANITIZE_URL);

        if (filter_var($cleanUrl, FILTER_VALIDATE_URL) === false) {
            throw new \Exception('Invalid url supplied');
        }
    }

    public function runCurlPassiveGet(array $extraOptions = array())
    {
        curl_setopt($this->curl, CURLOPT_NOBODY, true);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 10);
        $this->forceSsl();
        $this->setCurlExtraOptions($extraOptions);
        $this->executeCommand();
        $response = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->closeConnection();

        return $response == 200 ? true : false;
    }

    private function getCurl()
    {
        if (empty($this->curl) === true) {
            $this->curl = curl_init($this->url);
        }

        return $this->curl;
    }

    public function setCurl($curl = '')
    {
        $this->curl = $curl;
    }

    private function forceSsl()
    {
        if ($this->forceSsl === true) {
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, true);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, true);
        } else {
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        }
    }

    private function setCurlExtraOptions(array $options)
    {
        if (empty($options) === true) {
            return false;
        }

        foreach ($options as $key => $val) {
            curl_setopt($this->curl, $key, $val);
        }
    }

    public function runCurlActivePost($postFields, array $extraOptions = array(), $debug = 0)
    {
        $addHeaders = array(
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => $this->makePostFieldsString($postFields)
        );
        $headers = count($extraOptions) <= 0 ? $addHeaders : $addHeaders + $extraOptions;

        return $this->runCurlActiveGet($headers, $debug);
    }

    private function makePostFieldsString($postFields)
    {
        if (is_array($postFields) === false) {
            return $postFields;
        }
        $fields = array();

        foreach ($postFields as $key => $val) {
            if (is_array($val)) {
                array_push($fields, $this->loopInner($key, $val));
            } else {
                array_push($fields, $key . '=' . urlencode($val));
            }
        }

        return implode('&', $fields);
    }

    private function loopInner($keyName, array $newArray)
    {
        $handle = array();
        $keys = stristr($keyName, '[') ? $keyName : $keyName . '[]';
        for ($i = 0; $i < count($newArray); $i++) {
            array_push($handle, $keys . '=' . urlencode($newArray[$i]));
        }

        return implode('&', $handle);
    }

    public function runCurlActiveGet(array $extraOptions = array(), $debug = 0)
    {
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 0);
        $this->forceSsl(); //

        $this->setCurlExtraOptions($extraOptions);

        $data = $this->executeCommand();

        $response = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        if ($debug == 1) {
            print_r(curl_getinfo($this->curl));
            echo $response;
        }
        if ($response != 200 && $response != 201 && $response != 202) {
            return false;
        }

        return urldecode($data);
    }

    private function executeCommand()
    {
        return curl_exec($this->curl);
    }

    private function closeConnection()
    {
        curl_close($this->curl);
    }

    public function parseMessage($msg)
    {
        $parts = explode("&", $msg);
        $result = array();
        foreach ($parts as $i => $value) {
            $bits = explode("=", $value, 2);
            $result[$bits[0]] = urldecode($bits[1]);
        }

        return $result;
    }
}
