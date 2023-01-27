/**
 * torrent details page
 */
(() => {
  "use strict";

  // click to copy permalink
  $(".permalink").on("click", (element) => {
    let text = $(element.target).data("permalink");
    navigator.clipboard.writeText(text);
  });

  // confirm freeleech use
  $(".useFreeleechToken").on("click", () => {
    return confirm("Are you sure you want to use a freeleech token here?");
  });

  // toggle biblatex citation
  $(".bibtexCitation").hide();
  $(".toggleBibtex").on("click", (event) => {
    let torrentId = $(event.target).data("torrentid");
    $("#bibtexCitation-" + torrentId).toggle();
  });

  // create bookmark
  $("#createGroupBookmark").on("click", (event) => {
    // the data to send
    var request = {
      frontendHash: frontendHash,
      userId: $(event.target).data("userid"),
      contentId: $(event.target).data("groupid"),
      contentType: "torrent",
    };

    // ajax request
    $.post("/api/internal/createBookmark", request, (response) => {
      if (response.status === "success") {
        $(event.target).removeClass("button-orange");
        $(event.target).addClass("button-red");
        $(event.target).html("remove bookmark");
        $(event.target).attr("id", "deleteGroupBookmark");
      }

      if (response.status === "failure") {
        console.log(response);
      }
    });
  });

  // toggle bookmark
  $("#toggleGroupBookmark").on("click", (event) => {
    // determine action
    if ($(event.target).html() === "add bookmark") {
      var action = "create";
    }

    if ($(event.target).html() === "remove bookmark") {
      var action = "delete";
    }

    // the data to send
    var request = {
      frontendHash: frontendHash,
      userId: $(event.target).data("userid"),
      contentId: $(event.target).data("groupid"),
      contentType: "torrent",
    };

    // ajax request
    if (action === "create") {
      $.post("/api/internal/createBookmark", request, (response) => {
        if (response.status === "success") {
          $(event.target).removeClass("button-orange");
          $(event.target).addClass("button-red");
          $(event.target).html("remove bookmark");
        }

        if (response.status === "failure") {
          console.log(response);
        }
      });
    }

    if (action === "delete") {
      $.post("/api/internal/deleteBookmark", request, (response) => {
        if (response.status === "success") {
          $(event.target).removeClass("button-red");
          $(event.target).addClass("button-orange");
          $(event.target).html("add bookmark");
        }

        if (response.status === "failure") {
          console.log(response);
        }
      });
    }
  });


  /*
      // create bookmark
  $("#createGroupBookmark").on("click", (event) => {
      // the data to send
      var request = {
          frontendHash: frontendHash,
          userId: $(event.target).data("userid"),
          contentId: $(event.target).data("groupid"),
          contentType: "torrent",
      };

      // ajax request
      $.post("/api/internal/createBookmark", request, (response) => {
          if (response.status === "success") {
              $(event.target).removeClass("button-orange");
              $(event.target).addClass("button-red");
              $(event.target).html("remove bookmark");
              $(event.target).attr("id", "deleteGroupBookmark");
          }

          if (response.status === "failure") {
              console.log(response);
          }
      });
  });

  // delete bookmark
  $("#deleteGroupBookmark").on("click", (event) => {
      // the data to send
      var request = {
          frontendHash: frontendHash,
          userId: $(event.target).data("userid"),
          contentId: $(event.target).data("groupid"),
          contentType: "torrent",
      };

      // ajax request
      $.post("/api/internal/deleteBookmark", request, (response) => {
          if (response.status === "success") {
              $(event.target).removeClass("button-red");
              $(event.target).addClass("button-orange");
              $(event.target).html("add bookmark");
              $(event.target).attr("id", "createGroupBookmark");
          }

          if (response.status === "failure") {
              console.log(response);
          }
      });
  });
*/
})();

