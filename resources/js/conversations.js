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
   * reactToMessage 
   */

  $(".reactToMessage").on("click", (event) => {
    // get the reaction
    var reaction = $(event.target).data("reaction");

    // get the elements needed to update
    var containerSelector = "#" + reaction + "Container-" + $(event.target).data("messageid");
    var countSelector = "#" + reaction + "Count-" + $(event.target).data("messageid");

    // the data to send
    var request = {
      reaction: reaction,
      messageId: $(event.target).data("messageid"),
      userId: $(event.target).data("userid"),
    };

    // ajax request
    $.ajax("/api/internal/reactToMessage", {
      method: "POST",
      headers: { "Authorization": "Bearer " + frontendHash },

      contentType: "application/vnd.api+json",
      dataType: "json",

      data: JSON.stringify(request),

      success: (response) => {
        // add or remove the hasReacted class
        if (response.data.hasUserReacted) {
          $(containerSelector).addClass("hasReacted");
        } else {
          $(containerSelector).removeClass("hasReacted");

        }

        // update the count, remembering the response is the *total* count
        $(countSelector).html(response.data.totalCount);
      },

      error: (response) => {
        console.log(response);
      },
    });
  });

})();
