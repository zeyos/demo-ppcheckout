$(document).ready(function() {
  function updateCheckoutButton() {
    console.log('len', $('[data-cart]').length);
    if ($('[data-cart]').length > 0) {
      $('#btnCheckout').removeAttr('disabled');
    } else {
      $('#btnCheckout').attr('disabled', 'disabled');
    }
  }

  $.each($('[data-item]'), function(index, elem) {
    $(elem).click(function() {
      if ($('[data-cart="' + $(elem).attr('data-item') + '"]').length == 0) {
        $('#itemsTable').append(
          '<tr data-cart="' + $(elem).attr('data-item') + '">'
          + '<td>' + $(elem).attr('data-name') + '</td>'
          + '<td>' + $(elem).attr('data-price') + '</td>'
          + '<td>'
            + '<button type="button" class="btn btn-xs btn-danger" data-remove="' + $(elem).attr('data-item') + '"><span class="glyphicon glyphicon-trash"></span></button>'
            + '<input type="hidden" name="checkout[]" value="' + $(elem).attr('data-item') + '" />'
          + '</td>'
          + '</tr>'
        );

        $('[data-remove="' + $(elem).attr('data-item') + '"]').click(function() {
          $('[data-cart="' + $(elem).attr('data-item') + '"]').remove();
          updateCheckoutButton();
        });

        updateCheckoutButton();
      }

      $('#cart').modal('show');
    });
  });
});
