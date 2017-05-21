$(() => {
  if ($('#bg_data')) {
    $('#content')[0].style.backgroundImage = "url(/misc/bg/"+$('#bg_data')[0].attributes.bg.value+")";
  }

  if ($('#no-cookies')) {
    cookie.set('cookie_test', 1, 1);
    if (cookie.get('cookie_test') != null) {
        cookie.del('cookie_test');
    } else {
        $('#no-cookies').gshow();
    }
  }

  if ($('#keep2fa').length) {
    $('#2fa_tr').ghide()
    $('#keep2fa')[0].onclick = (e) => {
      $('#2fa_tr')[$('#keep2fa')[0].checked ? 'gshow' : 'ghide']()
    }
  }
})
