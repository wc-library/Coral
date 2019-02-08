<?php
class Dashboard {

    public function getQuery($resourceTypeID, $year, $acquisitionTypeID, $orderTypeID, $subjectID, $costDetailsID, $fundID, $organizationID, $roleID, $groupBy) {
        $config = new Configuration();
        if ($config->settings->organizationsModule == 'Y' && $config->settings->organizationsDatabaseName) {
            $orgDB = $config->settings->organizationsDatabaseName;
            $orgField = 'name';
        } else {
            $orgDB = $config->database->name;
            $orgField = 'shortName';
        }

        $query = "SELECT
                        R.resourceID,
                        R.titleText,
                        RT.shortName AS resourceType,
                        AT.shortName AS acquisitionType,
                        OT.shortName AS orderType,
                        CD.shortName AS costDetails,
                        GS.shortName AS generalSubject,
                        DS.shortName AS detailedSubject,
                        RA.libraryNumber AS libraryNumber,
                        F.shortName AS fundName,
                        O.$orgField AS organizationName,
                        SUM(ROUND(COALESCE(RP.paymentAmount, 0) / 100, 2)) as paymentAmount
                        ";

        $query .= "
                 FROM Resource R
                    LEFT JOIN ResourceAcquisition RA ON RA.resourceID = R.resourceID
                    LEFT JOIN ResourcePayment RP ON RP.resourceAcquisitionID = RA.resourceAcquisitionID
                    LEFT JOIN ResourceType RT ON RT.resourceTypeID = R.resourceTypeID
                    LEFT JOIN AcquisitionType AT ON AT.acquisitionTypeID = RA.acquisitionTypeID
                    LEFT JOIN OrderType OT ON OT.orderTypeID = RP.orderTypeID
                    LEFT JOIN CostDetails CD ON CD.costDetailsID = RP.costDetailsID
                    LEFT JOIN ResourceSubject RS ON RS.resourceID = R.resourceID
                    LEFT JOIN GeneralDetailSubjectLink GDSL ON GDSL.generalDetailSubjectLinkID = RS.generalDetailSubjectLinkID
                    LEFT JOIN GeneralSubject GS ON GS.generalSubjectID = GDSL.generalSubjectID
                    LEFT JOIN DetailedSubject DS ON DS.detailedSubjectID = GDSL.detailedSubjectID
                    LEFT JOIN Fund F ON RP.fundID = F.FundID
                    LEFT JOIN ResourceOrganizationLink ROL ON ROL.resourceID = R.resourceID
                    LEFT JOIN $orgDB.Organization O on O.organizationID = ROL.organizationID
                ";

        $query .= " WHERE RP.year=$year";

        if ($resourceTypeID) $query .= " AND R.resourceTypeID = $resourceTypeID";
        if ($acquisitionTypeID) $query .= " AND RA.acquisitionTypeID = $acquisitionTypeID";
        if ($orderTypeID) $query .= " AND RP.orderTypeID = $orderTypeID";
        if ($costDetailsID) $query .= " AND RP.costDetailsID = $costDetailsID";
        if ($fundID) $query .= " AND F.fundID = $fundID";
        if ($organizationID) $query .= " AND O.organizationID = $organizationID";
        if ($roleID) $query .= " AND ROL.organizationRoleID = $roleID";
        if ($subjectID) {
            if (substr($subjectID, 0, 1) == "d") {
                $query .= " AND GDSL.detailedSubjectID = " . substr($subjectID, 1);
            } else {
                $query .= " AND GDSL.generalSubjectID = $subjectID";
            }
        }
        $query .= " GROUP BY ";
        if ($groupBy != '') $query .= "$groupBy, ";
        $query .= "resourceID WITH ROLLUP";
        return $query;
    }

