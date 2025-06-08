(function ($, Drupal, once) {
  Drupal.behaviors.brandSafetyDataTables = {
    attach: function (context, settings) {
      $(once('keyword-table', '#keyword-table', context)).DataTable({
        pageLength: 25,
        ordering: true,
        language: {
          search: "Filter:",
          lengthMenu: "Show _MENU_ entries",
        }
      });
    }
  };
})(jQuery, Drupal, once);
