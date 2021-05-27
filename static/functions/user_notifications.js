$(document).ready(function () {
  'use strict';

  var skip = getSkippedPage();
  $('.noty-notification').each(function () {
    var $this = $(this);
    var type = $this.data('noty-type'),
      importance = $this.data('noty-importance'),
      id = $this.data('noty-id'),
      url = $this.data('noty-url');

    if (type != skip) {
      createNoty(type, id, $this.text(), url, importance);
    }
    $this.remove();
  });
});

/**
 * getSkippedPage
 */
function getSkippedPage() {
  var skip
  var url = {
    path: window.location.pathname.split('/').reverse()[0].split('.')[0],
    query: window.location.search.slice(1).split('&').reduce((a, b) => Object.assign(a, { [b.split('=')[0]]: b.split('=')[1] }), {})
  };

  switch (url.path) {
    case 'inbox':
      if (url.query.length == 0 || (url.query.length == 1 && url.query.hasOwnProperty('sort'))) {
        skip = 'Inbox';
      }
      break;

    case 'userhistory':
      if (url.query['action'] == 'subscriptions') {
        skip = 'Subscriptions';
      }

      if (url.query['action'] == 'quote_notifications') {
        skip = 'Quotes';
      }

      if (url.query['action'] == 'subscribed_collages') {
        skip = 'Collages';
      }
      break;

    case 'user':
      if (url.query['action'] == 'notify') {
        skip = 'Torrents';
      }
      break;

    case 'blog':
      if (url.query.length == 0) {
        skip = 'Blog';
      }
      break;

    case 'index':
      if (url.query.length == 0) {
        skip = 'News';
      }
      break;

    case 'staffpm':
      if (url.query.length == 0) {
        skip = 'StaffPM';
      }
      break;

    default:
      break;
  }
  return skip;
}

/**
 * createNoty
 */
function createNoty(type, id, message, url, importance) {
  var hideButtons = !url;

  noty({
    text: message,
    type: importance,
    layout: 'bottomRight',
    closeWith: ['click'],

    animation: {
      open: { height: 'toggle' },
      close: { height: 'toggle' },
      easing: 'swing',
      speed: 200
    },

    buttons: [
      {
        addClass: 'brackets noty_button_view' + (hideButtons ? ' hidden' : ''),
        text: 'View',
        onClick: function ($noty) {
          window.location.href = url;
        }
      },

      {
        addClass: 'brackets noty_button_clear',
        text: 'Clear',
        onClick: function ($noty) {
          $noty.close();
          clear(type, id);
        }
      },

      {
        addClass: 'brackets noty_button_close ',
        text: 'Hide',
        onClick: function ($noty) {
          $noty.close();
        }
      },
    ]
  });
}

/**
 * clear
 */
function clear(type, id) {
  $.ajax({
    type: 'POST',
    url: 'api.php?action=clear_user_notification',
    dataType: 'json',
    data: {
      'type': type,
      'id': id
    }
  });
}