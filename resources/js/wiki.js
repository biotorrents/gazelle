/**
 * wiki
 */

(() => {
  "use strict";

  /**
   * listen for starboard notebook events
   * 
   * @see https://www.teamsimmer.com/2023/05/02/how-do-i-use-the-postmessage-method-with-cross-site-iframes/
   */

  // only allow the uri set in the config
  let allowedOrigins = ["https://" + env.starboardDomain];

  // define the event listener function
  let handleMessage = function (event) {
    // abort if request doesn't come from a valid origin
    if (!allowedOrigins.includes(event.origin)) {
      return;
    }

    // save the last update to a global variable
    if (event.data.type === "NOTEBOOK_CONTENT_UPDATE") {
      window.notebookContent = event.data.payload.content;
    }

    // reset the edit button content
    $("#updateWikiArticle").html("save your edits");
  };

  // add the event listener
  window.addEventListener("message", handleMessage);


  /**
   * updateWikiArticle
   */

  $("#updateWikiArticle").on("click", (event) => {
    // the data to send
    var request = {
      id: $(event.target).data("articleid"),
      title: $("#articleTitle").val(),
      body: window.notebookContent,
      minClassRead: $("#minClassRead").val(),
      minClassEdit: $("#minClassEdit").val(),
    };

    // remove the body if it's empty
    if (!request.body) {
      delete request.body;
    }

    // ajax request
    $.ajax("/api/internal/updateWikiArticle", {
      method: "POST",
      headers: { "Authorization": "Bearer " + frontendHash },

      contentType: "application/vnd.api+json",
      dataType: "json",

      data: JSON.stringify(request),

      success: (response) => {
        $(event.target).html("success! please resume editing");
      },

      error: (response) => {
        console.log(response);
      },
    });
  });


  /**
   * createWikiAlias
   */
  $("#createWikiAlias").on("click", (event) => {
    // todo
  });


  /**
   * deleteWikiAlias
   */
  $("#deleteWikiAlias").on("click", (event) => {
    // todo
  });

})();
