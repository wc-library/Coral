<?php
/*
**************************************************************************************************************************
** CORAL Licensing Module Terms Tool Add-On Terms Tool Add-On v. 1.0
**
** Copyright (c) 2010 University of Notre Dame
**
** This file is part of CORAL.
**
** CORAL is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
**
** CORAL is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License along with CORAL.  If not, see <http://www.gnu.org/licenses/>.
**
**************************************************************************************************************************
*/

include_once 'directory.php';

function get_display($isbn = '', $issn = '', $typeID = null){

    // variables for permitted/prohibited qualifiers (this should match up with the available qualifiers in the license module)
    //permitted terms will display green checkbox
    $permittedQualifier = 'Permitted';

    //prohibited terms will display red x
    $prohibitedQualifier = 'Prohibited';

    //either isbn or isn must be passed in
    if (($isbn == '') && ($issn == '')){
        return array('Terms Tool - Missing ISxN', 'You must pass in either ISSN or ISBN.');
    }

    //get targets from the terms tool service for this ISBN or ISSN
    $targetsArray = array();
    try{
        $termsServiceObj = new TermsService(new NamedArguments(array('issn' => $issn, 'isbn' => $isbn)));
        $termsToolObj = $termsServiceObj->getTermsToolObj();
        $targetsArray = $termsToolObj->getTargets();

    } catch(Exception $e) {
        return array(
            'Terms Tool - Error',
            $e->getMessage() . "  Please verify your information in the configuration.ini file and try again."
        );
    }

    // No results found
    if (empty($targetsArray)){
        return array(
            'Terms Tool - No Providers Found',
            'Sorry, no Full Text Providers are available for this ISSN/ISBN'
        );
    }

    $pageTitle = 'Terms Tool - ';
    $displayHTML = '';

    //expression type ID was not passed in - find out what expression types are available for these targets and prompt
    if(empty($typeID)) {
        $pageTitle .= "Select Expression Type";

        $expressionTypeObj = new ExpressionType();
        $targetArray = array();
        $uniqueExpressionTypeArray = array();

        foreach ($targetsArray as $i => $targetArray){
            $expressionTypeArray = $expressionTypeObj->getExpressionTypesByResource($targetArray['public_name']);
            //loop through each displayable expression type and add to final array
            foreach ($expressionTypeArray as $expressionTypeID){
                $uniqueExpressionTypeArray[] = $expressionTypeID;
            }
            //end target loop
        }

        //make sure expression type IDs are unique
        $uniqueExpressionTypeArray = array_unique($uniqueExpressionTypeArray);

        if (empty($uniqueExpressionTypeArray)){
            $displayHTML .= "<p>Sorry, no available license expressions have been located in CORAL Licensing for the following providers:</p>";
            $displayHTML .= '<ul>';
            foreach($targetsArray as $i => $provider) {
                $displayHTML .= '<li>'.$provider['public_name'].'</li>';
            }
            $displayHTML .= '</ul>';
        } else {
            $displayHTML .= '<div class="darkShaded" style="width:664px; padding:8px; margin:0 0 15px 0;">'
                        .'<span class="headerText">Available Expression Types</span> for ' . $termsToolObj->getTitle() . '</div>';

            $displayHTML .= '<ul>';
            //loop through each distinct displayable expression type
            foreach ($uniqueExpressionTypeArray as $expressionTypeID){
                $expressionType = new ExpressionType(new NamedArguments(array('primaryKey' => $expressionTypeID)));
                $displayHTML .= '<li><a href="?issn=' . $issn . '&isbn=' . $isbn . '&typeID=' . $expressionType->expressionTypeID . '">' . $expressionType->shortName . '</a></li>';
            }

            $displayHTML .= '</ul>';
        }

        return array($pageTitle, $displayHTML);
    }

    // else we have all required parameters
    $expressionType = new ExpressionType(new NamedArguments(array('primaryKey' => $typeID)));
    $pageTitle = $expressionType->shortName . " License Terms";
    $displayHTML = '<div class="darkShaded" style="width:664px; padding:8px; margin:0 0 7px 0;"><span class="headerText">'
        . $expressionType->shortName . ' Terms</span> for ' . $termsToolObj->getTitle() . '</div>';

    $displayHTML .= '<div style="margin-left:5px;">';

    $orderedTargetsArray = array();
    $orderedTargetsArray = $expressionType->reorderTargets($targetsArray);

    $targetArray = array();
    foreach ($orderedTargetsArray as $i => $targetArray){
        $displayHTML .= "<span class='titleText'>" . $targetArray['public_name'] . "</span><br />";

        $expressionArray = array();
        $expressionArray = $expressionType->getExpressionsByResource($targetArray['public_name']);

        //if no expressions are defined for this resource / expression type combination
        if (count($expressionArray) == '0'){
            $displayHTML .= "No " . $expressionType->shortName . " terms are defined.<br /><br />";
        } else {

            //loop through each expression for this resource / expression type combination
            foreach ($expressionArray as $expression){
                //get qualifiers into an array
                $qualifierArray = array();
                foreach ($expression->getQualifiers as $qualifier){
                    $qualifierArray[] = $qualifier->shortName;
                    if (strtoupper($qualifier->shortName) == strtoupper($permittedQualifier)){
                        $qualifierImage = "<img src='images/icon_check.gif'>";
                    }else if (strtoupper($qualifier->shortName) == strtoupper($prohibitedQualifier)){
                        $qualifierImage = "<img src='images/icon_x.gif'>";
                    }else{
                        $qualifierImage = "";
                    }
                }


                //determine document effective date
                $document = new Document(new NamedArguments(array('primaryKey' => $expression->documentID)));

                if ((!$document->effectiveDate) || ($document->effectiveDate == '0000-00-00')){
                    $effectiveDate = format_date($document->getLastSignatureDate());
                }else{
                    $effectiveDate = format_date($document->effectiveDate);;
                }

                $displayHTML .= "Terms as of " . format_date($expression->getLastUpdateDate) . ".  ";

                $displayHTML .= "The following terms apply ONLY to articles accessed via <a href='" . $targetArray['target_url'] . "' target='_blank'>" . $targetArray['public_name'] . "</a><br /><br />";

                $displayHTML .= "<div style='margin:0 0 30px 20px;'>";

                $displayHTML .= "<div class='shaded' style='width:630px; padding:3px;'>";
                $displayHTML .= "<b>" . $expressionType->shortName . " Notes:</b>&nbsp;&nbsp;" . $qualifierImage;

                //start bulletted list
                $displayHTML .= "<ul>\n";

                //first in the bulleted list will be the list of qualifiers, if applicable
                if (count($qualifierArray) > 0){
                    $displayHTML .= "<li>Qualifier: " . implode(",", $qualifierArray) . "</li>\n";
                }

                foreach ($expression->getExpressionNotes as $expressionNote){
                    $displayHTML .= "<li>" . $expressionNote->note . "</li>\n";
                }

                $displayHTML .= "</ul>\n";
                $displayHTML .= "</div>";


                //only display 'show license snippet' if there's actual license document text
                if ($expression->documentText){
                    $displayHTML .= "<br />";

                    $displayHTML .= '<div style="width:600px;">'
                        .'<a href="javascript:void(0);" class="toggleSnippet smallLink" data-toggle="' . $expression->expressionID . $i . '" onclick="toggleSnippet(this)">'
                            .'<img src="images/arrowright.gif"> view license snippet</a>'
                        .'<div class="shaded" style="margin-top: 5px; padding:5px 5px 5px 18px; display:none;" id="' . $expression->expressionID . $i . '">'
                            .'From the license agreement ('.$effectiveDate.'):<br><br>'
                            .'<em>' . nl2br($expression->documentText) . '</em></div>'
                        . '</div>';
                }

                $displayHTML .= "</div>";

                //end expression loop
            }

            //end expression count
        }

        //target foreach loop
    }
    $displayHTML .= "</div>";

    return array($pageTitle, $displayHTML);
}

//get the passed in ISSN or ISBN
$issn = filter_input(INPUT_GET, 'issn', FILTER_SANITIZE_STRING);
$isbn = filter_input(INPUT_GET, 'isbn', FILTER_SANITIZE_STRING);
$typeID = filter_input(INPUT_GET, 'typeID', FILTER_SANITIZE_STRING);

$display = get_display($isbn, $issn, $typeID);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $display[0]; ?></title>
<link rel="stylesheet" href="css/style.css" type="text/css" />
</head>
<body>
<div style="margin:10px auto; width: 900px; background: white; padding: 20px; text-align: left;">
    <?php echo $display[1]; ?>
</div>
</body>
<script>
  function toggleSnippet(el){
    var element = document.getElementById(el.getAttribute('data-toggle'));
    var style = window.getComputedStyle ? getComputedStyle(element, null) : element.currentStyle;
    if (style.display === 'none') {
      element.style.display = 'block';
      el.innerHTML = '<img src="images/arrowdown.gif"> hide license snippet</a>';
    } else {
      element.style.display = 'none';
      el.innerHTML = '<img src="images/arrowright.gif"> view license snippet</a>';
    }
  }
</script>
</html>















