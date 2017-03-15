<?php

interface ILSClient {
    function addVendor($vendor);
    function getVendor($id);
    function getILSName();
    function getILSURL();
}

?>
