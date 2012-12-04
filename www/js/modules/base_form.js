base_form_init = function()
{
	$(".Form input").each(function()
	{
		$(this).focus(function()
		{
			 $(this).addClass("FormFocused");
		});
		
		$(this).blur(function()
		{
			 $(this).removeClass("FormFocused");
		});
	});	
	
	$(".Form textarea").each(function()
	{
		$(this).focus(function()
		{
			 $(this).addClass("FormFocused");
		});
		
		$(this).blur(function()
		{
			 $(this).removeClass("FormFocused");
		});
	});	
	
	$(".Form select").each(function()
	{
		if (($(this).attr("size") === undefined) || ($(this).attr("size") === 1))
		{
			var option_list = $("<ul class='FormSelectList'></ul>").css({"display":"none","min-width":$(this).width()});
			var container = $("<div class='FormSelectContainer'></div>").width($(this).width());
			var entry = $("<div class='FormSelectEntry'></div>").width(($(this).width()-35));
			var button = $("<div class='FormSelectButton'><img src='images/down.png' alt=''></div>").click(function()
			{
				if ($(option_list).css("display") === "none")
				{
					$(option_list).css("display", "block");
				}
				else
				{
					$(option_list).css("display", "none");
				}
			});
			
			if ($(this).children("option:selected"))
			{
				$(entry).html($(this).children("option:selected").html());
			}
			
			$(this).children("option").each(function()
			{
				var local_option = $(this);
				var list_entry = $("<li>"+$(this).html()+"</li>").bind("click", {option: $(this)}, function(event)
				{
					var data = event.data;
					$(option_list).css("display", "none");
					$(data.option).parent().children("option:selected").removeAttr("selected");
					$(data.option).attr("selected", "selected");
					$(entry).html($(data.option).html());
				});
				
				$(option_list).append(list_entry);
			});
			
			$(container).append(entry);
			$(container).append(button);
			$(container).append("<div style='clear: both;'></div>");
			
			$(this).before(container);
			$(this).before(option_list);
			$(this).css("display", "none");
		}
	});
}


$(document).ready(function()
{
	base_form_init();
});