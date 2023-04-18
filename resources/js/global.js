/**
 * global javascript
 */

(() => {
  "use strict";


  /**
   * third party libraries
   */

  // smart quotes
  // https://smartquotes.js.org
  smartquotes();

  // code syntax highlighting
  // https://highlightjs.org
  hljs.highlightAll();

  // tom select
  // https://tom-select.js.org
  document.querySelectorAll(".select").forEach((element) => {
    new TomSelect(element);
  });

  // start jquery extensions
  // todo: continue to prune
  $.fn.extend({
    gshow: function () {
      return this.removeClass("hidden");
    },

    ghide: function (force) {
      return this.addClass("hidden", force);
    },

    gtoggle: function (force) {
      if (this[0].className.split(" ").indexOf("hidden") === -1) {
        this.addClass("hidden", force);
      } else {
        this.removeClass("hidden");
      }
      return this;
    },

    listen: function (event, callback) {
      for (let i = 0; i < this.length; i++) {
        let object = this[i];
        if (document.addEventListener) {
          object.addEventListener(event, callback, false);
        } else {
          object.attachEvent("on" + event, callback);
        }
      }
      return this;
    },

    enable: function () {
      $(this).prop("disabled", false);
      return this;
    },

    disable: function () {
      $(this).prop("disabled", true);
      return this;
    },

    raw: function (number) {
      if (typeof number === "undefined") {
        number = 0;
      }
      return $(this).get(number);
    },

    nextElementSibling: function () {
      let here = this[0];
      if (here.nextElementSibling) {
        return $(here.nextElementSibling);
      }
      do {
        here = here.nextSibling;
      } while (here.nodeType !== 1);
      return $(here);
    },

    previousElementSibling: function () {
      let here = this[0];
      if (here.previousElementSibling) {
        return $(here.previousElementSibling);
      }
      do {
        here = here.nextSibling;
      } while (here.nodeType !== 1);
      return $(here);
    },

    // prevent double submission of forms
    preventDoubleSubmission: function () {
      $(this).submit(function (e) {
        let form = $(this);
        if (form.data("submitted") === true) {
          e.preventDefault();
        } else {
          form.data("submitted", true);
        }
      });
      return this;
    },
  }); // end jquery extensions


  /**
   * main menu
   */

  // toggle menu
  $("#subMenu").hide();
  $("#hamburger").on("click", function () {
    $("#subMenu").toggle();
    $("#hamburger").toggleClass("active");

    // change icon
    let isActive = $("#hamburger").hasClass("active");
    if (isActive) {
      $("#toggleIcon").html("<i class='fal fa-times'></i>");
    } else {
      $("#toggleIcon").html("<i class='fal fa-bars'></i>");

    }

    // close on esc
    $(document).on('keydown', function (event) {
      if (event.key == "Escape") {
        $("#subMenu").hide();
        $("#hamburger").removeClass("active");
        $("#toggleIcon").html("<i class='fal fa-bars'></i>");
      }
    });

    // change the search bar target
    // https://stackoverflow.com/a/16750165
    $("#searchWhat").on("change", function () {
      let action = $(this).val();
      $("#universalSearch").attr("action", action + ".php");
    });
  }); // end main menu
})(); // end iife


/** legacy */


/**
 * get_size
 */
function get_size(size) {
  var steps = 0;
  for (; size >= 1024; size /= 1024, steps++) { }
  var exts = ["B", "KiB", "MiB", "GiB", "TiB", "PiB", "EiB", "ZiB", "YiB"];
  return size.toFixed(2) + (exts[steps] || "");
}


/**
 * ratio
 */
function ratio(a, b) {
  var rc = "r50";
  for (var i of [
    [5, "20"],
    [2, "10"],
    [1, "09"],
    [0.9, "08"],
    [0.8, "07"],
    [0.7, "06"],
    [0.6, "05"],
    [0.5, "04"],
    [0.4, "03"],
    [0.3, "02"],
    [0.2, "01"],
    [0.1, "00"],
  ]) {
    if (a / b < i[0]) rc = "r" + i[1];
  }
  if (b == 0) return a ? '<span class="r99">∞</span>' : "--";
  return '<span class="' + rc + '">' + (a / b - 0.005).toFixed(2) + "</span>";
}


