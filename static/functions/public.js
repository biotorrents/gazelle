$(() => {
  if ($('[name=bg_data]')) {
    $('#content')[0].style.backgroundImage = "url(/misc/bg/"+$('[name=bg_data]')[0].attributes.content.value+")";
  }

  if ($('#no-cookies')) {
    cookie.set('cookie_test', 1, 1);
    if (cookie.get('cookie_test') != null) {
        cookie.del('cookie_test');
    } else {
        $('#no-cookies').gshow();
    }
  }
})