/**
* ArtistManager
*/
function ArtistManager() {
  var GroupID = window.location.search.match(/[?&]id=(\d+)/);
  if (typeof GroupID == "undefined") {
    return;
  } else {
    GroupID = GroupID[1];
  }
  var ArtistList;
  if (!(ArtistList = $("#artist_list").raw())) {
    return false;
  } else if ($("#artistmanager").raw()) {
    $("#artistmanager").gtoggle();
    $("#artist_list").gtoggle();
  } else {
    MainArtistCount = 0;
    var elArtistManager = document.createElement("div");
    elArtistManager.id = "artistmanager";

    var elArtistList = ArtistList.cloneNode(true);
    elArtistList.id = "artistmanager_list";
    for (var i = 0; i < elArtistList.children.length; i++) {
      if (elArtistList.children[i].children[0].tagName.toUpperCase() == "A") {
        var ArtistID =
          elArtistList.children[i].children[0].href.match(/[?&]id=(\d+)/)[1];
        var elBox = document.createElement("input");
        elBox.type = "checkbox";
        elBox.name = "artistmanager_box";
        elBox.value = ArtistID;
        elBox.onclick = function (e) {
          SelectArtist(e, this);
        };
        elArtistList.children[i].insertBefore(
          elBox,
          elArtistList.children[i].children[0]
        );
        elArtistList.children[i].insertBefore(
          document.createTextNode(" "),
          elArtistList.children[i].children[1]
        );
        MainArtistCount++;
      }
    }
    elArtistManager.appendChild(elArtistList);

    var elArtistForm = document.createElement("form");
    elArtistForm.id = "artistmanager_form";
    elArtistForm.method = "post";
    var elGroupID = document.createElement("input");
    elGroupID.type = "hidden";
    elGroupID.name = "groupid";
    elGroupID.value = GroupID;
    elArtistForm.appendChild(elGroupID);
    var elAction = document.createElement("input");
    elAction.type = "hidden";
    elAction.name = "manager_action";
    elAction.id = "manager_action";
    elAction.value = "manage";
    elArtistForm.appendChild(elAction);
    var elAction = document.createElement("input");
    elAction.type = "hidden";
    elAction.name = "action";
    elAction.value = "manage_artists";
    elArtistForm.appendChild(elAction);
    var elAuth = document.createElement("input");
    elAuth.type = "hidden";
    elAuth.name = "auth";
    elAuth.value = authkey;
    elArtistForm.appendChild(elAuth);
    var elSelection = document.createElement("input");
    elSelection.type = "hidden";
    elSelection.id = "artists_selection";
    elSelection.name = "artists";
    elArtistForm.appendChild(elSelection);

    var elSubmitDiv = document.createElement("div");
    elSubmitDiv.appendChild(document.createTextNode(" "));

    elSubmitDiv.className = "body";
    var elDelButton = document.createElement("input");
    elDelButton.type = "button";
    elDelButton.value = "Delete";
    elDelButton.onclick = ArtistManagerDelete;
    elSubmitDiv.appendChild(elDelButton);

    elArtistForm.appendChild(elSubmitDiv);
    elArtistManager.appendChild(elArtistForm);
    ArtistList.parentNode.appendChild(elArtistManager);
    $("#artist_list").ghide();
  }
}

/**
* SelectArtist
*/
function SelectArtist(e, obj) {
  if (window.event) {
    e = window.event;
  }
  EndBox = Number(obj.id.substr(17));
  if (!e.shiftKey || typeof StartBox == "undefined") {
    StartBox = Number(obj.id.substr(17));
  }
  Dir = EndBox > StartBox ? 1 : -1;
  var checked = obj.checked;
  for (var i = StartBox; i != EndBox; i += Dir) {
    var key,
      importance = obj.value.substr(0, 1),
      id = obj.value.substr(2);
    $("#artistmanager_box" + i).raw().checked = checked;
  }
  StartBox = Number(obj.id.substr(17));
}

/**
* ArtistManagerSubmit
*/
function ArtistManagerSubmit() {
  var Selection = new Array();
  var MainSelectionCount = 0;
  for (var i = 0, boxes = $('[name="artistmanager_box"]'); boxes.raw(i); i++) {
    if (boxes.raw(i).checked) {
      Selection.push(boxes.raw(i).value);
      if (boxes.raw(i).value.substr(0, 1) == "1") {
        MainSelectionCount++;
      }
    }
  }
  if (
    Selection.length == 0 ||
    ($("#manager_action").raw().value == "delete" &&
      !confirm(
        "Are you sure you want to delete " +
        Selection.length +
        " artists from this group?"
      ))
  ) {
    return;
  }
  $("#artists_selection").raw().value = Selection.join(",");
  if (
    (($("#artists_importance").raw().value != 1 &&
      $("#artists_importance").raw().value != 4 &&
      $("#artists_importance").raw().value != 6) ||
      $("#manager_action").raw().value == "delete") &&
    MainSelectionCount == MainArtistCount
  ) {
    if (!$(".error_message").raw()) {
      save_message(
        "All groups need to have at least one main artist, composer, or DJ.",
        true
      );
    }
    $(".error_message").raw().scrollIntoView();
    return;
  }
  $("#artistmanager_form").raw().submit();
}

/**
* ArtistManagerDelete
*/
function ArtistManagerDelete() {
  $("#manager_action").raw().value = "delete";
  ArtistManagerSubmit();
  $("#manager_action").raw().value = "manage";
}

/**
* Vote
*/
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
