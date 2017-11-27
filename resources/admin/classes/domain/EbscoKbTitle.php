<?php

class EbscoKbTitle extends EbscoKbResult {

    public function getIsPeerReviewed($value)
    {
        return $value ? 'Yes' : 'No';
    }

    public function getCustomerResourcesList($value)
    {
        return array_map(function($resource){
            return new EbscoKbCustomerResource($resource);
        }, $value);
    }

    public function getSubjects()
    {
        return array_map(function($subject){
            return $subject['subject'];
        }, $this->subjectsList);
    }
}