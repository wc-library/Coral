<?php

class EbscoKbService extends Object {

/*
 * Vars
 */
    protected $config;
    protected $error;
    protected $queryParams;
    private $queryPath;
    private $queryHeader;

    static $apiUrl = 'https://api.ebsco.io/rm/rmaccounts/';

    static $defaultSearchParameters = [
        'orderby' => 'relevance',
        'offset' => 1,
        'count' => 10,
        'type' => 'titles',
        'searchField' => 'titlename',
        'selection' => 1,
        'resourcetype' => 0,
        'contenttype' => 0,
    ];

    static $queryTypes = [
        'holdings' => 'Holdings',
        'packages' => 'Packages',
        'titles' => 'Titles',
        'vendors' => 'Vendors',
    ];

    static $titleSearchFieldOptions = [
        0 => 'Title Name',
        1 => 'Publisher',
        2 => 'ISXN',
        3 => 'Subject',
    ];

    static $titleResourceTypeOptions = [
        0 => 'All',
        1 => 'Journal',
        2 => 'Newsletter',
        3 => 'Report',
        4 => 'Proceedings',
        5 => 'Website',
        6 => 'Newspaper',
        7 => 'Unspecified',
        8 => 'Book',
        9 => 'Book Series',
        10 => 'Database',
        11 => 'Thesis & Dissertation',
        12 => 'Streaming Audio',
        13 => 'Streaming Video',
        14 => 'Audio Book',
    ];

    static $selectionOptions = [
        0 => 'All',
        1 => 'Selected in EBSCO Kb',
        2 => 'Not Selected',
        3 => 'Ordered through EBSCO',
    ];

    static $packageContentTypeOptions = [
        0 => 'All',
        1 => 'Aggregated Fulltext',
        3 => 'Abstract & Index',
        4 => 'ebook',
        5 => 'ejournal',
        6 => 'Print',
        7 => 'Unknown',
        8 => 'Online Reference',
    ];

/*
 * General purpose
 */
    protected function init(NamedArguments $arguments) {
        parent::init($arguments);
        $this->config = new Configuration;
        $this->checkForError();
        $this->queryHeader = [
            'Accept: application/json',
            'x-api-key: '.$this->config->settings->ebscoKbApiKey,
        ];
        $this->queryPath = [
            $this->config->settings->ebscoKbCustomerId,
        ];
        $this->queryParams = [];
    }

/*
 * Error handling
 */
    protected function checkForError() {
        $errors = [];
        if($this->config->settings->ebscoKbEnabled != 'Y'){
            $errors = 'EBSCO Kb is not enabled';
        }
        $customer_id = $this->config->settings->ebscoKbCustomerId;
        if(empty($customer_id)){
            $errors[] = 'There is no EBSCO Customer ID';
        }
        $apiKey = $this->config->settings->ebscoKbApiKey;
        if(empty($apiKey)){
            $errors[] = 'There is no EBSCO Kb API key';
        }

        if(!empty($errors)){
            $this->error = $errors;
            throw new Exception(_("There was a problem with the EBSCO Kb configuration: ") . implode(', ', $errors));
        }
    }

    protected function checkParams($params) {
        if(empty($params['type'])){
            throw new Exception(_("There was a problem with the EBSCO Kb Service Query: No search type was selected."));
        }
        if(!in_array($params['type'], array_keys(self::$queryTypes))){
            throw new Exception(_("There was a problem with the EBSCO Kb Service Query: The provided search type (".$params['type'].") could not be found"));
        }
    }

/*
 * Session helpers
 */

    public static function setSearch($search) {
        foreach (self::$defaultSearchParameters as $key => $value) {
            if (!isset($search[$key])) {
                $search[$key] = $value;
            }
        }
        foreach ($search as $key => $value) {
            $search[$key] = trim($value);
        }

        CoralSession::set('ebscoKbSearch', $search);
    }

    public static function resetSearch() {
        EbscoKbSearch::setSearch(array());
    }

    public static function getSearch() {
        if (!CoralSession::get('ebscoKbSearch')) {
            EbscoKbSearch::resetSearch();
        }
        return CoralSession::get('ebscoKbSearch');
    }

/*
 * Query Functions
 */
    public function createQuery($params)
    {
        $this->checkParams($params);
        $type = $params['type'];
        unset($params['type']);
        $this->queryPath[] = $type;
        if(empty($params['search'])){
            $params['orderby'] = substr($type,0,-1).'name';
        }
        $this->queryParams = $params;
    }

    public function getTitle($titleId)
    {
        $this->queryPath[] = 'titles';
        $this->queryPath[] = $titleId;
        return $this->execute();
    }

    public function getVendor($vendorId, $packages = false)
    {
        $this->queryPath[] = 'vendors';
        $this->queryPath[] = $vendorId;
        if($packages){
            $this->queryPath[] = 'packages';
        }
        return $this->execute();
    }

    public function getPackage($vendorId, $packageId, $titles = false)
    {
        $this->queryPath[] = 'vendors';
        $this->queryPath[] = $vendorId;
        $this->queryPath[] = 'packages';
        $this->queryPath[] = $packageId;
        if($titles){
            $this->queryPath[] = 'titles';
        }
        return $this->execute();
    }

    public function execute()
    {
        $url = self::$apiUrl.implode('/',$this->queryPath);
        if(!empty($this->queryParams)){
            $url .= '?'.http_build_query($this->queryParams);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->queryHeader);

        $response = curl_exec($ch);
        curl_close ($ch);

        $response = json_decode($response);

        return $response;
    }



}