/**
 * user settings javascript
 */

(() => {
  "use strict";

  /**
   * site appearance filter previews
   */

  // font
  $("#font").change(() => {
    let fontSelection = $("#font").children("option:selected").val();
    let fontClasses = [
      "notoSans",
      "notoSerif",
      "luxiSans",
      "luxiSerif",
      "openDyslexic",
      "comicNeue",
    ];

    $("body").removeClass(fontClasses);
    $("body").addClass(fontSelection);
  });

  // desaturate
  $("#desaturate").click(() => {
    let modeSelection = $("#desaturate").prop("checked");
    let modeClasses = ["desaturate", "darkMode"];

    if (modeSelection) {
      $("#darkMode").prop("checked", false);
      $("body").removeClass(modeClasses);
      $("body").addClass("desaturate");
    } else {
      $("body").removeClass(modeClasses);
    }
  });

  // dark mode
  $("#darkMode").click(() => {
    let modeSelection = $("#darkMode").prop("checked");
    let modeClasses = ["desaturate", "darkMode"];

    if (modeSelection) {
      $("#desaturate").prop("checked", false);
      $("body").removeClass(modeClasses);
      $("body").addClass("darkMode");
    } else {
      $("body").removeClass(modeClasses);
    }
  });

  /**
   * create 2fa (totp)
   */
  $("#twoFactorResponse").hide();
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
      $("#twoFactorResponse").show();
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
  $("#twoFactorResponse").hide();
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
   * hide everything but selected
   */
  let allSettingsSections = [
    "siteAppearanceSettings",
    "torrentSettings",
    "communitySettings",
    "notificationSettings",
    "profileSettings",
    "securitySettings",
  ];

  allSettingsSections.forEach(() => {
    /**
     * todo:
     * doing a forEach loop is probably stupid,
     * but i need a way to call $("#foo").hide()
     * on all the shit that's not selected,
     * and apply "button-primary" to that which is selected,
     * then i get mad salesforce-tier ui points,
     * maybe
     */
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
