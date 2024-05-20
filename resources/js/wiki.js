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
    $("#createUpdateWikiArticle").html("save your edits");
  };

  // add the event listener
  window.addEventListener("message", handleMessage);


  /**
   * createUpdateWikiArticle
   */

  $("#createUpdateWikiArticle").on("click", (event) => {
    // the data to send
    var request = {
      articleId: $(event.target).data("articleid"),
      title: $("#articleTitle").val(),
      body: window.notebookContent,
      minClassRead: $("#minClassRead").val(),
      minClassEdit: $("#minClassEdit").val(),
      authorId: $(event.target).data("authorid"),
    };

    // remove the body if it's empty
    if (!request.body) {
      delete request.body;
    }

    // ajax request
    $.ajax("/api/internal/createUpdateWikiArticle", {
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
    // the data to send
    var request = {
      articleId: $("#newAliasContent").data("articleid"),
      alias: $("#newAliasContent").val(),
    };

    // ajax request
    $.ajax("/api/internal/createWikiAlias", {
      method: "POST",
      headers: { "Authorization": "Bearer " + frontendHash },

      contentType: "application/vnd.api+json",
      dataType: "json",

      data: JSON.stringify(request),

      success: (response) => {
        // kinda lame, should match /templates/wiki/sidebar.twig
        $("#wikiAliases").append("<tr><th>" + request.alias + "</th><td class='right'><a class='button deleteWikiAlias' data-articleid='" + request.articleId + "' data-alias='" + request.alias + "'>delete</a></td></tr>");
      },

      error: (response) => {
        console.log(response);
      },
    });
  });


  /**
   * deleteWikiAlias
   */
  $(".deleteWikiAlias").on("click", (event) => {
    // the data to send
    var request = {
      articleId: $(event.target).data("articleid"),
      alias: $(event.target).data("alias"),
    };

    // ajax request
    $.ajax("/api/internal/deleteWikiAlias", {
      method: "POST",
      headers: { "Authorization": "Bearer " + frontendHash },

      contentType: "application/vnd.api+json",
      dataType: "json",

      data: JSON.stringify(request),

      success: (response) => {
        $(event.target).closest("tr").hide();
      },

      error: (response) => {
        console.log(response);
      },
    });
  });

})();
