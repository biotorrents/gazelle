/**
 * user settings javascript
 */

(() => {
  "use strict";

  /**
   * site appearance filter previews
   */

  // font
  $("#font").on("change", () => {
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

  // calm mode
  $("#calmMode").on("click", () => {
    let modeSelection = $("#calmMode").prop("checked");
    let modeClasses = ["calmMode", "darkMode"];

    if (modeSelection) {
      $("#darkMode").prop("checked", false);
      $("body").removeClass(modeClasses);
      $("body").addClass("calmMode");
    } else {
      $("body").removeClass(modeClasses);
    }
  });

  // dark mode
  $("#darkMode").on("click", () => {
    let modeSelection = $("#darkMode").prop("checked");
    let modeClasses = ["calmMode", "darkMode"];

    if (modeSelection) {
      $("#calmMode").prop("checked", false);
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
  $("#createTwoFactor").on("click", () => {
    // the data to send
    var request = {
      frontendHash: frontendHash,
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
      return;
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
  $("#deleteTwoFactor").on("click", () => {
    // the data to send
    var request = {
      frontendHash: frontendHash,
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
      return;
    }

    // ajax request
    $.post("/api/internal/deleteTwoFactor", request, (response) => {
      $("#twoFactorResponse").show();
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
  $("#createPassphrase").on("click", () => {
    // the data to send
    var request = {
      frontendHash: frontendHash,
    };

    // ajax request
    $.post("/api/internal/createPassphrase", request, (response) => {
      $("#suggestedPassphrase").val(response.data);
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
   * createBearerToken
   */
  $("#createBearerToken").on("click", () => {
    // the data to send
    var request = {
      frontendHash: frontendHash,
      tokenName: $("#tokenName").val(),
    };

    // ajax request
    $.post("/api/internal/createBearerToken", request, (response) => {
      $("#newTokenMessage").html(response.data);
    });
  });

  /**
   * deleteBearerToken
   */
  $(".deleteBearerToken").on("click", (event) => {
    // confirm deletion
    if (!confirm("Are you sure you want to delete this token?")) {
      return;
    }

    // the data to send
    var request = {
      frontendHash: frontendHash,
      tokenId: $(event.target).data("tokenid"),
    };

    // ajax request
    $.post("/api/internal/deleteBearerToken", request, (response) => {
      if (response.status === "success") {
        $("#tokenId-" + request.tokenId).hide();
      }

      if (response.status === "failure") {
        // todo: communicate the failure somehow
      }
    });
  });

})();