    public function getQueryYearlyCosts($resourceTypeID, $startYear, $endYear, $acquisitionTypeID, $orderTypeID, $subjectID, $costDetailsID, $fundID, $organizationID, $roleID, $groupBy) {
        $config = new Configuration();
        if ($config->settings->organizationsModule == 'Y' && $config->settings->organizationsDatabaseName) {
            $orgDB = $config->settings->organizationsDatabaseName;
            $orgField = 'name';
        } else {
            $orgDB = $config->database->name;
            $orgField = 'shortName';
        }

         $query = "SELECT
                        R.resourceID,
                        R.titleText,
                        RT.shortName AS resourceType,
                        AT.shortName AS acquisitionType,
                        CD.shortName AS costDetails,
                        GS.shortName AS generalSubject,
                        DS.shortName AS detailedSubject,
                        RA.libraryNumber AS libraryNumber,
                        F.shortName as fundName,
                        O.$orgField AS organizationName
                        ";

        $costDetails = new CostDetails();
        $costDetailsArray = $costDetails->allAsArray();
        $sum_parts = array();
        for ($i = $startYear; $i <= $endYear; $i++) {
            foreach ($costDetailsArray as $costDetail) {

                if ($costDetailsID && $costDetail['costDetailsID'] != $costDetailsID) continue;

                $sum_query = " SUM(if(RP.year = $i";
                $sum_query .= " AND RP.costDetailsID = " . $costDetail['costDetailsID'];

                if ($orderTypeID) $sum_query .= " AND RP.orderTypeID = $orderTypeID";

                $sum_query .= ", ROUND(COALESCE(RP.paymentAmount, 0) / 100, 2), 0)) AS `" . $costDetail['shortName'] . " / $i`";
                $sum_parts[] = $sum_query;
            }
        }
        $query_sum = join(",", $sum_parts);
        if ($query_sum) $query .= "," . $query_sum;

        $query .= "
                 FROM Resource R
                    LEFT JOIN ResourceAcquisition RA ON RA.resourceID = R.resourceID
                    LEFT JOIN ResourcePayment RP ON RP.resourceAcquisitionID = RA.resourceAcquisitionID
                    LEFT JOIN ResourceType RT ON RT.resourceTypeID = R.resourceTypeID
                    LEFT JOIN AcquisitionType AT ON AT.acquisitionTypeID = RA.acquisitionTypeID
                    LEFT JOIN CostDetails CD ON CD.costDetailsID = RP.costDetailsID
                    LEFT JOIN ResourceSubject RS ON RS.resourceID = R.resourceID
                    LEFT JOIN GeneralDetailSubjectLink GDSL ON GDSL.generalDetailSubjectLinkID = RS.generalDetailSubjectLinkID
                    LEFT JOIN GeneralSubject GS ON GS.generalSubjectID = GDSL.generalSubjectID
                    LEFT JOIN DetailedSubject DS ON DS.detailedSubjectID = GDSL.detailedSubjectID
                    LEFT JOIN Fund F ON RP.fundID = F.FundID
                    LEFT JOIN ResourceOrganizationLink ROL ON ROL.resourceID = R.resourceID
                    LEFT JOIN $orgDB.Organization O on O.organizationID = ROL.organizationID
                ";

        $query_parts = array();
        if ($resourceTypeID) $query_parts[] = " R.resourceTypeID = $resourceTypeID";
        if ($acquisitionTypeID) $query_parts[] = " RA.acquisitionTypeID = $acquisitionTypeID";
        if ($orderTypeID) $query_parts[] = " RP.orderTypeID = $orderTypeID";
        if ($costDetailsID) $query_parts[] = " RP.costDetailsID = $costDetailsID";
        if ($fundID) $query_parts[] = " F.fundID = $fundID";
        if ($organizationID) $query_parts[] .= " O.organizationID = $organizationID";
        if ($roleID) $query_parts[] .= " ROL.organizationRoleID = $roleID";
        if ($subjectID) {
            if (substr($subjectID, 0, 1) == "d") {
                $query_parts[] = " GDSL.detailedSubjectID = " . substr($subjectID, 1);
            } else {
                $query_parts[] = " GDSL.generalSubjectID = $subjectID";
            }
        }
        $query_where .= join(" AND ", $query_parts);
        if ($query_where) $query .= " WHERE " . $query_where;

        $query .= " GROUP BY ";
        if ($groupBy != '') $query .= "$groupBy, ";
        $query .= "resourceID WITH ROLLUP";
        return $query;
    }

    public function getResults($query) {
        $this->db = new DBService;
        $result = $this->db->processQuery($query, 'assoc');
        if (isset($result['resourceID'])) { $result = [$result]; }
        return $result;

    }

