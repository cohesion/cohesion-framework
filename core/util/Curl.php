<?php
namespace Cohesion\Util;

class Curl {

    public static function getJSON($url, $fields = array()) {
        $fields_string = '';
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . urlencode($value) . '&';
        }

        rtrim($fields_string, '&');

        $ch = curl_init($url . '?' . $fields_string);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json'
        ));

        $response = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        curl_close($ch);

        if ($response) {
            list($header, $body) = explode("\r\n\r\n", $response, 2);
            return array('header' => $header, 'body' => json_decode($body, true));
        } else {
            throw new CurlException('Unable to contact ' . $url);
        }
    }

    public static function postJSON($url, $fields = array()) {
        $data = json_encode($fields);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
        );
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        curl_close($ch);

        return array('header' => $header, 'body' => json_decode($body, true));
    }
}

class CurlException extends RuntimeException {}

