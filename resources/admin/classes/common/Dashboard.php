<?php
class Dashboard {

    public function getQuery($resourceTypeID, $year, $acquisitionTypeID, $orderTypeID, $subjectID, $costDetailsID, $groupBy) {
        $query = "SELECT
                        R.resourceID,
                        R.titleText,
                        RT.shortName AS resourceType,
                        AT.shortName AS acquisitionType,
                        OT.shortName AS orderType,
                        CD.shortName AS costDetails,
                        GS.shortName AS generalSubject,
                        DS.shortName AS detailedSubject,
                        SUM(RP.paymentAmount) as paymentAmount
                        ";

        $query .= "
                 FROM Resource R
                    LEFT JOIN ResourcePayment RP ON RP.resourceID = R.resourceID
                    LEFT JOIN ResourceType RT ON RT.resourceTypeID = R.resourceTypeID
                    LEFT JOIN AcquisitionType AT ON AT.acquisitionTypeID = R.acquisitionTypeID
                    LEFT JOIN OrderType OT ON OT.orderTypeID = RP.orderTypeID
                    LEFT JOIN CostDetails CD ON CD.costDetailsID = RP.costDetailsID
                    LEFT JOIN ResourceSubject RS ON RS.resourceID = R.resourceID
                    LEFT JOIN GeneralDetailSubjectLink GDSL ON GDSL.generalDetailSubjectLinkID = RS.generalDetailSubjectLinkID
                    LEFT JOIN GeneralSubject GS ON GS.generalSubjectID = GDSL.generalSubjectID
                    LEFT JOIN DetailedSubject DS ON DS.detailedSubjectID = GDSL.detailedSubjectID
                ";

        $query .= " WHERE RP.year=$year";

        if ($resourceTypeID) $query .= " AND R.resourceTypeID = $resourceTypeID";
        if ($acquisitionTypeID) $query .= " AND R.acquisitionTypeID = $acquisitionTypeID";
        if ($orderTypeID) $query .= " AND RP.orderTypeID = $orderTypeID";
        if ($costDetailsID) $query .= " AND RP.costDetailsID = $costDetailsID";
        if ($subjectID) {
            if (substr($subjectID, 0, 1) == "d") {
                $query .= " AND GDSL.detailedSubjectID = " . substr($subjectID, 1);
            } else {
                $query .= " AND GDSL.generalSubjectID = $subjectID";
            }
        }
        $query .= " GROUP BY $groupBy";
        error_log(substr($query, -20));
        return $query;
    }

    public function getQueryYearlyCosts($resourceTypeID, $startYear, $endYear, $acquisitionTypeID, $orderTypeID, $subjectID, $costDetailsID) {
     $query = "SELECT
                        R.resourceID,
                        R.titleText,
                        RT.shortName AS resourceType,
                        AT.shortName AS acquisitionType,
                        CD.shortName AS costDetails,
                        GS.shortName AS generalSubject,
                        DS.shortName AS detailedSubject,
                        ";

        for ($i = $startYear; $i <= $endYear; $i++) {
            $query .= " SUM(if(RP.year = $i";

            if ($costDetailsID) $query .= " AND RP.costDetailsID = $costDetailsID";
            if ($orderTypeID) $query .= " AND RP.orderTypeID = $orderTypeID";

            $query .= ", RP.paymentAmount, 0)) AS `$i`";
            if ($i < $endYear) $query .= ",";
        }

        $query .= "
                 FROM Resource R
                    LEFT JOIN ResourcePayment RP ON RP.resourceID = R.resourceID
                    LEFT JOIN ResourceType RT ON RT.resourceTypeID = R.resourceTypeID
                    LEFT JOIN AcquisitionType AT ON AT.acquisitionTypeID = R.acquisitionTypeID
                    LEFT JOIN CostDetails CD ON CD.costDetailsID = RP.costDetailsID
                    LEFT JOIN ResourceSubject RS ON RS.resourceID = R.resourceID
                    LEFT JOIN GeneralDetailSubjectLink GDSL ON GDSL.generalDetailSubjectLinkID = RS.generalDetailSubjectLinkID
                    LEFT JOIN GeneralSubject GS ON GS.generalSubjectID = GDSL.generalSubjectID
                    LEFT JOIN DetailedSubject DS ON DS.detailedSubjectID = GDSL.detailedSubjectID
                ";

        $query_parts = array();
        if ($resourceTypeID) $query_parts[] = " R.resourceTypeID = $resourceTypeID";
        if ($acquisitionTypeID) $query_parts[] = " R.acquisitionTypeID = $acquisitionTypeID";
        if ($orderTypeID) $query_parts[] = " RP.orderTypeID = $orderTypeID";
        if ($costDetailsID) $query_parts[] = " RP.costDetailsID = $costDetailsID";
        if ($subjectID) {
            if (substr($subjectID, 0, 1) == "d") {
                $query_parts[] = " GDSL.detailedSubjectID = " . substr($subjectID, 1);
            } else {
                $query_parts[] = " GDSL.generalSubjectID = $subjectID";
            }
        }
        $query_where .= join(" AND ", $query_parts);
        if ($query_where) $query .= " WHERE " . $query_where;
        $query .= " GROUP BY resourceID WITH ROLLUP";
        return $query;
    }

