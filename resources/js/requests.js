function Vote(amount, requestid) {
  if (typeof amount == "undefined") {
    amount = parseInt($("#amount").raw().value);
  }
  if (amount == 0) {
    amount = 20 * 1024 * 1024;
  }

  var index;
  var votecount;
  if (!requestid) {
    requestid = $("#requestid").raw().value;
    votecount = $("#votecount").raw();
    index = false;
  } else {
    votecount = $("#vote_count_" + requestid).raw();
    bounty = $("#bounty_" + requestid).raw();
    index = true;
  }

  if (amount > 20 * 1024 * 1024) {
    upload = $("#current_uploaded").raw().value;
    download = $("#current_downloaded").raw().value;
    rr = $("#current_rr").raw().value;
    if (amount > 0.3 * (upload - rr * download)) {
      if (
        !confirm(
          "This vote is more than 30% of your buffer. Please confirm that you wish to place this large of a vote."
        )
      ) {
        return false;
      }
    }
  }

  ajax.get(
    "requests.php?action=takevote&id=" +
      requestid +
      "&auth=" +
      authkey +
      "&amount=" +
      amount,
    function (response) {
      if (response == "bankrupt") {
        save_message(
          "You do not have sufficient upload credit to add " +
            get_size(amount) +
            " to this request",
          true
        );
        return;
      } else if (response == "dupesuccess") {
        //No increment
      } else if (response == "success") {
        votecount.innerHTML = parseInt(votecount.innerHTML) + 1;
      }

      if ($("#total_bounty").length > 0) {
        totalBounty = parseInt($("#total_bounty").raw().value);
        totalBounty += amount * (1 - $("#request_tax").raw().value);
        $("#total_bounty").raw().value = totalBounty;
        $("#formatted_bounty").raw().innerHTML = get_size(totalBounty);

        save_message(
          "Your vote of " +
            get_size(amount) +
            ", adding a " +
            get_size(amount * (1 - $("#request_tax").raw().value)) +
            " bounty, has been added"
        );
        $("#button").raw().disabled = true;
      } else {
        save_message("Your vote of " + get_size(amount) + " has been added");
      }
    }
  );
}

function Calculate() {
  if (!$("#unit").raw()) {
    return;
  }
  var mul =
    $("#unit").raw().options[$("#unit").raw().selectedIndex].value == "mb"
      ? 1024 * 1024
      : 1024 * 1024 * 1024;
  var amt = Math.floor($("#amount_box").raw().value * mul);
  if (amt > $("#current_uploaded").raw().value) {
    $("#new_uploaded").raw().innerHTML = "You can't afford that request!";
    $("#new_bounty").raw().innerHTML = "0.00 MB";
    $("#bounty_after_tax").raw().innerHTML = "0.00 MB";
    $("#button").raw().disabled = true;
  } else if (
    isNaN($("#amount_box").raw().value) ||
    (window.location.search.indexOf("action=new") != -1 &&
      $("#amount_box").raw().value * mul < 100 * 1024 * 1024) ||
    (window.location.search.indexOf("action=view") != -1 &&
      $("#amount_box").raw().value * mul < 20 * 1024 * 1024)
  ) {
    $("#new_uploaded").raw().innerHTML = get_size(
      $("#current_uploaded").raw().value
    );
    $("#new_bounty").raw().innerHTML = "0.00 MB";
    $("#bounty_after_tax").raw().innerHTML = "0.00 MB";
    $("#button").raw().disabled = true;
  } else {
    $("#button").raw().disabled = false;
    $("#amount").raw().value = amt;
    $("#new_uploaded").raw().innerHTML = get_size(
      $("#current_uploaded").raw().value - amt
    );
    $("#new_ratio").raw().innerHTML = ratio(
      $("#current_uploaded").raw().value - amt,
      $("#current_downloaded").raw().value
    );
    $("#new_bounty").raw().innerHTML = get_size(
      mul * $("#amount_box").raw().value
    );
    $("#bounty_after_tax").raw().innerHTML = get_size(
      mul * 0.9 * $("#amount_box").raw().value
    );
  }
}

