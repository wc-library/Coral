$(document).ready(function(){
  $("#submitDashboard").live("click", function() {
        submitDashboard();
    });

  $("#submitDashboardYearlyCosts").live("click", function() {
        submitDashboardYearlyCosts();
    });

});

function submitDashboard() {
	$.ajax({
		type:       "POST",
		url:        "ajax_htmldata.php?action=getDashboard",
		cache:      false,
		data:       {
                        "resourceTypeID": $("#resourceTypeID").val(),
                        "year":$("#year").val(),
                        "acquisitionTypeID": $("#acquisitionTypeID").val(),
                        "orderTypeID": $("#orderTypeID").val(),
                        "subjectID": $("#subjectID").val(),
                        "costDetailsID": $("#costDetailsID").val(),
                        "groupBy": $("#groupBy").val(),
                    },
		success:    function(html) {
            $("#dashboardTable").html(html);
		}
	});
}

function submitDashboardYearlyCosts() {
	$.ajax({
		type:       "POST",
		url:        "ajax_htmldata.php?action=getDashboardYearlyCosts",
		cache:      false,
		data:       {
                        "resourceTypeID": $("#resourceTypeID").val(),
                        "startYear":$("#startYear").val(),
                        "endYear":$("#endYear").val(),
                        "acquisitionTypeID": $("#acquisitionTypeID").val(),
                        "orderTypeID": $("#orderTypeID").val(),
                        "subjectID": $("#subjectID").val(),
                        "costDetailsID": $("#costDetailsID").val(),
                        "groupBy": $("#groupBy").val(),
                    },
		success:    function(html) {
            $("#dashboardTable").html(html);
		}
	});
}


