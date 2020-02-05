(function ($, Drupal) {
    Drupal.behaviors.working_hours_field_widget_renderer = {
        attach: function (context) {
            $('[data-add-record]').bind('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var control = $(this).parents('[data-row-control]');
                var row = $(this).parent('[data-row]');
                var clone = row.clone(true);
                clone.appendTo(control);
            });

            $('[data-delete-record]').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var row = $(this).parent('[data-row]');
                row.remove();
            });
        }
    }
})(jQuery, Drupal);
