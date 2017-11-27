<?php

class EbscoKbPackage extends EbscoKbResult {

    public function getContentType($value)
    {
        return preg_replace('/(?<!^)([A-Z])/', ' \\1', $value);
    }
}