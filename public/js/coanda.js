$(document).ready( function () {

	$('.breadcrumb-nav .breadcrumb li .expand').on('click', function (e) {

		if (!$($(this).attr('href')).is(':visible'))
		{
			$(this).addClass('selected');

			$('.sub-pages-expand').hide();

			$($(this).attr('href')).slideDown();
		}
		else
		{
			$(this).removeClass('selected');
			$($(this).attr('href')).hide()
		}

	});

	$('a.new-window').attr('target', '__blank');

	$('input.select-all').on('click', function () {

		$(this).get(0).setSelectionRange(0, this.value.length)

	});

	if ($('.datetimepicker').size() > 0)
	{
		$('.datetimepicker').each (function () {

			$(this).datetimepicker({
				useSeconds: false,
				sideBySide: false
			});

			$(this).find('.date-field').on('focus', function () {

				$(this).parents('.datetimepicker').data("DateTimePicker").show();

			});

		})
	}

	$('.show-tooltip').tooltip();

	// var dropZone = $("#dropzone-uploader").dropzone();
	var reload_url = $("#dropzone-uploader").data('reload-url');

	Dropzone.options.dropzoneUploader = {

		init: function() {
				this.on("success", function() {
	
					if (this.getQueuedFiles().length == 0 && this.getUploadingFiles().length == 0)
					{
						window.location = reload_url;
					}
				})
			}
		};

});