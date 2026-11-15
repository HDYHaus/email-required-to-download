(function ($) {
	'use strict';

	function updateDeliveryHint() {
		$('.erd-protected-help').removeClass('hide-if-js').prop('hidden', $('input[name="delivery_mode"]:checked').val() !== 'protected');
	}

	$(updateDeliveryHint);
	$(document).on('change', 'input[name="delivery_mode"]', updateDeliveryHint);

	function fileRow() {
		return $('<div class="erd-file-row">' +
			'<input class="erd-file-id" type="hidden" name="file_ids[]" value="">' +
			'<input class="erd-protected-path" type="hidden" name="protected_paths[]" value="">' +
			'<input class="regular-text erd-file-url" type="url" name="file_urls[]" placeholder="File URL" value="">' +
			'<input class="regular-text erd-file-label" type="text" name="file_labels[]" placeholder="File label" value="">' +
			'<button type="button" class="button erd-select-file">Select File</button> ' +
			'<button type="button" class="button erd-remove-file">Remove</button>' +
			'<label class="erd-protected-upload"><span>Protected upload</span><input type="file" name="protected_uploads[]"></label>' +
		'</div>');
	}

	$(document).on('click', '.erd-add-file', function (event) {
		event.preventDefault();
		$('.erd-files').append(fileRow());
	});

	$(document).on('click', '.erd-remove-file', function (event) {
		event.preventDefault();
		if ($('.erd-file-row').length > 1) {
			$(this).closest('.erd-file-row').remove();
		}
	});

	$(document).on('click', '.erd-select-file', function (event) {
		event.preventDefault();
		var row = $(this).closest('.erd-file-row');

		var frame = wp.media({
			title: 'Select download file',
			button: { text: 'Use this file' },
			multiple: false
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			row.find('.erd-file-id').val(attachment.id);
			row.find('.erd-protected-path').val('');
			row.find('.erd-file-url').val(attachment.url);
			if (!row.find('.erd-file-label').val()) {
				row.find('.erd-file-label').val(attachment.title || attachment.filename);
			}
		});

		frame.open();
	});
})(jQuery);
