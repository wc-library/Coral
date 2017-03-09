<?php

require '../resources/api_client/vendor/autoload.php';
require 'ILSClient.php';

class KohaClient implements ILSClient {

    private $server;

    function __construct() {
        $config = new Configuration();
        $this->server = $config->ils->ilsApiUrl;
    }

    function addVendor($vendor) {
        $headers = array("Accept" => "application/json");
        $body = Unirest\Request\Body::json($this->_vendorToKoha($vendor));
        $response = Unirest\Request::post($this->server . "/acquisitions/vendors", $headers, $body);
        return ($response->body->id) ? $response->body->id : null;
        //return ($response->body->id) ? $response->body->id : $response->raw_body;
    }

    function getVendor() {
        $response = Unirest\Request::get($this->server . "/acquisitions/vendors/");
        return "Getting vendor from koha";
    }

    function getILSName() {
        return "Koha";
    }
    

    function getILSURL() {
        return $this->server;
    }

    private function _vendorToKoha($vendor) {
        $coralToKohaKeys = array("companyURL" => "url", "noteText" => "notes");
        foreach ($coralToKohaKeys as $coralKey => $kohaKey) {
            if (array_key_exists($coralKey, $vendor)) {
                $vendor[$kohaKey] = $vendor[$coralKey];
                unset($vendor[$coralKey]);
            }
        }
        return $vendor;
    }

}

?>
