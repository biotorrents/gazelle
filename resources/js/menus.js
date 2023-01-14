/**
 * main menu
 */

(() => {
  "use strict";

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

    /**
     * change the search bar target
     * https://stackoverflow.com/a/16750165
     */
    $("#searchForm").on("change", function () {
      var action = $("#searchWhat").val();
      $("#searchForm").attr("action", action + ".php");
    });
  });
})();
