$waitingdiv = $("<div>").addClass("xhr_waiting");

$.fn.serializeObject = function()
{
	var o = {};
	var a = this.serializeArray();
	$.each(a, function() {
		if (this.value === "")
			return;
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
$.fn.displayToggle = function(show){
	if (typeof show == "undefined")
		show = !$(this).is(":visible");

	if(show)
	{
		if (!$(this).is(":visible"))
			$(this).show().css({ "opacity": 0, "height": 0 });

		var h = $(this).height('auto').height();
		$(this).height(0);
		$(this).animate({
			"opacity": 1,
			"height": h
		});
	}
	else
	{
		$(this).animate({
			"opacity": 0,
			"height": 0
		}, function() {
			$(this).hide();
		});
	}
};

function submit_install_step(dataToSubmit)
{
	if (typeof dataToSubmit === "undefined")
		dataToSubmit = {};
	dataToSubmit.installing = true;
	$.ajax({
		type: "POST",
		url: "index.php",
		data: dataToSubmit,
		dataType: "json",
		success: function(data){
			$(".main").animate({"opacity": 0, "paddingRight": 30 }, 200, function(){
				$waitingdiv.fadeOut("fast");
				if (data.redirect_home)
				{
					window.location.reload(true);
				}
				else if (data.show_completion)
				{
					$(".installation_stuff").hide();
					var countdown = 10;
					$(".redirection .countdown").text(countdown);
					$(".completion_title").text(data.completion_title);
					$(".completion_message").text(data.completion_message);
					$(".redirection_message").text(data.redirection_message);
					var $holder = $(".completed_test_holder");
					data.completed_tests.forEach(function(test){
						$holder.append($("<li>").addClass("completed_test").text(test));
					});
					$(".redirection").fadeIn();
					setTimeout(injectCssForAnimation, 1200);
					setInterval(function(){
						if (countdown-- <= 0)
						{
							window.location.reload(true);
						}
						else
							$(".redirection .countdown").text(countdown);
					}, 1000);
				}
				else {
					$(".section-title, .messages, .mainbody").empty();

					if (typeof data.title !== "undefined")
					$(".section-title").html(data.title);

					if (typeof data.messages !== "undefined")
					{
						if (data.messages)
						{
							data.messages.forEach(function(msg){
								$(".messages").append(
									$("<div>").addClass("message").html(msg)
								);
							});
						}
					}

					if (typeof data.body !== "undefined")
					$(".mainbody").html(data.body);

					$("[data-toggle-section]").each(function(){
						var $ts = $( $(this).attr("data-toggle-section") );
						$ts.css('height', $ts.height());
					});
					$(".toggleSection").each(function(){
						var section_to_toggle = $(this).attr("data-toggle-section");
						var invert = typeof $(this).attr("data-toggle-invert") !== "undefined" ? $(this).attr("data-toggle-invert") == "true" : false;
						if (typeof $(this).attr("data-toggle-default") !== "undefined")
						{
							var toCheck = $(this).attr("data-toggle-default") == "true";
							$(section_to_toggle).displayToggle(toCheck);
							if ($(this).is(":checkbox"))
							{
								$(this).prop('checked', toCheck ^ invert);
							}
						}
						if ($(this).is(":checkbox"))
						{
							$(section_to_toggle).displayToggle( $(this).is(":checked")  ^ invert );
						}
					});
				}

				$(".main").css({ "opacity": 0, "paddingLeft": 30 });
				$(".main").animate({ "opacity": 1, "paddingLeft": 0 }, 300, function(){
					$(".percentageComplete").animate({ "width": data.completion+"%" }, 1000);
				});
			});
			console.log(data.completed_tests);
		}
	}).fail(function(jqXHR){
		$(".main").animate({"opacity": 0, "paddingRight": 30 }, 500, function(){
			$waitingdiv.fadeOut("fast");
			$(".content-head").text("CORAL Installer Failed");
			$(".section-title, .messages, .mainbody").empty();
			$(".messages").append(
				$("<div>").addClass("message").html("<pre>" + jqXHR.responseText + "</pre>")
			);

			$(".main").css({ "opacity": 0, "paddingLeft": 30 });
			$(".main").animate({ "opacity": 1, "paddingLeft": 0 }, 300);
		});
	});
}
$(document).ready(function(){
	console.log("ready");
	$(".main").css({"opacity": 0});
	$waitingdiv.appendTo("body").hide();
	submit_install_step();
}).on("submit", function(){
	$waitingdiv.fadeIn()
	var $form_elements = $("form :input:visible, form input[type=hidden]");
	//But sometimes checkboxes are hidden and their labels are used:
	$form_elements = $form_elements.add($(":checkbox:hidden").filter(function(){
		return $("label[for='" + $(this).attr("id") + "']").is(":visible");
	}));
	$form_elements.filter(':checkbox').each(function() {
		$(this).val($(this).is(":checked") ? 1 : 0);
		$(this).attr('type', 'hidden');
	});
	var data = $form_elements.serializeObject();
	submit_install_step(data);
	return false; // Prevent submit
}).on("click", ".toggleSection", function(){
	var original_message = $(this).html();
	if (typeof $(this).attr("data-alternate-message") !== "undefined")
	{
		var new_message = $(this).attr("data-alternate-message");
		$(this).html(new_message);
		$(this).attr("data-alternate-message", original_message);
	}
	var section_to_toggle = $(this).attr("data-toggle-section");
	if ($(this).is(":checkbox"))
	{
		var invert = typeof $(this).attr("data-toggle-invert") !== "undefined" ? $(this).attr("data-toggle-invert") == "true" : false;
		$(section_to_toggle).displayToggle($(this).is(":checked") ^ invert);
	}
	else
	{
		$(section_to_toggle).displayToggle();
	}
});

function injectCssForAnimation()
{
	var head  = document.getElementsByTagName('head')[0];
	var link  = document.createElement('link');
	link.id   = "checkmarkAnimation";
	link.rel  = 'stylesheet';
	link.type = 'text/css';
	link.href = 'css/checkmarkAnimation.css';
	link.media = 'all';
	head.appendChild(link);
}