    public function displayExportParameters($resourceTypeID, $startYear, $endYear, $acquisitionTypeID, $orderTypeID, $subjectID, $costDetailsID, $groupBy) {
        $resourcesFilters = array();
        if ($resourceTypeID) {
            $resourceType = new ResourceType(new NamedArguments(array('primaryKey' => $resourceTypeID)));
            $resourceFilters[] = _("Resource Type") . ": " . $resourceType->shortName;
        }
        if ($subjectID) {
            if (substr($subjectID, 0, 1) == "d") {
                $subject = new DetailedSubject(new NamedArguments(array('primaryKey' => substr($subjectID, 1))));
            } else {
                $subject = new GeneralSubject(new NamedArguments(array('primaryKey' => $subjectID)));
            }
            $resourceFilters[] = _("Subject") . ": " . $subject->shortName;
        }
        if ($acquisitionTypeID) {
            $acquisitionType = new AcquisitionType(new NamedArguments(array('primaryKey' => $acquisitionTypeID)));
            $resourceFilters[] = _("Acquisition Type") . ": " . $acquisitionType->shortName;
        }

        $paymentFilters = array();
        if ($orderTypeID) {
            $orderType = new OrderType(new NamedArguments(array('primaryKey' => $orderTypeID)));
            $paymentFilters[] = _("Order Type") . ": " . $orderType->shortName;
        }
        if ($costDetailsID) {
            $costDetails = new CostDetails(new NamedArguments(array('primaryKey' => $costDetailsID)));
            $paymentFilters[] = _("Cost Details") . ": " . $costDetails->shortName;
        }

        echo _("Filters on resources") . ":\r\n";
        if (count($resourceFilters) > 0) {
            echo join(" / ", $resourceFilters) . "\r\n";
        } else {
            echo _("none") . "\r\n";
        }

        echo _("Filters on payments") . ":\r\n";
        if (count($paymentFilters) > 0) {
            echo join(" / ", $paymentFilters) . "\r\n";
        } else {
            echo _("none") . "\r\n";
        }

        if ($startYear && $endYear) {
            echo _("Start year") . ": $startYear\r\n";
            echo _("End year") . ": $endYear\r\n";
        } else {
            echo _("Year") . ": $startYear\r\n";
        }

        if ($groupBy) {
            echo _("Group by") . ": " . $groupBy . "\r\n";
        }

    }

    function getResourceTypesAsDropdown($currentID = null) {
        $display = array();
        $resourceType = new ResourceType();
        echo '<select name="resourceTypeID" id="resourceTypeID" style="width:150px;">';
        echo "<option value=''>All</option>";
        foreach($resourceType->getAllResourceType() as $display) {
            if ($display['resourceTypeID'] == $currentID) {
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
            if ($display['acquisitionTypeID'] == $currentID) {
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
            if ($display['orderTypeID'] == $currentID) {
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
            if ($display['costDetailsID'] == $currentID) {
                echo "<option value='" . $display['costDetailsID'] . "' selected>" . $display['shortName'] . "</option>";
            } else {
                echo "<option value='" . $display['costDetailsID'] . "'>" . $display['shortName'] . "</option>";
            }
        }
        echo '</select>';

    }

    function getFundsAsDropdown($currentID = null) {
        $display = array();
        $funds = new Fund();
        echo '<select name="fundID" id="fundID" style="width:150px;">';
        echo "<option value=''>All</option>";
        foreach($funds->allAsArray() as $display) {
            if ($display['fundID'] == $currentID) {
                echo "<option value='" . $display['fundID'] . "' selected>" . $display['shortName'] . "</option>";
            } else {
                echo "<option value='" . $display['fundID'] . "'>" . $display['shortName'] . "</option>";
            }
        }
        echo '</select>';

    }

    function getOrganizationsAsDropdown($currentID = null) {
        $resource = new Resource();
        $organizations = $resource->getOrganizationList();

        echo '<select name="organizationID" id="organizationID" style="width:150px;">';
        echo "<option value=''>All</option>";

        foreach ($organizations as $display) {
            if ($display['organizationID'] == $currentID) {
                echo "<option value='" . $display['organizationID'] . "' selected>" . $display['name'] . "</option>";
            } else {
                echo "<option value='" . $display['organizationID'] . "'>" . $display['name'] . "</option>";
            }
        }
        echo '</select>';
    }

    function getOrganizationsRolesAsDropdown($currentID = null) {
        $organizationRole = new OrganizationRole();
        $roles = $organizationRole->getArray();

        echo '<select name="roleID" id="roleID" style="width:150px;">';
        echo "<option value=''>All</option>";

        foreach ($roles as $roleID => $roleName) {
            if ($roleID == $currentID) {
                echo "<option value='" . $roleID . "' selected>" . $roleName . "</option>";
            } else {
                echo "<option value='" . $roleID . "'>" . $roleName. "</option>";
            }
        }
        echo '</select>';

    }

}
?>
