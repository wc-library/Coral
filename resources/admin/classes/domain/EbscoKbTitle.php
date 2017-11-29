<?php

class EbscoKbTitle extends EbscoKbResult {

    public $hasDbResource = null;
    public $resource = null;

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

    public function getIsxns()
    {
        return array_unique(array_filter(array_map(function($identifier){
            if(in_array($identifier['type'], [0,1,2])){
                return $identifier['id'];
            }
        }, $this->identifiersList)));
    }

    public function getSubjects()
    {
        return array_map(function($subject){
            return $subject['subject'];
        }, $this->subjectsList);
    }

    public function getAccessibleUrls()
    {
        $accessibleUrls = [];
        foreach($this->customerResourcesList as $key => $value){
            if($value->isSelected){
                $accessibleUrls[$key] = $value->url;
            }
        }
        return $accessibleUrls;
    }

    public function getCoverageTextArray()
    {

        $statements = [];
        foreach($this->customerResourcesList as $resource){
            if(!$resource->isSelected){
                continue;
            }
            $coverage = $resource->coverageStatement;
            $embargo = $resource->embargoStatement;
            if(!empty($coverage) || !empty($embargo)){
                $statement = $resource->packageName.': ';
                if(!empty($coverage)){
                    $statement .= $coverage;
                }
                if(!empty($embargo)){
                    $statement .= " (embargo: $embargo)";
                }
                $statements[] = $statement;
            }

        }
        return $statements;
    }

    public function sortUrlsByCoverage()
    {
        $urls = [];

        foreach($this->customerResourcesList as $key => $value){
            if(!$value->isSelected){
                continue;
            }

            $coverageSpan  = 0; // Larger is better
            $age = 100000; // Lower is better
            $url = $value->url;
            $now = date_create();
            foreach($value->managedCoverageList as $c){
                $begin = date_create_from_format('Y-m-d', $c['beginCoverage']);
                $end = empty($c['endCoverage']) ? $now : $end = date_create_from_format('Y-m-d', $c['endCoverage']);

                if(!empty($c->managedEmbargoPeriod['embargoUnit'])){
                    $end = date_sub($end,
                        date_interval_create_from_date_string(
                            $c->managedEmbargoPeriod['embargoValue'].' '.strtolower($c->managedEmbargoPeriod['embargoUnit']
                            )
                        )
                    );
                }
                $coverageDiff = date_diff($begin, $end);
                $coverageSpan =  $coverageDiff->days > $coverageSpan ? $coverageDiff->days : $coverageSpan;
                $ageDiff = date_diff($end, $now);
                $age = $ageDiff->days < $age ? $ageDiff->days : $age;
            }
            $urls[] = [
                'coverageSpan' => $coverageSpan,
                'age' => $age,
                'url' => $url,
            ];

        }
        usort($urls, function ($a, $b) {
            if ($a['age'] == $b['age']) {
                if($a['coverageSpan'] == $b['coverageSpan']){
                    return 0;
                }
                return ($a['coverageSpan'] > $b['coverageSpan']) ? -1 : 1;
            }
            return ($a['age'] < $b['age']) ? -1 : 1;
        });
        return $urls;
    }

    public function generateCoralResource($data = [])
    {
        $resource = new Resource();
        $existingResource = $resource->getResourceByEbscoKbId($this->titleId);
        // Search for a matching resource
        if ($existingResource){
            //get this resource
            $resource = $existingResource;
        }else{
            //set up new resource
            $resource->createLoginID = $loginID;
            $resource->createDate = date( 'Y-m-d' );
            $resource->updateLoginID = '';
            $resource->updateDate = '';
        }
    }

}