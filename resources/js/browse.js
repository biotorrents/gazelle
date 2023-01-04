/**
 * torrent search page
 */

(() => {
  "use strict";

  // toggle search type
  siteOptions.searchType ??= null;

  if (siteOptions.searchType === "simple") {
    $("#simpleSearch").hide();
  }

  if (siteOptions.searchType === "complex") {
    $("#complexSearch").hide();
  }

  // show simple, hide complex
  $("#showSimpleSearch").on("click", () => {
    $("#simpleSearch").show();
    $("#complexSearch").hide();
  });

  // show complex, hide simple
  $("#showComplexSearch").on("click", () => {
    $("#complexSearch").show();
    $("#simpleSearch").hide();
  });

  // tom select: probably a less dumb way to do this
  var tomSelects = [
    new TomSelect("#sequencePlatforms"),
    new TomSelect("#graphPlatforms"),
    new TomSelect("#imagePlatforms"),
    new TomSelect("#documentPlatforms"),

    new TomSelect("#sequenceFormats"),
    new TomSelect("#imageFormats"),
    new TomSelect("#otherFormats"),
    //new TomSelect("#archiveFormats"),

    new TomSelect("#scope"),
    new TomSelect("#leechStatus"),
    new TomSelect("#license"),

    new TomSelect("#categories"),
    new TomSelect("#tagList"),

    new TomSelect("#orderBy"),
    new TomSelect("#orderWay"),
  ];

  /*
  // toggle tag list
  $("#toggleTagList").on("click", () => {
    $("#officialTagList").toggle();
  });
  */

  /*
  // append tag to search
  $(".officialTag").on("click", (event) => {
    let tagList = $("#tagList").val();
    let value = $(event.target).html();

    $("#tagList").val(tagList + "," + value);
  });
  */

  // reset the form
  $("#resetSearchForm").on("click", () => {
    // normal form
    let formObject = $("#torrentSearch").get(0);
    formObject.reset();

    // tom select elements
    tomSelects.forEach((element) => {
      element.clear();
    });
  });
})();

/** legacy */

/**
 * show_peers
 */
function show_peers(TorrentID, Page) {
  if (Page > 0) {
    ajax.get(
      "torrents.php?action=peerlist&page=" + Page + "&torrentid=" + TorrentID,
      function (response) {
        $("#peers_" + TorrentID)
          .gshow()
          .raw().innerHTML = response;
      }
    );
  } else {
    if ($("#peers_" + TorrentID).raw().innerHTML === "") {
      $("#peers_" + TorrentID)
        .gshow()
        .raw().innerHTML = "<h4>Loading&hellip;</h4>";
      ajax.get(
        "torrents.php?action=peerlist&torrentid=" + TorrentID,
        function (response) {
          $("#peers_" + TorrentID)
            .gshow()
            .raw().innerHTML = response;
        }
      );
    } else {
      $("#peers_" + TorrentID).gtoggle();
    }
  }

  $("#snatches_" + TorrentID).ghide();
  $("#downloads_" + TorrentID).ghide();
  $("#files_" + TorrentID).ghide();
  $("#reported_" + TorrentID).ghide();
}

/**
 * show_snatches
 */
function show_snatches(TorrentID, Page) {
  if (Page > 0) {
    ajax.get(
      "torrents.php?action=snatchlist&page=" + Page + "&torrentid=" + TorrentID,
      function (response) {
        $("#snatches_" + TorrentID)
          .gshow()
          .raw().innerHTML = response;
      }
    );
  } else {
    if ($("#snatches_" + TorrentID).raw().innerHTML === "") {
      $("#snatches_" + TorrentID)
        .gshow()
        .raw().innerHTML = "<h4>Loading...</h4>";
      ajax.get(
        "torrents.php?action=snatchlist&torrentid=" + TorrentID,
        function (response) {
          $("#snatches_" + TorrentID)
            .gshow()
            .raw().innerHTML = response;
        }
      );
    } else {
      $("#snatches_" + TorrentID).gtoggle();
    }
  }

  $("#peers_" + TorrentID).ghide();
  $("#downloads_" + TorrentID).ghide();
  $("#files_" + TorrentID).ghide();
  $("#reported_" + TorrentID).ghide();
}

