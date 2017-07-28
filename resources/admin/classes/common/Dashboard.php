<?php
class Dashboard {

    public function getQuery($resourceTypeID, $year, $acquisitionTypeID, $orderTypeID, $subjectID, $costDetailsID) {
        $query = "SELECT
                        R.resourceID,
                        R.titleText,
                        RT.shortName AS resourceType,
                        NULL,
                        AT.shortName AS acquisitionType,
                        OT.shortName AS orderType,
                        CD.shortName AS costDetails,
                        GS.shortName AS generalSubject,
                        DS.shortName AS detailedSubject,
                        SUM(RP.paymentAmount) as paymentAmount
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
        $query .= " WHERE RP.year = $year";

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