/**
 * save_message
 */
function save_message(message, err = false) {
  var messageDiv = document.createElement("div");
  messageDiv.className = err ? "error_message box" : "save_message box";
  messageDiv.innerHTML = message;
  $("#content").raw().insertBefore(messageDiv, $("#content").raw().firstChild);
}


/**
 * toggleChecks
 *
 * Check or uncheck checkboxes in formElem.
 * If masterElem is false, toggle each box, otherwise use masterElem's status on all boxes.
 * If elemSelector is false, act on all checkboxes in formElem.
 */
function toggleChecks(formElem, masterElem, elemSelector) {
  elemSelector = elemSelector || "input:checkbox";
  if (masterElem) {
    $("#" + formElem + " " + elemSelector).prop("checked", masterElem.checked);
  } else {
    $("#" + formElem + " " + elemSelector).each(function () {
      this.checked = !this.checked;
    });
  }
}


/**
 * lightbox
 */
var lightbox = {
  init: function (image, size) {
    if ($("#lightbox").length == 0 || $("#curtain").length == 0) {
      var lightboxEl = document.createElement("div");
      lightboxEl.id = "lightbox";
      lightboxEl.className = "lightbox hidden";
      var curtainEl = document.createElement("div");
      curtainEl.id = "curtain";
      curtainEl.className = "curtain hidden";
      $("#wrapper")[0].appendChild(lightboxEl);
      $("#wrapper")[0].appendChild(curtainEl);
    }
    if (typeof image == "string") {
      $("#lightbox").gshow().listen("click", lightbox.unbox).raw().innerHTML =
        '<p size="7" style="color: gray; font-size: 50px;">Loading...<p>';
      $("#curtain").gshow().listen("click", lightbox.unbox);
      var src = image;
      image = new Image();
      image.onload = function () {
        lightbox.box_async(image);
      };
      image.src = src;
    }
    if (image.naturalWidth === undefined) {
      var tmp = document.createElement("img");
      tmp.style.visibility = "hidden";
      tmp.src = image.src;
      image.naturalWidth = tmp.width;
    }
    if (image.naturalWidth > size) {
      lightbox.box(image);
    }
  },

  box: function (image) {
    var hasA = false;
    if (
      image.parentNode != null &&
      image.parentNode.tagName.toUpperCase() == "A"
    ) {
      hasA = true;
    }
    if (!hasA) {
      $("#lightbox").gshow().listen("click", lightbox.unbox).raw().innerHTML =
        '<img src="' + image.src + '" alt="" />';
      $("#curtain").gshow().listen("click", lightbox.unbox);
    }
  },

  box_async: function (image) {
    var hasA = false;
    if (
      image.parentNode != null &&
      image.parentNode.tagName.toUpperCase() == "A"
    ) {
      hasA = true;
    }
    if (!hasA) {
      $("#lightbox").raw().innerHTML = '<img src="' + image.src + '" alt="" />';
    }
  },

  unbox: function (data) {
    $("#curtain").ghide();
    $("#lightbox").ghide().raw().innerHTML = "";
  },
};

// horrible hack to let arrow keys work as forward/back in lightbox
window.onkeydown = function (e) {
  e = e || window.event;
  if (e.keyCode == 37 || e.keyCode == 39) {
    if (
      $("#lightbox").raw() &&
      !$("#lightbox").raw().classList.contains("hidden")
    ) {
      (
        $(
          '[id!="lightbox"] > [lightbox-img="' +
          $("#lightbox > img").raw().src +
          '"]'
        )
          .raw()
        [(e.keyCode == 39 ? "next" : "previous") + "Sibling"].click() ||
        function () { }
      )();
    }
  }
};


/**
 * resize
 */
