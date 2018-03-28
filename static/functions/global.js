"use strict";

//PHP ports
function html_entity_decode(str) {
  var el = document.createElement("div");
  el.innerHTML = str;
  for (var i = 0, ret = ''; i < el.childNodes.length; i++) {
    ret += el.childNodes[i].nodeValue;
  }
  return ret;
}

function get_size(size) {
  var steps = 0;
  for (; size >= 1024; size /= 1024, steps++);
  var exts = ['B','KiB','MiB','GiB','TiB','PiB','EiB','ZiB','YiB']
  return (size.toFixed(2) + (exts[steps]||''))
}

function ratio(a, b) {
  var rc = 'r50';
  for (var i of [[5,  '20'],[2,  '10'],[1,  '09'],[0.9,'08'],[0.8,'07'],[0.7,'06'],
                 [0.6,'05'],[0.5,'04'],[0.4,'03'],[0.3,'02'],[0.2,'01'],[0.1,'00']]) {
    if (a/b < i[0]) rc = 'r'+i[1];
  }
  if (b == 0) return a ? '<span class="r99">∞</span>' : '--';
  return '<span class="'+rc+'">'+((a/b)-0.005).toFixed(2)+'</span>';
}

function save_message(message, err = false) {
  var messageDiv = document.createElement("div");
  messageDiv.className = err ? "error_message" : "save_message";
  messageDiv.innerHTML = message;
  $("#content").raw().insertBefore(messageDiv,$("#content").raw().firstChild);
}

$.fn.extend({
  gshow: function() {
    return this.remove_class('hidden');
  },
  ghide: function(force) {
    return this.add_class('hidden', force);
  },
  gtoggle: function(force) {
    if (this[0].className.split(' ').indexOf('hidden') == -1) {
      this.add_class('hidden', force);
    } else {
      this.remove_class('hidden');
    }
    return this;
  },
  listen: function(event, callback) {
    for (var i = 0; i < this.length; i++) {
      var object = this[i];
      if (document.addEventListener) {
        object.addEventListener(event, callback, false);
      } else {
        object.attachEvent('on' + event, callback);
      }
    }
    return this;
  },
  add_class: function(class_name, force) {
    for (var i = 0; i < this.length; i++) {
      var object = this[i];
      if (object.className === '') {
        object.className = class_name;
      } else if (force || object.className.split(' ').indexOf(class_name) == -1) {
        object.className = object.className + ' ' + class_name;
      }
    }
    return this;
  },
  remove_class: function(class_name) {
    for (var i = 0; i < this.length; i++) {
      var object = this[i];
      var classes = object.className.split(' ');
      var result = classes.indexOf(class_name);
      if (result != -1) {
        classes.splice(result, 1);
        object.className = classes.join(' ');
      }
    }
    return this;
  },
  has_class: function(class_name) {
    for (var i = 0; i < this.length; i++) {
      var object = this[i];
      var classes = object.className.split(' ');
      if (classes.indexOf(class_name) != -1) {
        return true;
      }
    }
    return false;
  },
  toggle_class: function(class_name) {
    for (var i = 0, il; i < this.length; i++) {
      var object = this[i];
      var classes = object.className.split(' ');
      var result = classes.indexOf(class_name);
      if (result != -1) {
        classes.splice(result, 1);
        object.className = classes.join(' ');
      } else {
        if (object.className === '') {
          object.className = class_name;
        } else {
          object.className = object.className + ' ' + class_name;
        }
      }
    }
    return this;
  },
  disable : function() {
    $(this).prop('disabled', true);
    return this;
  },
  enable : function() {
    $(this).prop('disabled', false);
    return this;
  },
  raw: function(number) {
    if (typeof number == 'undefined') {
      number = 0;
    }
    return $(this).get(number);
  },
  nextElementSibling: function() {
    var here = this[0];
    if (here.nextElementSibling) {
      return $(here.nextElementSibling);
    }
    do {
      here = here.nextSibling;
    } while (here.nodeType != 1);
    return $(here);
  },
  previousElementSibling: function() {
    var here = this[0];
    if (here.previousElementSibling) {
      return $(here.previousElementSibling);
    }
    do {
      here = here.nextSibling;
    } while (here.nodeType != 1);
    return $(here);
  },
  updateTooltip: function(tooltip) {
    if ($.fn.tooltipster) {
      $(this).tooltipster('update', tooltip);
    } else {
      $(this).attr('title', tooltip);
    }
    return this;
  },

  // Disable unset form elements to allow search URLs cleanups
  disableUnset: function() {
    $('input, select', this).filter(function() {
      return $(this).val() === "";
    }).disable();
    return this;
  },

  // Prevent double submission of forms
  preventDoubleSubmission: function() {
    $(this).submit(function(e) {
      var $form = $(this);
      if ($form.data('submitted') === true) {
        e.preventDefault();
      } else {
        $form.data('submitted', true);
      }
    });
    return this;
  }
});

