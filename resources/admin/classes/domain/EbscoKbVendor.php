<?php

class EbscoKbVendor extends EbscoKbResult {

    static $packageTemplateFile = __DIR__.'/../../../templates/ebscoKbPackageList.php';

    public function getPackages($value)
    {
        $ebscoKb = new EbscoKbService();
        return $ebscoKb->getPackages($this->vendorId);
    }

    public function renderPackageList()
    {
        ob_start();
        $items = $this->packages;
        include_once self::$packageTemplateFile;
        return ob_get_clean();
    }

}