function resize(id) {
  var textarea = document.getElementById(id);
  if (textarea.scrollHeight > textarea.clientHeight) {
    textarea.style.height =
      Math.min(1000, textarea.scrollHeight + textarea.style.fontSize) + "px";
  }
}


/**
 * add_selection
 *
 * ZIP downloader stuff.
 */
function add_selection() {
  var selected = $("#formats").raw().options[$("#formats").raw().selectedIndex];
  if (selected.disabled === false) {
    var listitem = document.createElement("li");
    listitem.id = "list" + selected.value;
    listitem.innerHTML =
      '            <input type="hidden" name="list[]" value="' +
      selected.value +
      '" /> ' +
      '            <span style="float: left;">' +
      selected.innerHTML +
      "</span>" +
      '            <a href="#" onclick="remove_selection(\'' +
      selected.value +
      '\'); return false;" class="u-pull-right" class="brackets">X</a>' +
      '            <br style="clear: all;" />';
    $("#list").raw().appendChild(listitem);
    $("#opt" + selected.value).raw().disabled = true;
  }
}


/**
 * remove_selection
 */
function remove_selection(index) {
  $("#list" + index).remove();
  $("#opt" + index).raw().disabled = "";
}


/**
 * preload
 */
function preload(image) {
  var img = document.createElement("img");
  img.style.display = "none";
  img.src = image;
  document.body.appendChild(img);
  document.body.removeChild(img);
}

let coverListener;
function getCover(event) {
  let image = event.target.attributes["data-cover"].value;
  $("#coverCont img").remove();
  let coverCont =
    $("#coverCont").length == 0
      ? document.body.appendChild(document.createElement("div"))
      : $("#coverCont")[0];
  coverCont.id = "coverCont";
  if ($("#coverCont img").length == 0) {
    coverCont.appendChild(document.createElement("img"));
  }
  $("#coverCont img")[0].src = image ? image : "/public/images/noartwork.png";
  coverCont.style.display = "block";
  coverListener = (mevent) => {
    let wh = window.innerHeight,
      ch = coverCont.clientHeight,
      ph = mevent.clientY;
    let pos =
      ph < wh / 2
        ? ph + ch + 10 > wh
          ? wh - ch
          : ph + 10
        : ph - ch - 10 < 0
          ? 0
          : ph - ch - 10;
    coverCont.style.top = pos + "px";
    if (mevent.clientX > window.innerWidth / 2) {
      coverCont.style.left = "initial";
      coverCont.style.right = window.innerWidth - mevent.clientX + 10 + "px";
    } else {
      coverCont.style.left = mevent.clientX + 10 + "px";
      coverCont.style.right = "initial";
    }
  };
  document.addEventListener("mousemove", coverListener);
  // preload next image
  if ($(".torrent_table, .request_table").length > 0) {
    var as = $("[data-cover]");
    var a = event.target;
    preload(
      (as[as.toArray().indexOf(a) + 1] || as[0]).attributes["data-cover"].value
    );
    preload(
      (as[as.toArray().indexOf(a) - 1] || as[0]).attributes["data-cover"].value
    );
  }
}


/**
 * ungetCover
 */
function ungetCover(event) {
  $("#coverCont img").remove();
  coverCont.style.display = "none";
  document.removeEventListener("mousemove", coverListener);
}


/**
 * iife
 */
$(function () {
  document.querySelectorAll("[data-toggle-target]").forEach(function (el) {
    el.addEventListener("click", function (event) {
      $(el.attributes["data-toggle-target"].value).gtoggle();
      if (el.attributes["data-toggle-replace"]) {
        [el.innerHTML, el.attributes["data-toggle-replace"].value] = [
          el.attributes["data-toggle-replace"].value,
          el.innerHTML,
        ];
      }
    });
  });

  $(document).on("mouseover", "[data-cover]", getCover);
  $(document).on("mouseleave", "[data-cover]", ungetCover);
  $(document).on("click", ".lightbox-init", function (e) {
    lightbox.init(
      (e.target.attributes["lightbox-img"] || []).value || e.target.src,
      (e.target.attributes["lightbox-size"] || []).value || e.target.width
    );
  });
});
