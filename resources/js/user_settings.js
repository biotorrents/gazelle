/**
 * user settings javascript
 */

// 2fa (totp)
(() => {
  $("#verifyTwoFactor").click(() => {
    // the data to send
    var request = {
      secret: $("#twoFactorSecret").val(),
      code: $("#twoFactorCode").val(),
    };

    // sanity check
    if (!request.code || request.code.length !== 6) {
      alert("please enter the 6-digit code from your authenticator app");
    }

    // ajax request
    $.post("/api/internal/verifyTwoFactor", request, function (response) {
      $("#twoFactorResponse").html(response.data);

      if (response.status === "success") {
        $("#twoFactorResponse").addClass("success");
      }

      if (response.status === "failure") {
        $("#twoFactorResponse").addClass("failure");
      }
    });
  });
})();

// some notifications thing
(() => {
  // I'm sure there is a better way to do this but this will do for now.
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
