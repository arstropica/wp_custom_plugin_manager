jQuery.noConflict();
(function($) {
    $(function(){
        $.expr[':'].Contains = function(a, i, m) { 
            return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0; 
        };

        (function fadeMessage(elem){
            elem.delay(20000).fadeOut('fast',function(){
                $(this).next().length && fadeMessage($(this).next()); 
            });
        })( $("#notices DIV.message:first") );

        $.fn.bar_message = function (type, text) {
            var selected = $(this);
            var _bar_message = function bar_message (_target, type, text) {
                $.msgBar ({
                    type: type
                    , 'text': text
                    , lifetime: 5000
                }).prependTo ($(_target));
            };

            return $(this).each(function () {
                return _bar_message(this, type, text);
            });
        };

        function ucwords(str) {
            return (str + '')
            .replace(/^([a-z\u00E0-\u00FC])|\s+([a-z\u00E0-\u00FC])/g, function ($1) {
                return $1.toUpperCase();
            });
        }                    

        function capitalise(string, strict) {
            if (typeof strict == 'undefined' ) strict = false;
            if (strict)
                return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
            else
                return string.charAt(0).toUpperCase() + string.slice(1);    
        }

        function sortObject(o) {
            var sorted = {},
            key, a = [];

            for (key in o) {
                if (o.hasOwnProperty(key)) {
                    a.push(key);
                }
            }

            a.sort();

            for (key = 0; key < a.length; key++) {
                sorted[a[key]] = o[a[key]];
            }
            return sorted;
        }

        // End Functions

        $(".ajax_loading").hide();
        $(document)
        .ajaxSend(function(e, jqxhr, settings) {
            $(".ajax_loading").show();
        })
        .ajaxComplete(function(e, jqxhr, settings) {
            $(".ajax_loading").show();
        })
        .ajaxStop(function (e, jqxhr, settings) {
            $(".ajax_loading").hide();
        });

        $.ajax({
            type: "GET",
            url : "/scripts/get_plugin.php?action=list_plugins&status=false&format=json",
            dataType : "json",
            success: function(_data) {
                var tarray = [];
                if (typeof _data == "object") {
                    if (_data.outcome && typeof _data.data == "object") {
                        var data = _data.data;
                        if (typeof data.plugins == "object") {
                            var plugins = sortObject(data.plugins);
                            for (pluginname in plugins) {
                                var h_pluginname = ucwords(pluginname.replace(/[_-]/gi, " "));
                                var av_sum = true;
                                var tsarray = [];
                                if (typeof plugins[pluginname].versions == 'object') {
                                    for (_version in plugins[pluginname].versions) {
                                        var plugin_version = plugins[pluginname].versions[_version];
                                        var available = plugin_version['active'] ? true : false;
                                        av_sum = available ? av_sum : false;
                                        tsarray.push("<tr class='subheader hidden'><td>" + h_pluginname + "</td><td>" + _version + "</td><td>" + moment(plugin_version['last_modified']).format('MM/DD/YY') + "</td><td><a href='#' class='edit_readme' data-slug='" + pluginname + "' data-version='"+ _version + "'>" + (plugin_version['readme'] ? "Yes" : "No") + "</a></td><td><input type='checkbox' value='1' class='toggle_availability' name='availability[" + pluginname + "][" + _version + "][active]' " + (available ? "checked='checked'" : "") + "/><input type='hidden' name='availability[" + pluginname + "][" + _version + "][inactive]' value='1' /></td></tr>");
                                    }
                                }
                                if (tsarray.length > 0) {
                                    tarray.push("<tr class='header'><td colspan='4'><span class='expand'>+</span>" + h_pluginname + "</td><td><input type='checkbox' class='toggle_availability'  " + (av_sum ? "checked='checked'" : "") + "/></td></tr>");
                                    tarray = tarray.concat(tsarray); 
                                }
                            }
                            if (tarray.length > 0) {
                                $('#plugin_status_table TBODY').empty();
                                $.each(tarray, function (i, trow){
                                    $('#plugin_status_table TBODY').append(trow);
                                });                    
                            }
                        }
                    }
                }
            },
            complete : function() {
                if ($('#plugin_status_table TBODY TR').length > 0) {
                    $(".tablefilter").on('change keyup', function(e){
                        var column = $(this).closest('TD');
                        var column_index = $(this).closest('TR').find('TD').index(column);
                        var pattern = $(this).val();
                        if (pattern.length != 1) {
                            $('#plugin_status_table')
                            .find('TBODY TR')
                            .hide()
                            .filter(function(index) {
                                return $(this).find("TD:eq(" + column_index + "):Contains('" + pattern + "')").length > 0;
                            })
                            .show()
                            .end()
                            .trigger("update");
                        }
                    });                                                
                    $("#plugin_status_table") 
                    .tablesorter({ 
                        headers: { 
                            0: { 
                                sorter: false 
                            }, 
                            1: { 
                                sorter: false 
                            } ,
                            2: { 
                                sorter: false 
                            }, 
                            3: { 
                                sorter: false 
                            }, 
                            4: { 
                                sorter: false 
                            }, 
                        } 
                    });
                }
            }
        });

        $('#plugin_status_table').on('click', 'A.edit_readme', function(e){
            e.preventDefault();
            var slug = $(this).data('slug');
            var version = $(this).data('version');
            window.open("readme-generator.php?slug=" + slug + "&version=" + version, "Readme Editor", "toolbar=no, scrollbars=no, resizable=no, titlebar=no, status=no, location=no, menubar=no, width=860,height=800");
            return false;
        });
        $('#plugin_status_table').on('click', ':input.toggle_availability', function(e){
            var is_checked = $(this).is(':checked');
            if ($(this).closest('TR').is('.header')) {
                if (is_checked) {
                    $(this).closest("TR").nextUntil("TR.header").find(":input.toggle_availability").prop("checked", true);
                } else {
                    $(this).closest("TR").nextUntil("TR.header").find(":input.toggle_availability").prop("checked", false);
                }
            } else if ($(this).closest('TR').is('.filterrow')){
                if (is_checked) {
                    $('#plugin_status_table TBODY').find(":input.toggle_availability").prop("checked", true);
                } else {
                    $('#plugin_status_table TBODY').find(":input.toggle_availability").prop("checked", false);                            
                }
            }
        });
        $('#plugin_status_table').on('click', '.expand', function(e){
            $(this).text(function(_, value){return value=='-'?'+':'-'});
            $(this).closest("TR").nextUntil("TR.header").slideToggle(100, function(){
                $(this).toggleClass('hidden');
            });                                                        
        });
    });
})(jQuery);
