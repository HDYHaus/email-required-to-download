(function ($) {
	'use strict';

	$(document).on('click', '.erd-select-file', function (event) {
		event.preventDefault();

		var frame = wp.media({
			title: 'Select download file',
			button: { text: 'Use this file' },
			multiple: false
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			$('#erd-file-id').val(attachment.id);
			$('#erd-file-url').val(attachment.url);
		});

		frame.open();
	});
})(jQuery);