    public function getResults($query) {
        $this->db = new DBService;
        $result = $this->db->processQuery($query, 'assoc');
        if (isset($result['resourceID'])) { $result = [$result]; }
        return $result;

    }


    function getResourceTypesAsDropdown($currentID = null) {
        $display = array();
        $resourceType = new ResourceType();
        echo '<select name="resourceTypeID" id="resourceTypeID" style="width:150px;">';
        echo "<option value=''>All</option>";
        foreach($resourceType->getAllResourceType() as $display) {
            if ($display['resourceTypeID'] == $current) {
                echo "<option value='" . $display['resourceTypeID'] . "' selected>" . $display['shortName'] . "</option>";
            } else {
                echo "<option value='" . $display['resourceTypeID'] . "'>" . $display['shortName'] . "</option>";
            }
        }
        echo '</select>';
    }

    function getAcquisitionTypesAsDropdown($currentID = null) {
        $display = array();
        $acquisitionType = new AcquisitionType();
        echo '<select name="acquisitionTypeID" id="acquisitionTypeID" style="width:150px;">';
        echo "<option value=''>All</option>";
        foreach($acquisitionType->allAsArray() as $display) {
            if ($display['acquisitionTypeID'] == $current) {
                echo "<option value='" . $display['acquisitionTypeID'] . "' selected>" . $display['shortName'] . "</option>";
            } else {
                echo "<option value='" . $display['acquisitionTypeID'] . "'>" . $display['shortName'] . "</option>";
            }
        }
        echo '</select>';
    }

    function getOrderTypesAsDropdown($currentID = null) {
        $display = array();
        $orderType = new OrderType();
        echo '<select name="orderTypeID" id="orderTypeID" style="width:150px;">';
        echo "<option value=''>All</option>";
        foreach($orderType->getAllOrderType() as $display) {
            if ($display['orderTypeID'] == $current) {
                echo "<option value='" . $display['orderTypeID'] . "' selected>" . $display['shortName'] . "</option>";
            } else {
                echo "<option value='" . $display['orderTypeID'] . "'>" . $display['shortName'] . "</option>";
            }
        }
        echo '</select>';
    }

    function getSubjectsAsDropdown($currentID = null) {
        $generalSubject = new GeneralSubject();
        $generalSubjectArray = $generalSubject->allAsArray();

        $detailedSubject = new DetailedSubject();
        $detailedSubjectArray = $detailedSubject->allAsArray();

        echo '<select name="subjectID" id="subjectID" style="width:150px;">';
        echo "<option value=''>All</option>";
        foreach($generalSubjectArray as $ug) {
            $generalSubject = new GeneralSubject(new NamedArguments(array('primaryKey' => $ug['generalSubjectID'])));
            echo "<option value='" . $ug['generalSubjectID'] . "'>" . $ug['shortName'] . "</option>";
            foreach ($generalSubject->getDetailedSubjects() as $ds){
                echo "<option value='d" . $ds->detailedSubjectID . "'> -- " . $ds->shortName . "</option>";
            }
        }
        echo '</select>';
    }

    function getCostDetailsAsDropdown($currentID = null) {
        $display = array();
        $costDetails = new CostDetails();
        echo '<select name="costDetailsID" id="costDetailsID" style="width:150px;">';
        echo "<option value=''>All</option>";
        foreach($costDetails->allAsArray() as $display) {
            if ($display['costDetailsID'] == $current) {
                echo "<option value='" . $display['costDetailsID'] . "' selected>" . $display['shortName'] . "</option>";
            } else {
                echo "<option value='" . $display['costDetailsID'] . "'>" . $display['shortName'] . "</option>";
            }
        }
        echo '</select>';

    }



}
?>
