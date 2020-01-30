/* jshint nomen:false */
/* global define, require, window, document, location, Blob, FormData */

(function (factory) {
    'use strict';
    if (typeof define === 'function' && define.amd) {
        // Register as an anonymous AMD module:
        define([
            'jquery',
            'jquery.ui.widget',
            'jquery.file-upload'
        ], factory);
    } else if (typeof exports === 'object') {
        // Node/CommonJS:
        factory(
            require('jquery'),
            require('./vendor/jquery.ui.widget')
        );
    } else {
        // Browser globals:
        factory(window.jQuery);
    }
}(function ($) {
    'use strict';

    $(document).on('click', '.fileupload-file .close', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var widget = $(this).closest('.fileupload-widget');
        widget.find('.fileupload-file').hide();
        widget.find('.fileupload-button').removeClass('disabled');
        widget.find('input[type="hidden"]').val('');
    });

    $.fn.sidusFileUpload = function (options) {
        var widgetAlert = function (widget, text) {
            if (text) {
                widget.find('.alert').html(text).show();
            } else {
                widget.find('.alert').html('').hide();
            }
        };

        $(this).each(function () {
            var widget = $(this);

            var defaultOptions = {
                dataType: 'json',
                add: function (e, data) {
                    var size = data.originalFiles[0]['size'];
                    var maxsize = widget.find('input[type="file"]').attr('data-maxsize');
                    if (size == 0) {
                        widgetAlert(widget, 'File size is null');
                        return;
                    }
                    if (maxsize && size > maxsize) {
                        widgetAlert(widget, 'Exceeded maximum file size : ' + maxsize / 1000 / 1000 + 'Mb');
                        return;
                    }
                    widgetAlert(widget);
                    data.process().done(function () {
                        data.submit();
                    });
                },
                start: function (e) {
                    widgetAlert(widget);
                    widget.find('.progress')
                        .show()
                        .find('.progress-bar')
                        .css('width', '0%');
                },
                done: function (e, data) {
                    widgetAlert(widget);
                    if (data.result.files && data.result.files[0] && data.result.files[0].error) {
                        var error = data.result.files[0].error;
                        if (data._error_messages[error]) {
                            error = data._error_messages[error];
                        }
                        widget.find('.progress').hide();
                        widgetAlert(widget, error);
                        return;
                    }
                    var file = data.result[0];
                    widget.find('.help-block').remove();
                    widget.find('.fileupload-file').show();
                    widget.find('.fileupload-button').addClass('disabled');
                    widget.find('.fileupload-filename').html(file.originalFileName);
                    widget.find('.progress').hide();
                    widget.find('input[type="hidden"]').val(file.identifier);
                },
                fail: function (e, data) {
                    widget.find('.progress').hide();
                    widgetAlert(widget, 'An unknown error occurred during file upload');
                },
                progressall: function (e, data) {
                    var progress = parseInt(data.loaded / data.total * 100, 10);
                    widget.find('.progress-bar').css('width', progress + '%');
                },
                _error_messages: {
                    'error.whitelist': "File type not allowed",
                    'error.blacklist': "File type not allowed",
                    'error.maxsize': "Maximum file size exceeded"
                }
            };

            if (options) {
                jQuery.extend(defaultOptions, options);
            }

            widget.find('input[type="file"]').fileupload(defaultOptions);
        });
    };
}));
