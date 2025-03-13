$.fn.setupListExecute = function (id) {
    const $container = this;
    const iListExecute = $container.attr('id').replace('wplistexecute-', '');

    let countChecked = function() {
        if ($container.find('.checkbox_objects').is(':checked')) {
            if($container.find('select.check_submit_select').val()){
                $container.find('input.list_execute_submit').prop('disabled', false);
            }
        } else {
            $container.find('input.list_execute_submit').prop('disabled', true);
        }
        var header_checked = $container.find('.checkbox_objects').not(':checked').length == 0;
        $container.find('.listexecute-all').val(header_checked ? 'ALL' : '');
    };

    $container.find('.listexecute-select-all').removeClass('listexecute-select-all')
        .on('click', function (e) {
            $(this).closest('form').find('tbody :checkbox:not(:disabled)').each(function () {
                $(this).prop("checked", ! $(this).prop("checked"));
            }).promise().done(function(){ countChecked(); });
        });

    $container.find('select[name=list_action]')
        .on('change', function() {
            var valueSel = $container.find('select.check_submit_select').val();
            if(valueSel == ''){
                $container.find('input.list_execute_submit').prop('disabled', true);
            } else {
                if($container.find('.checkbox_objects').is(':checked')){
                    $container.find('input.list_execute_submit').prop('disabled', false);
                }
            }
            var params = $(this).find('option:selected').data('input');
            var inputType = $(this).find('option:selected').data('inputtype');
            if(typeof params === "object") {
                params = Object.values(params).filter(function(el){ return !!el; }).shift();
            }
            if (typeof params === "object") {
                $container.find('.list_input_container').load(
                    $.service('tracker', 'fetch_item_field', params),
                    function () {
                        $(this).tiki_popover().applySelect2();
                    }
                ).show();
            } else if( params ) {
                //if it is 'text', show the text input, else it is 'category_tree', show the category tree.
                if (inputType === "text") {
                    $(this).closest('.list_execute_actions').find('input[name=list_input]').show();
                } else {
                    $(".cat_tree").show();
                }
                $container.find('.list_input_container').hide();
            } else {
                $(this).closest('.list_execute_actions').find('input[name=list_input]').hide();
                $container.find('.list_input_container').hide();
                $(".cat_tree").hide();
            }
        });

    $container.find(".checkbox_objects").on("click", countChecked);
    countChecked();

    $container.on("submit", function(e){
        feedback(tr('Action is being executed, please wait.'));
        let modal = $container.parent().tikiModal(" ");
        var filters = $(`#list_filter${iListExecute} form`).serializeArray(),
            inp, i;
        for(i = 0, l = filters.length; i < l; i++) {
            inp = $('<input type="hidden">');
            inp.attr('name', filters[i].name);
            inp.val(filters[i].value);
            $container.append(inp);
        }
        var trackerInputs = $("input,select,textarea", $container.find('.list_input_container')).serializeArray();
        if (trackerInputs) {
            for (i = 0; i < trackerInputs.length; i++) {
                inp = $('<input type="hidden">');
                inp.attr("name", "list_input~" + trackerInputs[i].name);    // add tracker inputs as an array "inside" list_input
                inp.val(trackerInputs[i].value);
                $container.append(inp);
            }
            $container.remove("input[list_input]");
        }
        $.ajax({
            url: $.service('wiki', 'execute'),
            type: 'POST',
            data: `page=${jqueryTiki.current_object.object}&` + $container.serialize() + '&' + (new URLSearchParams(window.location.search).toString()),
            success: function(data) {
                modal.tikiModal();
                $('#tikifeedback .alert.alert-dismissable').remove();
                $container.replaceWith(data.result);
                $('#' + $container.attr('id')).setupListExecute(id).trigger('tiki.submit.listexecute');
            },
            error: function (xhr, status, err) {
                modal.tikiModal();
                $('#tikifeedback .alert.alert-dismissable').remove();
                feedback(err, 'error');
            }
        });
        e.preventDefault();
        return false;
    });

    $(`#listexecute-download-${iListExecute}, #listexecute-download-top-${iListExecute}`).on("submit", function(){
        var $form = $(this);
        $form.find('input[name^=filter]').remove();
        $('.tablesorter-filter').each(function(i,el){
            var column = $(el).data('column'),
                    value = $(el).val();
            if( value ) {
                $('<input type="hidden" name="filter['+column+']">')
                    .val(value)
                    .appendTo($form);
            }
        });
        var m = id.match(/wpcs\-(\d+)$/);
        var id = m ? m[1] : null;
        var cs = window['customsearch_'+id];
        if (cs) {
            $form.attr('action', $.service('search_customsearch', 'customsearch'));
            var datamap = {
                definition: cs.definition,
                adddata: JSON.stringify(cs.searchdata),
                searchid: cs.id,
                offset: cs.offset,
                maxRecords: cs.maxRecords,
                store_query: cs.store_query
            };
            $.each(datamap, function(k, v) {
                $('<input type="hidden">').attr('name', k).val(v).appendTo($form);
            });
        }
    });

    $container.trigger('tiki.load.listexecute');

    return this;
};

$.fn.reloadListExecute = function() {
    const $container = this;
    let modal = $container.parent().tikiModal(" ");
    $.ajax({
        url: $.service('wiki', 'execute'),
        type: 'GET',
        data: `page=${jqueryTiki.current_object.object}&` + $container.serialize() + '&' + (new URLSearchParams(window.location.search).toString()),
        success: function(data) {
            modal.tikiModal();
            let id = $container.data('id');
            $container.replaceWith(data.result);
            $('#' + $container.attr('id')).setupListExecute(id);
        },
        error: function (xhr, status, err) {
            modal.tikiModal();
            feedback(err, 'error');
        }
    });
};

(function($) {
    $('.list-executable').each(function() {
        $(this).setupListExecute($(this).data('id'));
    });
})(jQuery);
