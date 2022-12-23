/**
 * user settings javascript
 */

(() => {
  "use strict";

  /**
   * create 2fa (totp)
   */
  $("#createTwoFactor").click(() => {
    // the data to send
    var request = {
      secret: $("#twoFactorSecret").val(),
      code: $("#twoFactorCode").val(),
    };

    // sanity checks
    if (
      !request.code ||
      request.code.length !== 6 ||
      Number.isNaN(request.code)
    ) {
      alert("please enter the 6-digit code from your authenticator app");
    }

    // ajax request
    $.post("/api/internal/createTwoFactor", request, (response) => {
      $("#twoFactorResponse").html(response.data);

      if (response.status === "success") {
        $("#twoFactorResponse").removeClass("failure");
        $("#twoFactorResponse").addClass("success");

        $("#twoFactorDisabled").hide();
      }

      if (response.status === "failure") {
        $("#twoFactorResponse").removeClass("success");
        $("#twoFactorResponse").addClass("failure");
      }
    });
  });

  /**
   * delete 2fa (totp)
   */
  $("#deleteTwoFactor").click(() => {
    // the data to send
    var request = {
      secret: $("#twoFactorSecret").val(),
      code: $("#twoFactorCode").val(),
    };

    // sanity checks
    if (
      !request.code ||
      request.code.length !== 6 ||
      Number.isNaN(request.code)
    ) {
      alert("please enter the 6-digit code from your authenticator app");
    }

    // ajax request
    $.post("/api/internal/deleteTwoFactor", request, (response) => {
      $("#twoFactorResponse").html(response.data);

      if (response.status === "success") {
        $("#twoFactorResponse").removeClass("failure");
        $("#twoFactorResponse").addClass("success");

        $("#twoFactorEnabled").hide();
      }

      if (response.status === "failure") {
        $("#twoFactorResponse").removeClass("success");
        $("#twoFactorResponse").addClass("failure");
      }
    });
  });

  /**
   * suggest a passphrase
   */
  $("#createPassphrase").click(() => {
    var request = null;

    // ajax request
    $.post("/api/internal/createPassphrase", request, (response) => {
      $("#suggestedPassphrase").val(response.data);
      $("#suggestedPassphrase").select();
    });
  });

  /**
   * some notifications stuff
   * "i'm sure there is a better way to do this"
   */
  $("#notifications_Inbox_traditional").click(function () {
    $("#notifications_Inbox_popup").prop("checked", false);
  });

  $("#notifications_Inbox_popup").click(function () {
    $("#notifications_Inbox_traditional").prop("checked", false);
  });

  $("#notifications_Torrents_traditional").click(function () {
    $("#notifications_Torrents_popup").prop("checked", false);
  });

  $("#notifications_Torrents_popup").click(function () {
    $("#notifications_Torrents_traditional").prop("checked", false);
  });
})();
