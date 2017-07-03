<?php
class Dashboard {

    public function getQuery($resourceTypeID, $year, $acquisitionTypeID, $orderTypeID, $subjectID) {
        $query = "SELECT R.resourceID, R.titleText, SUM(RP.paymentAmount) as paymentAmount
                     FROM Resource R 
                    LEFT JOIN ResourcePayment RP ON RP.resourceID = R.resourceID";

        if ($subjectID) {
            $query .= " LEFT JOIN ResourceSubject RS ON RS.resourceID = R.resourceID
                    LEFT JOIN GeneralDetailSubjectLink GDSL ON GDSL.generalDetailSubjectLinkID = RS.generalDetailSubjectLinkID";
        }
        $query .= " WHERE RP.year = $year";

        if ($resourceTypeID) $query .= " AND R.resourceTypeID = $resourceTypeID";
        if ($acquisitionTypeID) $query .= " AND R.acquisitionTypeID = $acquisitionTypeID";
        if ($orderTypeID) $query .= " AND RP.orderTypeID = $orderTypeID";
        if ($subjectID) {
            if (substr($subjectID, 0, 1) == "d") {
                $query .= " AND GDSL.detailedSubjectID = " . substr($subjectID, 1);
            } else {
                $query .= " AND GDSL.generalSubjectID = $subjectID";
            }
        }
        $query .= " GROUP BY resourceID";
        return $query;
    }
    
    public function getResults($query) {
        $this->db = new DBService;
        $result = $this->db->processQuery($query, 'assoc');
        if (isset($result['resourceID'])) { $result = [$result]; }
        return $result;

    }

}
?>
