$.fn.serializeObject = function()
{
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

function submit_install_step(dataToSubmit)
{
	if (typeof dataToSubmit === "undefined")
		dataToSubmit = {};
	dataToSubmit.installing = true;
	$.post("install.php", dataToSubmit, function(data){
		$(".main").animate({"opacity": 0, "paddingRight": 30 }, 500);

		if (typeof data.title !== "undefined")
			$(".current-test-title").html(data.title);

		if (typeof data.messages !== "undefined")
		{
			$(".messages").empty();
			if (data.messages)
			data.messages.forEach(function(msg){
				$(".messages").append(
					$("<div>").addClass("message").html(msg)
				);
			});
		}

		if (typeof data.body !== "undefined")
			$(".mainbody").html(data.body);

		$(".main").css({ "opacity": 0, "paddingLeft": 30 });
		$(".main").animate({ "opacity": 1, "paddingLeft": 0 }, 300, function(){
			$(".percentageComplete").animate({ "width": data.completion+"%" }, 1000);
		});
	}, 'json');
}
$(document).ready(function(){
	console.log("ready");
	$(".main").css({"opacity": 0});
	submit_install_step();
}).on("click", "#submit", function(){
	var data = $("form").serializeObject();
	submit_install_step(data);
}).on("click", ".toggleSection", function(){
	var original_message = $(this).html();
	$(this).html($(this).attr("data-alternate-message"));
	$(this).attr("data-alternate-message", original_message);
	var section_to_toggle = $(this).attr("data-toggle-section");
	$(section_to_toggle).slideToggle();
});
