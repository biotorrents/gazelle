var ARTIST_AUTOCOMPLETE_URL = 'artist.php?action=autocomplete';
var TAGS_AUTOCOMPLETE_URL = 'torrents.php?action=autocomplete_tags';
var SELECTOR = '[data-gazelle-autocomplete="true"]';
$(document).ready(initAutocomplete)

/**
 * initAutocomplete
 */
function initAutocomplete() {
  if (!$.Autocomplete) {
    window.setTimeout(function () {
      initAutocomplete();
    }, 500)
    return;
  }

  var url = {
    path: window.location.pathname.split('/').reverse()[0].split(".")[0],
    query: window.location.search.slice(1).split('&').reduce((a, b) => Object.assign(a, { [b.split('=')[0]]: b.split('=')[1] }), {})
  }

  $('#artistsearch' + SELECTOR).autocomplete({
    deferRequestBy: 300,
    onSelect: function (suggestion) {
      window.location = 'artist.php?id=' + suggestion['data'];
    },
    serviceUrl: ARTIST_AUTOCOMPLETE_URL,
  });

  if (url.path == 'torrents' || url.path == 'upload' || url.path == 'artist' || (url.path == 'requests' && url.query['action'] == 'new')) {
    $("#artist" + SELECTOR).autocomplete({
      deferRequestBy: 300,
      serviceUrl: ARTIST_AUTOCOMPLETE_URL
    });
  }

  if (url.path == 'torrents' || url.path == 'upload' || url.path == 'collages' || url.path == 'requests' || url.path == 'top10' || (url.path == 'requests' && url.query['action'] == 'new')) {
    $("#tags" + SELECTOR).autocomplete({
      deferRequestBy: 300,
      delimiter: ',',
      serviceUrl: TAGS_AUTOCOMPLETE_URL
    });

    $("#tagname" + SELECTOR).autocomplete({
      deferRequestBy: 300,
      delimiter: ',',
      serviceUrl: TAGS_AUTOCOMPLETE_URL
    });
  }

  if (url.path == 'upload' || (url.path == 'torrents' && url.query['action'] == 'editgroup')) {
    $("#artist_0" + SELECTOR).autocomplete({
      deferRequestBy: 300,
      serviceUrl: ARTIST_AUTOCOMPLETE_URL
    });
  }

  if (url.path == 'requests' && url.query['action'] == 'new') {
    $("#artist_0" + SELECTOR).autocomplete({
      deferRequestBy: 300,
      serviceUrl: ARTIST_AUTOCOMPLETE_URL
    });
  }
};
