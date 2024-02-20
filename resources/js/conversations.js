/**
 * conversations javascript
 */

(() => {
  "use strict";

  /**
   * replyToMessage
   * 
   * Sets the value of replyToId in the post form.
   */

  $(".replyToMessage").on("click", () => {
    let replyToId = $(event.target).data("messageid");
    $("#replyToId").val(replyToId);
  });


  /**
   * quoteMessage
   * 
   * Grabs the selected text and sets the value of the quote in the post form.
   * This is very similar to 4chan's quick reply box functionality.
   */
  $(".quoteMessage").on("click", () => {
    // get the editor id to use as a literal
    let editorId = "replyEditor_" + $(event.target).data("conversationid");

    // get the selected text and split it into an array of lines
    let quoteArray = window.getSelection().toString().split("\n");

    // add markdown syntax
    let quoteBody = "";
    quoteArray.forEach((line) => {
      quoteBody += ">" + line + "\n";
    });

    // set the value of the editor
    window[editorId].value(quoteBody);
  });


  /**
   * likeMessage
   */

  $(".likeMessage").on("click", (event) => {
    // get the span element
    var likeElement = "likeCount-" + $(event.target).data("messageid");

    // the data to send
    var request = {
      messageId: $(event.target).data("messageid"),
      userId: $(event.target).data("userid"),
    };

    // ajax request
    $.ajax("/api/internal/likeMessage", {
      method: "POST",
      headers: { "Authorization": "Bearer " + frontendHash },

      contentType: "application/vnd.api+json",
      dataType: "json",

      data: JSON.stringify(request),

      success: (response) => {
        $("#" + likeElement).html(response.data);
      },

      error: (response) => {
        console.log(response);
      },
    });
  });


  /**
   * dislikeMessage
   */

  $(".dislikeMessage").on("click", (event) => {
    // get the span element
    var dislikeElement = "dislikeCount-" + $(event.target).data("messageid");

    // the data to send
    var request = {
      messageId: $(event.target).data("messageid"),
      userId: $(event.target).data("userid"),
    };

    // ajax request
    $.ajax("/api/internal/dislikeMessage", {
      method: "POST",
      headers: { "Authorization": "Bearer " + frontendHash },

      contentType: "application/vnd.api+json",
      dataType: "json",

      data: JSON.stringify(request),

      success: (response) => {
        console.log(response, dislikeElement);
        $("#" + dislikeElement).html(response.data);
      },

      error: (response) => {
        console.log(response);
      },
    });
  });


})();
