jQuery( function( $ ) {
    var $preview = $('input[name=notification_previews]'),
        $previewLength = $('select[name=notification_preview_length]'),
        $previewEllipsis = $('input[name=notification_preview_ellipsis]'),
        $previewFields = $previewLength.add($previewEllipsis).closest('.form-group');

    $preview.on('click', function () {
        this.checked ? $previewFields.show() : $previewFields.hide();
    });
    $preview.triggerHandler('click');
});