/**
 * show_downloads
 */
function show_downloads(TorrentID, Page) {
  if (Page > 0) {
    ajax.get(
      "torrents.php?action=downloadlist&page=" +
        Page +
        "&torrentid=" +
        TorrentID,
      function (response) {
        $("#downloads_" + TorrentID)
          .gshow()
          .raw().innerHTML = response;
      }
    );
  } else {
    if ($("#downloads_" + TorrentID).raw().innerHTML === "") {
      $("#downloads_" + TorrentID)
        .gshow()
        .raw().innerHTML = "<h4>Loading...</h4>";
      ajax.get(
        "torrents.php?action=downloadlist&torrentid=" + TorrentID,
        function (response) {
          $("#downloads_" + TorrentID).raw().innerHTML = response;
        }
      );
    } else {
      $("#downloads_" + TorrentID).gtoggle();
    }
  }

  $("#peers_" + TorrentID).ghide();
  $("#snatches_" + TorrentID).ghide();
  $("#files_" + TorrentID).ghide();
  $("#reported_" + TorrentID).ghide();
}

/**
 * show_files
 */
function show_files(TorrentID) {
  $("#files_" + TorrentID).gtoggle();
  $("#peers_" + TorrentID).ghide();
  $("#snatches_" + TorrentID).ghide();
  $("#downloads_" + TorrentID).ghide();
  $("#reported_" + TorrentID).ghide();
}

/**
 * show_reported
 */
function show_reported(TorrentID) {
  $("#files_" + TorrentID).ghide();
  $("#peers_" + TorrentID).ghide();
  $("#snatches_" + TorrentID).ghide();
  $("#downloads_" + TorrentID).ghide();
  $("#reported_" + TorrentID).gtoggle();
}

/**
 * add_tag
 */
function add_tag(tag) {
  if ($("#tags").raw().value == "") {
    $("#tags").raw().value = tag;
  } else {
    $("#tags").raw().value = $("#tags").raw().value + ", " + tag;
  }
}

/**
 * toggle_group
 */
function toggle_group(groupid, link, event) {
  window.getSelection().removeAllRanges();
  var toToggle = event.shiftKey
    ? $(".group_torrent")
    : $(".groupid_" + groupid);
  var toReButton = event.shiftKey
    ? $(".hide_torrents, .show_torrents")
    : [link.parentNode];

  if (link.parentNode.className == "hide_torrents") {
    for (var i = 0; i < toToggle.length; i++) {
      toToggle[i].classList.add("hidden");
    }

    for (var i = 0; i < toReButton.length; i++) {
      toReButton[i].className = "show_torrents";
    }
  } else {
    for (var i = 0; i < toToggle.length; i++) {
      toToggle[i].classList.remove("hidden");
    }

    for (var i = 0; i < toReButton.length; i++) {
      toReButton[i].className = "hide_torrents";
    }
  }
}

/**
 * addCoverField
 */
var coverFieldCount = 0;
var hasCoverAddButton = false;
function addCoverField() {
  if (coverFieldCount >= 100) {
    return;
  }

  var x = $("#add_cover").raw();
  x.appendChild(document.createElement("br"));

  var field = document.createElement("input");
  field.type = "text";
  field.name = "image[]";
  field.placeholder = "URL";
  x.appendChild(field);
  x.appendChild(document.createTextNode(" "));

  var summary = document.createElement("input");
  summary.type = "text";
  summary.name = "summary[]";
  summary.placeholder = "Summary";
  x.appendChild(summary);
  coverFieldCount++;

  if (!hasCoverAddButton) {
    x = $("#add_covers_form").raw();
    field = document.createElement("input");
    field.type = "submit";
    field.value = "Add";
    x.appendChild(field);
    hasCoverAddButton = true;
  }
}