if ($('meta[name=authkey]').raw()) {
  var authkey = $('meta[name=authkey]').raw().content;
  var userid = parseInt($('meta[name=userid]').raw().content);
}

/**
 * Check or uncheck checkboxes in formElem
 * If masterElem is false, toggle each box, otherwise use masterElem's status on all boxes
 * If elemSelector is false, act on all checkboxes in formElem
 */
function toggleChecks(formElem, masterElem, elemSelector) {
  elemSelector = elemSelector || 'input:checkbox';
  if (masterElem) {
    $('#' + formElem + ' ' + elemSelector).prop('checked', masterElem.checked);
  } else {
    $('#' + formElem + ' ' + elemSelector).each(function() {
      this.checked = !this.checked;
    })
  }
}

var lightbox = {
  init: function (image, size) {
    if ($('#lightbox').length == 0 || $('#curtain').length == 0) {
      var lightboxEl = document.createElement('div')
      lightboxEl.id = 'lightbox'
      lightboxEl.className = 'lightbox hidden'
      var curtainEl = document.createElement('div')
      curtainEl.id = 'curtain'
      curtainEl.className = 'curtain hidden'
      $('#wrapper')[0].appendChild(lightboxEl)
      $('#wrapper')[0].appendChild(curtainEl)
    }
    if (typeof(image) == 'string') {
      $('#lightbox').gshow().listen('click', lightbox.unbox).raw().innerHTML =
        '<p size="7" style="color: gray; font-size: 50px;">Loading...<p>';
      $('#curtain').gshow().listen('click', lightbox.unbox);
      var src = image;
      image = new Image();
      image.onload = function() {
        lightbox.box_async(image);
      }
      image.src = src;
    }
    if (image.naturalWidth === undefined) {
      var tmp = document.createElement('img');
      tmp.style.visibility = 'hidden';
      tmp.src = image.src;
      image.naturalWidth = tmp.width;
    }
    if (image.naturalWidth > size) {
      lightbox.box(image);
    }
  },
  box: function (image) {
    var hasA = false;
    if (image.parentNode != null && image.parentNode.tagName.toUpperCase() == 'A') {
      hasA = true;
    }
    if (!hasA) {
      $('#lightbox').gshow().listen('click', lightbox.unbox).raw().innerHTML = '<img src="' + image.src + '" alt="" />';
      $('#curtain').gshow().listen('click', lightbox.unbox);
    }
  },
  box_async: function (image) {
    var hasA = false;
    if (image.parentNode != null && image.parentNode.tagName.toUpperCase() == 'A') {
      hasA = true;
    }
    if (!hasA) {
      $('#lightbox').raw().innerHTML = '<img src="' + image.src + '" alt="" />';
    }
  },
  unbox: function (data) {
    $('#curtain').ghide();
    $('#lightbox').ghide().raw().innerHTML = '';
  }
};

// Horrible hack to let arrow keys work as forward/back in lightbox
window.onkeydown = function(e) {
  e = e || window.event
  if (e.keyCode == 37 || e.keyCode == 39) {
    if ($('#lightbox').raw() && !$('#lightbox').raw().classList.contains('hidden')) {
      ($('[id!="lightbox"] > [lightbox-img="'+$('#lightbox > img').raw().src+'"]').raw()[((e.keyCode==39)?'next':'previous')+'Sibling'].click()||function(){})()
    }
  }
}

