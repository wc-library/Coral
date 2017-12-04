<?php

class EbscoKbPackage extends EbscoKbResult {

    public function getContentType($value)
    {
        return preg_replace('/(?<!^)([A-Z])/', ' \\1', $value);
    }

    public function getTitles()
    {
        $ebscoKb = EbscoKbService::getInstance();
        $ebscoKb->createQuery(['vendorId' => $this->vendorId, 'packageId' => $this->packageId]);
        $ebscoKb->execute();
        return $ebscoKb->results();
    }
}