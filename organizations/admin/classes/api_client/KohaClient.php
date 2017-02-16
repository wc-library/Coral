<?php

class KohaClient implements ILSClient {

    function addVendor() {
        return "Adding vendor to koha";
    }

    function getVendor() {
        return "Getting vendor from koha";
    }

    function getILSVersion() {
        return "Koha";
    }

}

?>
