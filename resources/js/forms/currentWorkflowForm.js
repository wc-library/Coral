$(document).ready(function(){

	lastKey = $('#finalKey').val();

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
        $('.newStepTable').find('.priorStepID').val('option:first');


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

	$(".moveArrow").live('click', function () {
	
	    var dir = $(this).attr('direction')
	
	    //first flip the rows
	    var movingKey = parseInt($(this).parent('.seqOrder').attr('key'));
	    var movingKeyHTML = $(this).parent().parent().html();

	    
	    //this is the key we're switching places with
	    if (dir == 'up'){
	    	var nextKey = movingKey - 1;
	    }else{
	    	var nextKey = movingKey + 1;
	    }
	    
	    var nextKeyHTML = $(".seqOrder[key='" + nextKey + "']").parent().html();


	    //hold the 3 fields so after the html is flipped we can reset them
	    var movingKeyStepName = $(this).parent().parent().children().children('.stepName').val();
	    var nextKeyStepName = $(".seqOrder[key='" + nextKey + "']").parent().children().children('.stepName').val();
	    var movingKeyUserGroupID = $(this).parent().parent().children().children('.userGroupID').val();
	    var nextKeyUserGroupID = $(".seqOrder[key='" + nextKey + "']").parent().children().children('.userGroupID').val();
	    var movingKeyPriorStepID = $(this).parent().parent().children().children('.priorStepID').val();
	    var nextKeyPriorStepID = $(".seqOrder[key='" + nextKey + "']").parent().children().children('.priorStepID').val();

	    //flip the html
	    $(".seqOrder[key='" + nextKey + "']").parent().html(movingKeyHTML);
	    $(this).parent().parent().html(nextKeyHTML);
	    
	    //now put those values back
	    $(".seqOrder[key='" + movingKey + "']").parent().children().children('.stepName').val(movingKeyStepName);
	    $(".seqOrder[key='" + nextKey + "']").parent().children().children('.stepName').val(nextKeyStepName);

	    $(".seqOrder[key='" + movingKey + "']").parent().children().children('.userGroupID').val(movingKeyUserGroupID);
	    $(".seqOrder[key='" + nextKey + "']").parent().children().children('.userGroupID').val(nextKeyUserGroupID);

	    $(".seqOrder[key='" + movingKey + "']").parent().children().children('.priorStepID').val(movingKeyPriorStepID);
	    $(".seqOrder[key='" + nextKey + "']").parent().children().children('.priorStepID').val(nextKeyPriorStepID);	    

	    
	    //flip the key values	    
  	    $(".seqOrder[key='" + nextKey + "']").attr('key',  function() {
  			return 'hold';
		});
  	    $(".seqOrder[key='" + movingKey + "']").attr('key',  function() {
  			return nextKey;
		});
  	    $(".seqOrder[key='hold']").attr('key',  function() {
  			return movingKey;
		});
	    	    
	    	   
	    setArrows();
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
	$('.moveArrow').die('click');
}


function validateWorkflow() {
    return true;
}

function setArrows(){

	$(".seqOrder").each(function(id) {
	      thisKey = $(this).attr('key');

		if (thisKey != ''){
			//this is the only row so it shuld be transparent
			if(lastKey == 1){
				$('.seqOrder[key="1"]').html("<img src='images/transparent.gif' style='width:43px;height:20px;' />");
			}else{
				//first gets down arrow only
				if (thisKey == 1){
					$('.seqOrder[key="1"]').html("<img src='images/transparent.gif' style='width:20px;height:20px;' />&nbsp;<a href='javascript:void(0);' class='moveArrow' direction='down'><img src='images/arrow_down.gif' /></a>");

				//if this is the last one it gets up arrow only
				}else if (thisKey == lastKey){
					$(".seqOrder[key='" + thisKey + "']").html("<a href='javascript:void(0);' class='moveArrow' direction='up'><img src='images/arrow_up.gif' /></a>&nbsp;<img src='images/transparent.gif' style='width:20px;height:20px;' />");

				//otherwise display both arrows
				}else{
					$(".seqOrder[key='" + thisKey + "']").html("<a href='javascript:void(0);' class='moveArrow' direction='up'><img src='images/arrow_up.gif' /></a>&nbsp;<a href='javascript:void(0);' class='moveArrow' direction='down'><img src='images/arrow_down.gif' /></a>");


				}

			}
		}

	}); 


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

	seqOrderList ='';
	$(".seqOrder").each(function(id) {
	      seqOrderList += $(this).attr('key') + ":::";
	}); 
	

    if (validateWorkflow() === true) {
        $('.submitCurrentWorkflowForm').attr("disabled", "disabled");
          $.ajax({
             type:       "POST",
             url:        "ajax_processing.php?action=submitCurrentWorkflow",
             cache:      false,
             data:       { resourceID: $("#editRID").val(), stepNames: stepNameList, userGroups: userGroupList, priorSteps: priorStepList, stepIDs: stepIDList, actions: actionList, seqOrders: seqOrderList },
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
