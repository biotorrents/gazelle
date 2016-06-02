var BBCode = {
	spoiler: function(link) {
		if ($(link.nextSibling).has_class('hidden')) {
			$(link.nextSibling).gshow();
			$(link).html('Hide');
			if ($(link).attr("value")) {
				$(link).attr("value", "Hide" + $(link).attr("value").substring(4))
			}
		} else {
			$(link.nextSibling).ghide();
			$(link).html('Show');
			if ($(link).attr("value")) {
				$(link).attr("value", "Show" + $(link).attr("value").substring(4))
			}
		}
	}
};