function resize(id) {
  var textarea = document.getElementById(id);
  if (textarea.scrollHeight > textarea.clientHeight) {
    textarea.style.height = Math.min(1000, textarea.scrollHeight + textarea.style.fontSize) + 'px';
  }
}

//ZIP downloader stuff
function add_selection() {
  var selected = $('#formats').raw().options[$('#formats').raw().selectedIndex];
  if (selected.disabled === false) {
    var listitem = document.createElement("li");
    listitem.id = 'list' + selected.value;
    listitem.innerHTML = '            <input type="hidden" name="list[]" value="' + selected.value + '" /> ' +
'            <span style="float: left;">' + selected.innerHTML + '</span>' +
'            <a href="#" onclick="remove_selection(\'' + selected.value + '\'); return false;" class="float_right" class="brackets">X</a>' +
'            <br style="clear: all;" />';
    $('#list').raw().appendChild(listitem);
    $('#opt' + selected.value).raw().disabled = true;
  }
}

function remove_selection(index) {
  $('#list' + index).remove();
  $('#opt' + index).raw().disabled = '';
}

function preload(image) {
  var img = document.createElement('img')
  img.style.display = 'none'
  img.src = image
  document.body.appendChild(img)
  document.body.removeChild(img)
}

let coverListener
function getCover(event) {
  let image = event.target.attributes['data-cover'].value
  $('#coverCont img').remove()
  let coverCont = ($('#coverCont').length==0)?document.body.appendChild(document.createElement('div')):$('#coverCont')[0]
  coverCont.id = 'coverCont'
  if ($('#coverCont img').length == 0) {
    coverCont.appendChild(document.createElement('img'))
  }
  $('#coverCont img')[0].src = image?image:'/static/common/noartwork/nocover.png'
  coverCont.style.display = 'block'
  coverListener = mevent => {
    let wh = window.innerHeight, ch = coverCont.clientHeight, ph = mevent.clientY
    let pos = (ph<wh/2) ? ((ph+ch+10>wh) ? wh-ch : ph+10) : ((ph-ch-10<0) ? 0 : ph-ch-10)
    coverCont.style.top = pos+'px'
    if (mevent.clientX > window.innerWidth/2) {
      coverCont.style.left = "initial"
      coverCont.style.right = window.innerWidth-mevent.clientX+10+"px"
    } else {
      coverCont.style.left = mevent.clientX+10+"px"
      coverCont.style.right = "initial"
    }
  }
  document.addEventListener("mousemove", coverListener)
  //Preload next image
  if ($('.torrent_table, .request_table').length > 0) {
    var as = $('[data-cover]')
    var a = event.target
    preload((as[as.toArray().indexOf(a)+1]||as[0]).attributes['data-cover'].value)
    preload((as[as.toArray().indexOf(a)-1]||as[0]).attributes['data-cover'].value)
  }
}
function ungetCover(event) {
  $('#coverCont img').remove()
  coverCont.style.display = 'none'
  document.removeEventListener("mousemove", coverListener)
}

// Apparently firefox doesn't implement NodeList.forEach until FF50
// Remove this shim after that's stable for a while
if (typeof NodeList.prototype.forEach !== 'function') {
  NodeList.prototype.forEach = Array.prototype.forEach
}

$(function() {
  document.querySelectorAll('[data-toggle-target]').forEach(function(el) {
    el.addEventListener('click', function(event) {
      $(el.attributes['data-toggle-target'].value).gtoggle()
      if (el.attributes['data-toggle-replace']) {
        [el.innerHTML, el.attributes['data-toggle-replace'].value] = [el.attributes['data-toggle-replace'].value, el.innerHTML]
      }
    })
  })

  $(document).on('mouseover', '[data-cover]', getCover)
  $(document).on('mouseleave', '[data-cover]', ungetCover)
  $(document).on('click', '.lightbox-init', function(e) {
    lightbox.init((e.target.attributes['lightbox-img']||[]).value||e.target.src, (e.target.attributes['lightbox-size']||[]).value||e.target.width)
  })
})
