<?php

require '../resources/api_client/vendor/autoload.php';
require 'ILSClient.php';

class KohaClient implements ILSClient {

    private $server;
    private $coralToKohaKeys;
    private $kohaToCoralKeys;

    function __construct() {
        $config = new Configuration();
        $this->server = $config->ils->ilsApiUrl;
        $this->coralToKohaKeys = array("companyURL" => "url", "noteText" => "notes", "accountDetailText" => "accountnumber");
        $this->kohaToCoralKeys = array_flip($this->coralToKohaKeys);
    }

    function addVendor($vendor) {
        $headers = array("Accept" => "application/json");
        $body = Unirest\Request\Body::json($this->_vendorToKoha($vendor));
        $response = Unirest\Request::post($this->server . "/acquisitions/vendors", $headers, $body);
        return ($response->body->id) ? $response->body->id : null;
        //return ($response->body->id) ? $response->body->id : $response->raw_body;
    }

    function getVendor($id) {
        $response = Unirest\Request::get($this->server . "/acquisitions/vendors/$id");
        return $this->_vendorToCoral((array) $response->body);
    }

    function getILSName() {
        return "Koha";
    }
    

    function getILSURL() {
        return $this->server;
    }

    private function _vendorToCoral($vendor) {
        $kohaToCoralKeys = $this->kohaToCoralKeys;
        return $this->_changeKeys($vendor, $kohaToCoralKeys);
    }

    private function _vendorToKoha($vendor) {
        $coralToKohaKeys = $this->coralToKohaKeys;
        return $this->_changeKeys($vendor, $coralToKohaKeys);
    }

    private function _changeKeys($array, $keys) {
        foreach ($keys as $oldKey => $newKey) {
            if (array_key_exists($oldKey, $array)) {
                $array[$newKey] = $array[$oldKey];
                unset($array[$oldKey]);
            }
        }
        return $array;
    }

}

?>
