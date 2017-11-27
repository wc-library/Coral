<?php

class EbscoKbCustomerResource extends EbscoKbResult {

    public function getCoverageStatement($value)
    {
        if(!empty($value)){
            return $value;
        }

        $coverage = [];
        foreach($this->managedCoverageList as $c){
            $begin = date_create_from_format('Y-m-d', $c['beginCoverage']);
            if(empty($c['endCoverage'])){
                $coverage[] = 'from '.date_format($begin,'F Y');
            } else {
                $end = date_create_from_format('Y-m-d', $c['endCoverage']);
                $coverage[] = date_format($begin,'F Y').' to '.date_format($end, 'F Y');
            }

        }
        return implode(', ',$coverage);
    }

    public function getEmbargoStatement()
    {
        if(empty($this->managedEmbargoPeriod['embargoUnit'])){
            return '';
        }

        return $this->managedEmbargoPeriod['embargoValue'].' '.$this->managedEmbargoPeriod['embargoUnit'];
    }
}