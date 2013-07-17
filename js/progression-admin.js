(function ($) {
	"use strict";
	$(function () {

		// init color picker on plugin settings page
		$('.progression-skincolor').wpColorPicker();


		function toggleCustomSettings() {
			$( '.progression-skincolor' ).closest('tr')[$('#progression_custom_skin').is(':checked') ? 'show' : 'hide']();
		}

		// This toggles the custom color settings fields when the checkbox is set
		$('#progression_custom_skin')
			
			// check on load
			.each(toggleCustomSettings)

			// check on change
			.change(toggleCustomSettings);

	});
}(jQuery));