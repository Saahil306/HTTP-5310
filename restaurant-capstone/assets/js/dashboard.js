jQuery(function ($) {

  // ================= TAB SWITCH =================
  $('.cb-sidebar a[data-tab]').on('click', function (e) {
    e.preventDefault();

    let tab = $(this).data('tab');

    $('.cb-sidebar a').removeClass('active');
    $(this).addClass('active');

    $('.cb-tab').removeClass('active');
    $('#tab-' + tab).addClass('active');
  });


  // ================= PROFILE UPDATE =================
  $('#profileForm').on('submit', function (e) {
    e.preventDefault();

    let fd = new FormData();
    fd.append('action', 'cb_update_profile');
    fd.append('nonce', cb_ajax.nonce);
    fd.append('name', $('[name="name"]').val());
    fd.append('password', $('[name="password"]').val());

    $.ajax({
      url: cb_ajax.ajax_url,
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      success: function (res) {
        $('#profileMsg')
          .text(res.data)
          .css('color', res.success ? 'lime' : 'red');
      }
    });
  });


  // ================= MENU FILTER (FIXED) =================
// ================= FRONTEND MENU FILTER (NO AJAX)
$(document).on('click', '.cb-filter-btn', function (e) {
  e.preventDefault();

  let cat = $(this).data('cat');

  $('.cb-filter-btn').removeClass('active');
  $(this).addClass('active');

  if (cat === 'all') {
    $('.cb-menu-card').show();
  } else {
    $('.cb-menu-card').hide();
    $('.cb-menu-card[data-cat="' + cat + '"]').show();
  }
});

// ================= RESERVATION SUBMIT
$('#reservationForm').on('submit', function (e) {
  e.preventDefault();

  let fd = new FormData(this);
  fd.append('action', 'cb_make_reservation');
  fd.append('nonce', cb_ajax.nonce);

  $.ajax({
    url: cb_ajax.ajax_url,
    type: 'POST',
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      $('#reservationMsg')
        .text(res.data)
        .css('color', res.success ? 'lime' : 'red');

      if (res.success) {
        $('#reservationForm')[0].reset();
      }
    }
  });
});

// ================= ADMIN ADD MENU
$('#addMenuForm').on('submit', function (e) {
  e.preventDefault();

  $.post(cb_ajax.ajax_url, $(this).serialize() + '&action=cb_add_menu_item', function (res) {
    $('#menuMsg').text(res.data).css('color', res.success ? 'lime' : 'red');
    if (res.success) location.reload();
  });
});

// ================= ADMIN DELETE MENU
$(document).on('click', '.cb-delete-menu', function () {
  if (!confirm('Delete this item?')) return;

  let id = $(this).data('id');

  $.post(cb_ajax.ajax_url, {
    action: 'cb_delete_menu_item',
    id: id
  }, () => location.reload());
});

// create user
$('#createUserForm').on('submit', function (e) {
  e.preventDefault();

  $.post(cb_ajax.ajax_url, $(this).serialize() + '&action=cb_create_user', function (res) {
    $('#userMsg').text(res.data).css('color', res.success ? 'lime' : 'red');
    if (res.success) location.reload();
  });
});

/*toggle user
$(document).on('change', '.cb-user-toggle', function () {
  $.post(cb_ajax.ajax_url, {
    action: 'cb_toggle_user',
    id: $(this).data('id'),
    val: this.checked ? '1' : '0'
  });
});*/

// export CSV
$('#exportReservations').on('click', function () {
  window.location = cb_ajax.ajax_url + '?action=cb_export_reservations';
});

// ================= UPDATE CAPACITY
$('#capacityForm').on('submit', function(e){
  e.preventDefault();

  $.post(cb_ajax.ajax_url, {
    action:'cb_update_capacity',
    capacity: $('[name="capacity"]').val()
  }, function(res){
    $('#capacityMsg')
      .text(res.data)
      .css('color', res.success ? 'lime' : 'red');
  });
});


// ================= UPDATE RESERVATION STATUS
$(document).on('change', '.cb-status-change', function () {

  let id = $(this).data('id');
  let status = $(this).val();
  let $select = $(this);

  $.post(cb_ajax.ajax_url, {
    action: 'cb_update_reservation_status',
    id: id,
    status: status
  }, function (res) {

    if (res.success) {

      // ✅ update badge color/text instantly
      let badge = $select.closest('tr').find('.cb-status');

      badge
        .removeClass('cb-status-confirmed cb-status-completed cb-status-cancelled')
        .addClass('cb-status-' + status)
        .text(status.charAt(0).toUpperCase() + status.slice(1));

    } else {
      alert(res.data || 'Update failed');
    }

  });

});


// ⭐ QUICK REBOOK
$(document).on('click', '.cb-quick-rebook', function () {

  let date   = $(this).data('date');
  let time   = $(this).data('time');
  let guests = $(this).data('guests');

  // switch to reserve tab
  $('.cb-sidebar a[data-tab="reserve"]').click();

  // autofill
  $('[name="date"]').val(date);
  $('[name="time"]').val(time);
  $('[name="guests"]').val(guests);

});

// ⭐ CUSTOMER CANCEL
$(document).on('click', '.cb-cancel-booking', function () {

  if (!confirm('Cancel this reservation?')) return;

  let id = $(this).data('id');
  let $row = $(this).closest('tr');

  $.post(cb_ajax.ajax_url, {
    action: 'cb_customer_cancel_reservation',
    id: id
  }, function (res) {

    if (res.success) {
      $row.find('.cb-status')
        .removeClass()
        .addClass('cb-status cb-status-cancelled')
        .text('Cancelled');

      $row.find('.cb-cancel-booking').remove();
    } else {
      alert(res.data || 'Failed');
    }

  });

});

// ================= QUICK REBOOK
$(document).on('click', '.cb-quick-rebook', function () {

  if (!confirm('Rebook this reservation for next available day?')) return;

  let id = $(this).data('id');

  $.post(cb_ajax.ajax_url, {
    action: 'cb_quick_rebook',
    id: id
  }, function (res) {

    alert(res.data || 'Done');

    if (res.success) {
      location.reload();
    }

  });

});

// ================= ADD CATEGORY
$('#addCategoryForm').on('submit', function(e){
  e.preventDefault();

  $.post(cb_ajax.ajax_url, $(this).serialize() + '&action=cb_add_category', function(res){
    $('#catMsg')
      .text(res.data)
      .css('color', res.success ? 'lime' : 'red');

    if(res.success) location.reload();
  });
});


// ================= DELETE CATEGORY
$(document).on('click', '.cb-delete-cat', function(){

  if(!confirm('Delete this category?')) return;

  $.post(cb_ajax.ajax_url, {
    action: 'cb_delete_category',
    id: $(this).data('id')
  }, () => location.reload());

});


// ================= EDIT MENU TOGGLE
$(document).on('click', '.cb-edit-menu', function () {
  let id = $(this).data('id');
  $('#edit-' + id).toggle();
});

// ================= CANCEL EDIT
$(document).on('click', '.cb-cancel-edit', function () {
  $(this).closest('.cb-edit-row').hide();
});

// ================= SAVE MENU EDIT
$(document).on('click', '.cb-save-menu', function () {

  let id = $(this).data('id');
  let row = $('#edit-' + id);

  $.post(cb_ajax.ajax_url, {
    action: 'cb_update_menu_item',
    id: id,
    title: row.find('.cb-edit-title').val(),
    description: row.find('.cb-edit-desc').val(),
    price: row.find('.cb-edit-price').val(),
    spice: row.find('.cb-edit-spice').val(),
    category: row.find('.cb-edit-category').val()
  }, function (res) {

    if (res.success) {
      location.reload(); // premium refresh
    } else {
      alert(res.data || 'Update failed');
    }

  });

});


// ================= STAFF EDIT TOGGLE =================
$(document).on('click', '.cb-edit-res', function (e) {
    e.preventDefault();

    let id = $(this).data('id');
    $('#edit-res-' + id).toggle();
});

// ================= STAFF CANCEL EDIT =================
$(document).on('click', '.cb-cancel-res', function () {
    $(this).closest('.cb-edit-row').hide();
});

// ================= STAFF SAVE =================
$(document).on('click', '.cb-save-res', function (e) {
    e.preventDefault();

    let id = $(this).data('id');
    let row = $('#edit-res-' + id);

    $.post(cb_ajax.ajax_url, {
        action: 'cb_staff_update_reservation',
        id: id,
        date: row.find('.cb-edit-date').val(),
        time: row.find('.cb-edit-time').val(),
        guests: row.find('.cb-edit-guests').val(),
        status: row.find('.cb-edit-status').val()
    }, function (res) {

        alert(res.data);

        if (res.success) {
            location.reload();
        }
    });
});



// ================= SYSTEM ADMIN UPDATE USER
$(document).on('click', '.cb-save-user', function () {

  let row = $(this).closest('tr');

  $.post(cb_ajax.ajax_url, {
    action: 'cb_update_user_account',
    id: $(this).data('id'),
    name: row.find('.cb-user-name').val(),
    email: row.find('.cb-user-email').val(),
    role: row.find('.cb-user-role').val(),
    password: row.find('.cb-user-pass').val(),
    active: row.find('.cb-user-toggle').is(':checked') ? '1' : '0'
  }, function (res) {

    alert(res.data);

    if (res.success) {
      location.reload();
    }

  });

});



// ================= UPDATE OPERATING HOURS
$('#hoursForm').on('submit', function(e){
  e.preventDefault();

  $.post(cb_ajax.ajax_url, {
    action: 'cb_update_hours',
    open: $('[name="open"]').val(),
    close: $('[name="close"]').val(),
    interval: $('[name="interval"]').val()
  }, function(res){
    $('#hoursMsg')
      .text(res.data)
      .css('color', res.success ? 'lime' : 'red');
  });
});

/* ================= RULES SAVE ================= */
jQuery(document).on('submit', '#rulesForm', function(e) {
  e.preventDefault();

  const form = jQuery(this);

  jQuery.post(cb_ajax.ajax_url, {
    action: 'cb_save_reservation_rules',
    max_guests: form.find('[name="max_guests"]').val(),
    advance_days: form.find('[name="advance_days"]').val(),
    cutoff: form.find('[name="cutoff"]').val(),
    nonce: cb_ajax.nonce
  }, function(res) {
    jQuery('#rulesMsg').text(res.data);
  });
});


/* ================= EXPORT CSV ================= */
jQuery(document).on('click', '#exportReservations', function() {
  window.location.href =
    cb_ajax.ajax_url + '?action=cb_export_reservations';
});


/* ================= HERO AUTO SLIDER ================= */
jQuery(function ($) {

  let slides = $('.hero-slide');
  let index = 0;

  if (slides.length > 1) {

    setInterval(function () {

      slides.removeClass('active');

      index++;
      if (index >= slides.length) index = 0;

      slides.eq(index).addClass('active');

    }, 4000); // 4 sec

  }

});

// ✅ ROLE CHANGE
jQuery(document).on('change', '.cb-user-role', function () {

    const userId = jQuery(this).data('id');
    const role   = jQuery(this).val();

    jQuery.post(cb_ajax.ajax_url, {
        action: 'cb_update_user_role',
        user_id: userId,
        role: role
    });
});


// ✅ STATUS TOGGLE
jQuery(document).on('change', '.cb-user-toggle', function () {

    const userId = jQuery(this).data('id');
    const active = jQuery(this).is(':checked') ? '1' : '0';

    jQuery.post(cb_ajax.ajax_url, {
        action: 'cb_toggle_user_status',
        user_id: userId,
        active: active
    });
});


// ===== UPLOAD GALLERY
$('#galleryForm').on('submit', function(e){
  e.preventDefault();

  let fd = new FormData(this);
  fd.append('action','cb_upload_gallery_image');

  $.ajax({
    url: cb_ajax.ajax_url,
    type:'POST',
    data: fd,
    processData:false,
    contentType:false,
    success:function(res){
      $('#galleryMsg').text(res.data);
      if(res.success) location.reload();
    }
  });
});

// ===== DELETE GALLERY
$(document).on('click','.cb-delete-gallery',function(){

  if(!confirm('Delete image?')) return;

  $.post(cb_ajax.ajax_url,{
    action:'cb_delete_gallery_image',
    id:$(this).data('id')
  },()=>location.reload());

});

jQuery(document).on('click', '.cb-save-page', function(e) {
    e.preventDefault(); // ⭐ VERY IMPORTANT

    const btn = jQuery(this);
    const id = btn.data('id');
    const content = btn.prev('.cb-page-content').val();

    jQuery.post(cb_ajax.ajax_url, {
        action: 'cb_save_page_content',
        page_id: id,
        content: content
    }, function(res) {
        if (res.success) {
            alert('Saved');
        } else {
            alert('Error');
        }
    });
});

jQuery(document).on('submit', '#createUserForm', function(e) {
    e.preventDefault();

    const form = jQuery(this);

    jQuery.post(cb_ajax.ajax_url, {
        action: 'cb_create_user',
        name: form.find('[name="name"]').val(),
        email: form.find('[name="email"]').val(),
        password: form.find('[name="password"]').val(),
        role: form.find('[name="role"]').val()
    }, function(res) {

        if (res.success) {
            jQuery('#userMsg').text(res.data).css('color','lightgreen');
            form[0].reset();
        } else {
            jQuery('#userMsg').text(res.data).css('color','red');
        }

    });

});
});