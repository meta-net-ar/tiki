// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
(function ($) {

  $(document).on('mouseenter', '.edit-ml tbody:not(.ui-sortable) .icon-sort', function () {
    $(this).closest('tbody').filter(':not(.ui-sortable)').sortable({
      handle: '.icon-sort',
      stop: function () {
        $(this).closest('table').trigger('model-update');
      }
    });
  });

  $(document).on('change', '.edit-ml .selection', function () {
    var value = $(this).val(), $add = $(this).closest('table').find('.add-learner');

    if (! $add.data('original-href')) {
      $add.data('original-href', $add.attr('href'));
    }

    $add
      .attr('href', $add.data('original-href') + '&class=' + value);
  });

  $(document).on('click', '.edit-ml .add-learner', $.clickModal({
    success: function (data) {
      var $row = $(this).closest('table').find('tbody tr.d-none').clone().removeClass('d-none').appendTo($(this).closest('table').find('tbody'));

      $('.learner', $row[0]).val(data.learner);
      $('.arguments', $row[0]).text(data.arguments).attr('href', $('.arguments', $row[0]).attr('href')+'&class='+data.payload.class);
      $('.serialized-args', $row[0]).val(JSON.stringify(data.payload));

      $(this).closest('table').trigger('model-update');

      $.closeModal();
    }
  }));

  var populate_arguments = function(args) {
    for (var i = 0, l = args.length; i < l; i++) {
      var arg = args[i];
      $('input[name="args['+arg.name+']"]', this).val(arg.value);
      if (arg.input_type == 'rubix' && arg.value) {
        var iargs = arg.value;
        $('[name="args['+arg.name+'][class]"]', this).val(iargs.class);
        $('textarea[name="args['+arg.name+'][args]"]', this).val(JSON.stringify(iargs.args));
      }
      if (arg.input_type == 'layers' && arg.value) {
        for (var j = 0, lj = arg.value.length; j < lj; j++) {
          var $container = $(this).find('.ml-layers').last();
          var iargs = arg.value[j];
          $('[name="args['+arg.name+'][classes][]"]', $container).val(iargs.class);
          $('textarea[name="args['+arg.name+'][args][]"]', $container).val(JSON.stringify(iargs.args));
          if (j+1 < lj) {
            $('.ml-add-layer', this).trigger("click");
          }
        }
      }
    }
  };

  $(document).on('click', '.edit-ml .arguments', function(e) {
    var $row = $(this).closest('tr');
    var payload = JSON.parse($row.find('.serialized-args').val());
    $.clickModal({
      open: function() {
        populate_arguments.bind(this)(payload.args);
      },
      success: function (data) {
        $('.arguments', $row[0]).text(data.arguments);
        $('.serialized-args', $row[0]).val(JSON.stringify(data.payload));

        $row.closest('table').trigger('model-update');

        $.closeModal();
      }
    }, $(this).attr('href'))(e);
    return false;
  });

  $(document).on('change', '.ml-class', function(e) {
    var cl = $(this).val();
    if (! cl) {
      return false;
    }
    var $storage = $(this).parent().find('textarea');
    var payload = JSON.parse($storage.val() || '[]');
    $.clickModal({
      open: function() {
        populate_arguments.bind(this)(payload);
      },
      success: function (data) {
        $storage.val(JSON.stringify(data.payload.args));
        $.closeModal();
      }
    }, $(this).data('href')+($(this).data('href').indexOf('?') > -1 ? '&' : '?')+'class='+cl)(e);
    return false;
  });

  $(document).on('click', '.ml-add-layer', function(e) {
    e.preventDefault();
    $(this).prev().clone().insertBefore($(this));
  });

  $(document).on('click', '.edit-ml .remove', function (e) {
    var $table = $(this).closest('table');
    e.preventDefault();
    $(this).closest('tr').remove();
    $table.trigger('model-update');
  });

  $(document).on('model-update', '.edit-ml table', function () {
    var payload = [];

    $('tbody tr:not(.d-none) .serialized-args', this).each(function () {
      payload.push(JSON.parse($(this).val()));
    });

    $('textarea[name=payload]').val(JSON.stringify(payload));
  });

}(jQuery));
