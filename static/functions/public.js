if ($('#no-cookies')) {
  cookie.set('cookie_test', 1, 1);
  if (cookie.get('cookie_test') != null) {
      cookie.del('cookie_test');
  } else {
      $('#no-cookies').gshow();
  }
}

$(() => {
  if ($('#bg_data')) {
    $('#content')[0].style.backgroundImage = "url(/misc/bg/"+$('#bg_data')[0].attributes.bg.value+")";
  }
})
