<?php
/**
 * Created by Riat Abduramani.
 * Date: 11/14/2018
 * Time: 12:25 PM
 */

namespace GameOfThree;

class Api
{
    public $call_service;
    private $api_username = 'cdb69e33-308c-40fa-9063-1c0a46308408';
    private $api_password = 'a9e6f112-b556-49e3-880f-eb596092e149';
    private $endpoint = 'https://gameofthree.restlet.net/v1/';
    private $service_url;
    private $request_method;
    private $request = array();

    public function get()
    {
        $this->service_url = $this->endpoint . $this->call_service;
        $this->request_method = "GET";
        $response = $this->send();

        if (isset($response['code']) && $response['code'] != 200) {
            return "Error: " . $response['description'];
        }

        return $response;
    }

    private function send()
    {

        $curl = curl_init();

        $login_hash = base64_encode($this->api_username . ":" . $this->api_password);

        if (!empty($this->request) && is_array($this->request)) {
            curl_setopt_array(
                $curl, array(CURLOPT_POSTFIELDS => json_encode($this->request, true))
            );
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->service_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $this->request_method,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic $login_hash",
                "Content-Type: application/json",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $response = "cURL Error #:" . $err;
        } else {
            $response = json_decode($response, true);
        }

        return $response;
    }

    public function post(array $request)
    {
        $this->request = $request;
        $this->service_url = $this->endpoint . $this->call_service;
        $this->request_method = "POST";
        $response = $this->send();

        if (isset($response['code']) && $response['code'] != 200) {
            return "Error: " . $response['description'];
        }

        return $response;
    }

    public function put(array $request)
    {
        $this->request = $request;
        $this->service_url = $this->endpoint . $this->call_service;
        $this->request_method = "PUT";
        $response = $this->send();

        if (isset($response['code']) && $response['code'] != 200) {
            return "Error: " . $response['description'];
        }

        return $response;
    }

    public function delete()
    {
        $this->service_url = $this->endpoint . $this->call_service;

        $this->request_method = "DELETE";
        $response = $this->send();

        if (isset($response['code']) && $response['code'] != 200) {
            return "Error: " . $response['description'];
        }

        return $response;
    }

}