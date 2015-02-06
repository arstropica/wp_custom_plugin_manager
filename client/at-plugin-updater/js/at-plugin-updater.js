jQuery.noConflict();
(function($){
    $(function(){
        if ($('#at-plugin-updater-url-settings').length > 0) {
            var submit_button = $('.at-plugin-updater-settings INPUT.button-primary');
            var url_field = $('#at-plugin-updater-url');
            url_field.on('blur', function(e){
                var field = $(this);
                var val = $(this).val();
                var email = $('#at-plugin-updater-user').val();
                var pass = $('#at-plugin-updater-pass').val();
                pass = (pass) ? hex_sha512(pass) : pass;
                if (val){
                    var url = "http://" + val + "/get_plugin.php?action=ping" + (email ? "&email=" + email : "") + (pass ? "&pass=" + pass : "") + "&format=jsonp&jsoncallback=?";
                    try {
                        $.getJSON(url,
                        function(response){
                            if (response && typeof response.outcome != 'undefined') {
                                field.removeClass('invalid').addClass('valid').prop('title', 'Valid url detected.');
                                submit_button.removeClass('disabled').prop('disabled', false);
                            } else {
                                field.removeClass('valid').addClass('invalid').prop('title', 'Please enter a valid url.');
                                submit_button.addClass('disabled').prop('disabled', true);
                            }
                        })
                        .fail(function(){
                            field.removeClass('valid').addClass('invalid').prop('title', 'Please enter a valid url.');
                            submit_button.addClass('disabled').prop('disabled', true);
                        }); 
                    } catch (e) {
                        field.removeClass('valid').addClass('invalid').prop('title', 'Something went wrong. Please try again.');
                        submit_button.addClass('disabled').prop('disabled', true);
                    }
                } else {
                    field.removeClass('valid').addClass('invalid');
                    submit_button.addClass('disabled').prop('disabled', true);
                }
            });
            $(document)
            .ajaxSend(function(e, jqxhr, settings) {
                url_field.addClass('ajax_loading');
            })
            .ajaxComplete(function(e, jqxhr, settings) {
                url_field.addClass('ajax_loading');
            })
            .ajaxStop(function (e, jqxhr, settings) {
                url_field.removeClass('ajax_loading');
            });                                                  
            if (url_field.val() == "") {
                submit_button.prop('disabled', true).addClass('disabled');
                url_field.removeClass('valid').addClass('invalid').prop('title', 'Please enter a valid url.');
            } else {
                url_field.trigger('blur');
            }
        }
    });
})(jQuery);