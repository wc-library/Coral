$(document).ready(function(){
  $("#submitDashboard").live("click", function() {
        submitDashboard();
    });

});

function submitDashboard() {
	$.ajax({
		type:       "POST",
		url:        "ajax_htmldata.php?action=getDashboard",
		cache:      false,
		data:       {
                        "resourceTypeID": $("#resourceTypeID").val(),
                        "startYear":$("#startYear").val(),
                        "endYear":$("#endYear").val(),
                        "acquisitionTypeID": $("#acquisitionTypeID").val(),
                        "orderTypeID": $("#orderTypeID").val(),
                        "subjectID": $("#subjectID").val(),
                        "costDetailsID": $("#costDetailsID").val(),
                    },
		success:    function(html) {
            $("#dashboardTable").html(html);
		}
	});
}


