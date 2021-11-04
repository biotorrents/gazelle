/**
 * spoiler button stuff
 * from bbcode.js, will move
 */
function BBSpoiler(link) {
  if ($(link.nextSibling).has_class("hidden")) {
    $(link.nextSibling).gshow();
    $(link).html("Hide");
    if ($(link).attr("value")) {
      $(link).attr("value", "Hide" + $(link).attr("value").substring(4));
    }
  } else {
    $(link.nextSibling).ghide();
    $(link).html("Show");
    if ($(link).attr("value")) {
      $(link).attr("value", "Show" + $(link).attr("value").substring(4));
    }
  }
}

$(function () {
  $(document).on("click", ".spoilerButton", (e) => BBSpoiler(e.target));
});

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
/*
function add_tag(tag) {
  if ($("#tags").raw().value == "") {
    $("#tags").raw().value = tag;
  } else {
    $("#tags").raw().value = $("#tags").raw().value + ", " + tag;
  }
}
*/

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
 * toggle_edition
 */
function toggle_edition(groupid, editionid, lnk, event) {
  var clickedRow = lnk;
  while (clickedRow.nodeName != "TR") {
    clickedRow = clickedRow.parentNode;
  }

  //var showing = has_class(nextElementSibling(clickedRow), 'hidden');
  var showing = $(clickedRow).nextElementSibling().has_class("hidden");
  var allEditions = event.ctrlKey;
  var group_rows = $("tr.groupid_" + groupid);

  for (var i = 0; i < group_rows.length; i++) {
    var row = $(group_rows.raw(i));
    if (row.has_class("edition") && (allEditions || row.raw(0) == clickedRow)) {
      var tooltip = showing
        ? 'Collapse this edition. Hold "Ctrl" while clicking to collapse all editions in this torrent group.'
        : 'Expand this edition. Hold "Ctrl" while clicking to expand all editions in this torrent group.';
      $("a", row).raw().innerHTML = showing ? "&minus;" : "+";
      $("a", row).updateTooltip(tooltip);
      continue;
    }

    if (allEditions || row.has_class("edition_" + editionid)) {
      if (showing && !row.has_class("torrentdetails")) {
        row.gshow();
      } else {
        row.ghide();
      }
    }
  }

  if (event.preventDefault) {
    event.preventDefault();
  } else {
    // for IE < 9 support
    event.returnValue = false;
  }
}

/**
 * toggleTorrentSearch
 */
function toggleTorrentSearch(mode) {
  if (mode == 0) {
    var link = $("#ft_toggle").raw();
    $("#ft_container").gtoggle();
    link.innerHTML = link.textContent == "Hide" ? "Show" : "Hide";
  }

  if (mode == "basic") {
    $(".fti_advanced").disable();
    $(".fti_basic").enable();
    $(".ftr_advanced").ghide(true);
    $(".ftr_basic").gshow();
    $("#ft_advanced").ghide();
    $("#ft_basic").gshow();
    $("#ft_type").raw().value = "basic";
  } else if (mode == "advanced") {
    $(".fti_advanced").enable();
    $(".fti_basic").disable();
    $(".ftr_advanced").gshow();
    $(".ftr_basic").ghide();
    $("#ft_advanced").gshow();
    $("#ft_basic").ghide();
    $("#ft_type").raw().value = "advanced";
  }
  return false;
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

/**
 * ToggleEditionRows
 */
function ToggleEditionRows() {
  $("#edition_title").gtoggle();
  $("#edition_label").gtoggle();
  $("#edition_catalogue").gtoggle();
}