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
    $("#bibtexCitation-" + torrentId).toggle(toggleDuration);
  });


  /**
   * bookmarks
   */

  // create bookmark
  $("#createGroupBookmark").on("click", (event) => {
    // the data to send
    var request = {
      userId: $(event.target).data("userid"),
      contentId: $(event.target).data("groupid"),
      contentType: "torrent",
    };

    // ajax request
    $.ajax("/api/internal/createBookmark", {
      method: "POST",
      headers: { "Authorization": "Bearer " + frontendHash },

      contentType: "application/vnd.api+json",
      dataType: "json",

      data: JSON.stringify(request),

      success: (response) => {
        $(event.target).removeClass("button-orange");
        $(event.target).addClass("button-red");
        $(event.target).html("remove bookmark");
        $(event.target).attr("id", "deleteGroupBookmark");
      },

      error: (response) => {
        console.log(response);
      },
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
      userId: $(event.target).data("userid"),
      contentId: $(event.target).data("groupid"),
      contentType: "torrent",
    };

    // ajax request
    if (action === "create") {
      $.ajax("/api/internal/createBookmark", {
        method: "POST",
        headers: { "Authorization": "Bearer " + frontendHash },

        contentType: "application/vnd.api+json",
        dataType: "json",

        data: JSON.stringify(request),

        success: (response) => {
          $(event.target).removeClass("button-orange");
          $(event.target).addClass("button-red");
          $(event.target).html("remove bookmark");
        },

        error: (response) => {
          console.log(response);
        },
      });
    }

    if (action === "delete") {
      $.ajax("/api/internal/deleteBookmark", {
        method: "POST",
        headers: { "Authorization": "Bearer " + frontendHash },

        contentType: "application/vnd.api+json",
        dataType: "json",

        data: JSON.stringify(request),

        success: (response) => {
          $(event.target).removeClass("button-red");
          $(event.target).addClass("button-orange");
          $(event.target).html("add bookmark");
        },

        error: (response) => {
          console.log(response);
        },
      });
    }
  });
})();