var ArtistCount = 1;
function AddArtistField() {
  window.getSelection().removeAllRanges();
  ArtistCount = $('input[name="artists[]"]').length;

  if (ArtistCount >= 200) {
    return;
  }

  var ArtistField = document.createElement("input");
  ArtistField.type = "text";
  ArtistField.id = "artists_" + ArtistCount;
  ArtistField.name = "artists[]";
  ArtistField.size = 45;

  var x = $("#artistfields").raw();
  x.appendChild(document.createElement("br"));
  x.appendChild(ArtistField);
  x.appendChild(document.createTextNode("\n"));

  if ($("#artists_0").data("gazelle-autocomplete")) {
    $(ArtistField).on("focus", function () {
      $(ArtistField).autocomplete({
        serviceUrl: ARTIST_AUTOCOMPLETE_URL,
      });
    });
  }
  ArtistCount++;
}

function RemoveArtistField() {
  window.getSelection().removeAllRanges();
  ArtistCount = $('input[name="artists[]"]').length;

  if (ArtistCount == 1) {
    return;
  }

  var x = $("#artistfields").raw();
  for (i = 0; i < 3; i++) {
    x.removeChild(x.lastChild);
  }
  ArtistCount--;
}

function add_tag() {
  if ($("#tags").raw().value == "") {
    $("#tags").raw().value =
      $("#genre_tags").raw().options[
        $("#genre_tags").raw().selectedIndex
      ].value;
  } else if (
    $("#genre_tags").raw().options[$("#genre_tags").raw().selectedIndex]
      .value == "---"
  ) {
  } else {
    $("#tags").raw().value =
      $("#tags").raw().value +
      ", " +
      $("#genre_tags").raw().options[$("#genre_tags").raw().selectedIndex]
        .value;
  }
}

function Toggle(id, disable) {
  var arr = document.getElementsByName(id + "[]");
  var master = $("#toggle_" + id).raw().checked;
  for (var x in arr) {
    arr[x].checked = master;
    if (disable == 1) {
      arr[x].disabled = master;
    }
  }

  if (id == "formats") {
    ToggleLogCue();
  }
}

function ToggleLogCue() {
  var formats = document.getElementsByName("formats[]");
  var flac = false;

  if (formats[1].checked) {
    flac = true;
  }

  if (flac) {
    $("#logcue_tr").gshow();
  } else {
    $("#logcue_tr").ghide();
  }
  ToggleLogScore();
}

function ToggleLogScore() {
  if ($("#needlog").raw().checked) {
    $("#minlogscore_span").gshow();
  } else {
    $("#minlogscore_span").ghide();
  }
}

function JavAutofill() {
  var map = {
    cn: "javdb",
    idols: "artist",
    title: "title",
    title_jp: "title_jp",
    image: "image",
    tags: "tags",
    description: "req_desc",
  };
  var cn = $("#catalogue").raw().value.toUpperCase();
  $.getJSON("/api.php?action=javfill&cn=" + cn, function (data) {
    if (data.status != "success") {
      $("#catalogue").raw().value = "Failed";
      return;
    } else {
      $("#catalogue").raw().value = data.response.cn;
    }
    for (i in data.response) {
      if (Array.isArray(data.response[i])) {
        for (j in data.response[i]) {
          if (i == "idols") {
            if (!$("#" + map[i] + "_" + j).raw()) {
              AddArtistField();
            }
            $("#" + map[i] + "_" + j).raw().value = data.response[i][j];
          }
          if (map[i] == "tags" && !$("#" + map[i]).raw().value) {
            $("#" + map[i]).raw().value = data.response[i].join(", ");
          }
        }
      }
      if (map[i] && $("#" + map[i]).raw() && !$("#" + map[i]).raw().value) {
        $("#" + map[i]).raw().value = data.response[i];
      }
    }
  });
}

$(function () {
  Categories();
  Calculate();
  document.querySelectorAll("[autofill]").forEach(function (el) {
    el.addEventListener("click", function (event) {
      ({ jav: JavAutofill }[el.attributes["autofill"].value]());
    });
  });
  $(document).on("click", ".add_artist_button", AddArtistField);
  $(document).on("click", ".remove_artist_button", RemoveArtistField);
});
