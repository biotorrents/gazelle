var tooltip_delay = 500;
$(function() {
	if (!$.fn.tooltipster) {
		$('.tooltip_interactive, .tooltip_image, .tooltip, .tooltip_left').each(function() {
			if ($(this).data('title-plain')) {
				$(this).attr('title', $(this).data('title-plain')).removeData('title-plain');
			}
		});
		return;
	}
	$('.tooltip_interactive').tooltipster({
		interactive: true,
		interactiveTolerance: 500,
		delay: tooltip_delay,
		updateAnimation: false,
		maxWidth: 400
	});
	$('.tooltip').tooltipster({
		delay: tooltip_delay,
		updateAnimation: false,
		maxWidth: 400
	});

	$('.tooltip_left').tooltipster({
		delay: tooltip_delay,
		position: 'left',
		updateAnimation: false,
		maxWidth: 400
	});

	$('.tooltip_image').tooltipster({
		delay: tooltip_delay,
		updateAnimation: false,
		fixedWidth: 252
	});
});
