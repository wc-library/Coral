$(document).ready(function(){
    $(".addStep").live('click', function() {
        var originalTR = $('.newStepTR').clone();
        $('.newStepTR').appendTo('.stepTable');
        $('.newStepTR').find('.addStep').attr({
          src: 'images/cross.gif',
          alt: _("remove this step"),
          title: _("remove this step")
        });
        $('.newStepTR').find('.addStep').removeClass('addStep').addClass('removeStep');
        $('.newStepTR').removeClass('newStepTR').addClass('stepTR');

        //next put the original clone back, we just need to reset the values
        originalTR.appendTo('.newStepTable');
        $('.newStepTable').find('.stepName').val('');
        $('.newStepTable').find('.userGroupID').val('');
        $('.newStepTable').find('.parentStepID').val('');

    });


    $(".removeStep").live('click', function () {
        //remove whole row from interface
        $(this).parent().parent().parent().fadeTo(400, 0, function () {
            $(this).find(">:first-child").find(">:first-child").val("delete");
            $(this).hide();
            $(this).die('click');
        });
        return false;
    });

    
    $("#submitCurrentWorkflowForm").click(function () {
       submitCurrentWorkflow();
    });
});

//kill all binds done by jquery live
function kill() {
    $('.addStep').die('click'); 
    $('.removeStep').die('click'); 
}


function validateWorkflow() {
    return true;
}


function submitCurrentWorkflow() {
    stepNameList ='';
    $(".stepTR .stepName").each(function(id) {
          stepNameList += $(this).val() + ":::";
    });

    userGroupList ='';
    $(".stepTR .userGroupID").each(function(id) {
          userGroupList += $(this).val() + ":::";
    });

    priorStepList ='';
    $(".stepTR .priorStepID").each(function(id) {
          priorStepList += $(this).val() + ":::";
    });

    stepIDList ='';
    $(".stepTR .stepID").each(function(id) {
          stepIDList += $(this).val() + ":::";
    });

    actionList = '';
    $(".stepTR .action").each(function(id) {
          actionList += $(this).val() + ":::";
    });



    if (validateWorkflow() === true) {
        $('.submitCurrentWorkflowForm').attr("disabled", "disabled");
          $.ajax({
             type:       "POST",
             url:        "ajax_processing.php?action=submitCurrentWorkflow",
             cache:      false,
             data:       { resourceID: $("#editRID").val(), stepNames: stepNameList, userGroups: userGroupList, priorSteps: priorStepList, stepIDs: stepIDList, actions: actionList },
             success:    function(html) {
                if (html){
                    $("#span_errors").html(html);
                    $("#submitCurrentWorkflowForm").removeAttr("disabled");
                }else{  
                    kill();
                    window.parent.tb_remove();
                    window.parent.updateRouting();
                    return false;
                }

             }
         });
    }
}
