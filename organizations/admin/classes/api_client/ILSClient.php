<?php

interface ILSClient {
    function addVendor($vendor);
    function getVendor();
    function getILSName();
    function getILSURL();
}

?>
