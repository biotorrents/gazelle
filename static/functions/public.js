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

  if (location.protocol != 'https:') {
    alert("This Gazelle installation will not be functional unless accessed over HTTPS");
  }
})
