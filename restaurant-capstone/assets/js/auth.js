jQuery(document).ready(function ($) {

  // ================= REGISTER =================
  $('#registerForm').on('submit', function (e) {
    e.preventDefault();

    let fd = new FormData();
    fd.append('action', 'cb_register');
    fd.append('nonce', cb_ajax.nonce);
    fd.append('name', $('#registerForm input[name="name"]').val());
    fd.append('email', $('#registerForm input[name="email"]').val());
    fd.append('password', $('#registerForm input[name="password"]').val());

    console.log('REGISTER DATA:', Object.fromEntries(fd));

    $.ajax({
      url: cb_ajax.ajax_url,
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      success: function (res) {
        console.log('REGISTER RESPONSE:', res);

        if (res.success) {
          $('#registerMsg').text(res.data).css('color', 'lime');
          $('#registerForm')[0].reset();
        } else {
          $('#registerMsg').text(res.data).css('color', 'red');
        }
      },
      error: function (xhr) {
        console.log('AJAX ERROR:', xhr.responseText);
        $('#registerMsg').text('AJAX error').css('color', 'red');
      }
    });
  });


  // ================= LOGIN =================
  $('#loginForm').on('submit', function (e) {
    e.preventDefault();

    let fd = new FormData();
    fd.append('action', 'cb_login');
    fd.append('nonce', cb_ajax.nonce);
    fd.append('email', $('#loginForm input[name="email"]').val());
    fd.append('password', $('#loginForm input[name="password"]').val());

    console.log('LOGIN DATA:', Object.fromEntries(fd));

    $.ajax({
      url: cb_ajax.ajax_url,
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      success: function (res) {
        console.log('LOGIN RESPONSE:', res);

        if (res.success) {
          window.location.href = res.data.redirect;
        } else {
          $('#loginMsg').text(res.data).css('color', 'red');
        }
      },
      error: function (xhr) {
        console.log('AJAX ERROR:', xhr.responseText);
        $('#loginMsg').text('AJAX error').css('color', 'red');
      }
    });
  });

});