<?php
if (!isset($_GET['resourceID'])){
    echo "<div><p>You must supply a valid resource ID.</p></div>";
}else{
    $resourceID = $_GET['resourceID'];
    $resource = new Resource(new NamedArguments(array('primaryKey' => $resourceID)));

    $userGroupObj = new UserGroup();
    $userGroupArray = $userGroupObj->allAsArray();

    $resourceSteps = $resource->getResourceSteps();
    $parentSteps = $resource->getResourceSteps();

    //make form
    ?>
    <div id='div_resourceStepForm'>
        <form id='resourceStepForm'>
            <input type='hidden' name='editRID' id='editRID' value='<?php echo $resourceID; ?>'>

            <div class='formTitle' style='width:705px; margin-bottom:5px;position:relative;'><span class='headerText'>Edit Workflow</span></div>

            <span class='smallDarkRedText' id='span_errors'></span>

            <table class='noBorder' style='width:100%;'>
                <tr style='vertical-align:top;'>
                    <td style='vertical-align:top;position:relative;'>
                        <span class='surroundBoxTitle'>&nbsp;&nbsp;<label for='rule'><b>Workflow Steps</b></label>&nbsp;&nbsp;</span>

                        <table class='surroundBox' style='width:700px;'>
                            <tr>
                                <td>
                                    <table class='noBorder newStepTable' style='width:660px; margin:15px 20px 10px 20px;'>
                                        <tr>
                                            <td><?php echo _("Name"); ?></td>
                                            <td><?php echo _("Approval/Notification group"); ?></td>
                                            <td><?php echo _("Parent Step"); ?></td>
                                            <td><?php echo _("Action"); ?></td>
                                        </tr>
                                        <tr class="newStepTR">
                                            <td>
                                            <input type="hidden" class="stepID" value="-1">
                                            <input type="text" class="stepName"></td>
                                            <td>
                                                <select name='userGroupID' id='userGroupID' style='width:150px;' class='changeSelect userGroupID'>
                                                        <?php
                                                        foreach ($userGroupArray as $userGroup){
                                                            $selected = ($userGroup['userGroupID'] == $resourceStep->userGroupID)? 'selected':'';
                                                            echo "<option value='" . $userGroup['userGroupID'] . "' ".$selected.">" . $userGroup['groupName'] . "</option>\n";
                                                        }
                                                        ?>
                                                </select>
                                            </td>
                                            <td>
                                               <select name='parentStepID' id='parentStepID' style='width:150px;' class='changeSelect parentStepID'>
                                                    <option value=""></option>
                                                    <?php
                                                    foreach ($parentSteps as $parentStep) {
                                                        $selected = ($parentStep->stepID == $resourceStep->priorStepID) ? 'selected' : '';
                                                        echo "<option value='" . $parentStep->stepID . "' ".$selected.">" . $parentStep->stepName . "</option>\n";
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                            <td><a href="javascript:void(0)"><img src="images/add.gif" class="addStep" alt="Add" /></a></td>
                                        </tr>
                                    </table>

                                        <hr />

                                    <table class='noBorder stepTable' style='width:660px; margin:15px 20px 10px 20px;'>
                                        <?php
                                        $count = count($resourceSteps);
                                        $i = 0;
                                        foreach ($resourceSteps as $resourceStep) {
                                        $i++;
                                        if ($i == $count) $lastStepClass = ' class="lastStep"';
                                        ?>
                                        <tr class="stepTR">
                                            <td>
                                            <input type="hidden" class="action" value="keep">
                                            <input type="hidden" class="stepID" value="<?php echo $resourceStep->resourceStepID; ?>">
                                            <input type="text" class="stepName changeInput" value="<?php echo $resourceStep->stepName; ?>"></td>
                                            <td style='vertical-align:top;text-align:left;'>
                                                <select name='userGroupID' id='userGroupID' style='width:150px;' class='changeSelect userGroupID'>
                                                    <?php
                                                    foreach ($userGroupArray as $userGroup){
                                                        $selected = ($userGroup['userGroupID'] == $resourceStep->userGroupID)? 'selected':'';
                                                        echo "<option value='" . $userGroup['userGroupID'] . "' ".$selected.">" . $userGroup['groupName'] . "</option>\n";
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                            <td>
                                                <select name='priorStepID' id='priorStepID' style='width:150px;' class='changeSelect priorStepID'>
                                                    <option value=""></option>
                                                    <?php
                                                    foreach ($parentSteps as $parentStep) {
                                                        $selected = ($parentStep->stepID == $resourceStep->priorStepID) ? 'selected' : '';
                                                        echo "<option value='" . $parentStep->stepID . "' ".$selected.">" . $parentStep->stepName . "</option>\n";
                                                    }
                                                    ?>
                                                </select>

                                            </td>
                                            <td><a href="javascript:void(0)"><img src="images/cross.gif" class="removeStep" alt="Delete" /></a></td>
                                        </tr>
                                        <?php
                                        }
                                        ?>
                                    </table>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table class='noBorderTable' style='width:125px;'>
                <tr>
                    <td style='text-align:left'><input type='button' value='submit' name='submitCurrentWorkflowForm' id ='submitCurrentWorkflowForm'></td>
                    <td style='text-align:right'><input type='button' value='cancel' onclick="kill(); tb_remove();"></td>
                </tr>
            </table>

            <script type="text/javascript" src="js/forms/currentWorkflowForm.js?random=<?php echo rand(); ?>"></script>
        </form>
    </div>

    <?php

}
