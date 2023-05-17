/**
 * user javascript
 */

(() => {
  "use strict";

  // toggle passkey
  $("#displayPassKey").hide();
  $("#togglePassKey").on("click", () => {
    $("#displayPassKey").toggle(toggleDuration);
  });

  // toggle authkey
  $("#displayAuthKey").hide();
  $("#toggleAuthKey").on("click", () => {
    $("#displayAuthKey").toggle(toggleDuration);
  });

  // toggle rss key
  $("#displayRssKey").hide();
  $("#toggleRssKey").on("click", () => {
    $("#displayRssKey").toggle(toggleDuration);
  });


  /**
   * login/register
   */

  // bcrypt length warning
  $("#passphraseWarning").hide();
  $("#passphrase").on("keyup", function () {
    let passphraseLength = $("#passphrase").val().length;
    if (passphraseLength > 72) {
      $("#passphraseWarning").show();
      $("#passphraseWarning").html("Your passphrase exceeds 72 characters. While we impose no upper length limit, please be aware that bcrypt will only use the first 72 bytes.");
    } else {
      $("#passphraseWarning").hide();
    }
  });

  // passphrases match confirmation
  $("#confirmPassphrase").on("keyup", function () {
    let passphrase = $("#passphrase").val();
    let confirmPassphrase = $("#confirmPassphrase").val();
    let confirmPassphraseLength = confirmPassphrase.length;

    if (confirmPassphraseLength > 0) {
      // no match
      if (passphrase !== confirmPassphrase) {
        $("#passphrase").addClass("errorBorder");
        $("#passphrase").removeClass("infoBorder");

        $("#confirmPassphrase").addClass("errorBorder");
        $("#confirmPassphrase").removeClass("infoBorder");
      }

      // yes match
      else {
        $("#passphrase").addClass("infoBorder");
        $("#passphrase").removeClass("errorBorder");

        $("#confirmPassphrase").addClass("infoBorder");
        $("#confirmPassphrase").removeClass("errorBorder");
      }
    }
  });


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
    // reset response
    $("#twoFactorResponse").hide();
    $("#twoFactorResponse").html("");

    // the data to send
    var request = {
      secret: $("#twoFactorSecret").val(),
      code: $("#twoFactorCode").val(),
    };

    // sanity checks
    if (!request.code || request.code.length !== 6 || Number.isNaN(request.code)) {
      $("#twoFactorResponse").removeClass("success");
      $("#twoFactorResponse").addClass("failure");

      $("#twoFactorResponse").show();
      $("#twoFactorResponse").html("Please enter the 6-digit code from your authenticator app");

      return;
    }

    // ajax request
    $.ajax("/api/internal/createTwoFactor", {
      method: "POST",
      headers: { "Authorization": "Bearer " + frontendHash },

      contentType: "application/json",
      dataType: "json",

      data: JSON.stringify(request),

      success: (response) => {
        $("#twoFactorResponse").show();
        $("#twoFactorResponse").html(response.data);

        $("#twoFactorResponse").removeClass("failure");
        $("#twoFactorResponse").addClass("success");

        $("#twoFactorDisabled").hide();
      },

      error: (response) => {
        $("#twoFactorResponse").show();
        $("#twoFactorResponse").html(response.data);

        $("#twoFactorResponse").removeClass("success");
        $("#twoFactorResponse").addClass("failure");
      },
    });
  });


  /**
   * delete 2fa (totp)
   */

  $("#twoFactorResponse").hide();
  $("#deleteTwoFactor").on("click", () => {
    // reset response
    $("#twoFactorResponse").hide();
    $("#twoFactorResponse").html("");

    // the data to send
    var request = {
      secret: $("#twoFactorSecret").val(),
      code: $("#twoFactorCode").val(),
    };

    // sanity checks
    if (!request.code || request.code.length !== 6 || Number.isNaN(request.code)) {
      $("#twoFactorResponse").removeClass("success");
      $("#twoFactorResponse").addClass("failure");

      $("#twoFactorResponse").show();
      $("#twoFactorResponse").html("Please enter the 6-digit code from your authenticator app");

      return;
    }

    // ajax request
    $.ajax("/api/internal/deleteTwoFactor", {
      method: "POST",
      headers: { "Authorization": "Bearer " + frontendHash },

      contentType: "application/json",
      dataType: "json",

      data: JSON.stringify(request),

      success: (response) => {
        $("#twoFactorResponse").show();
        $("#twoFactorResponse").html(response.data);

        $("#twoFactorResponse").removeClass("failure");
        $("#twoFactorResponse").addClass("success");

        $("#twoFactorDisabled").hide();
      },

      error: (response) => {
        $("#twoFactorResponse").show();
        $("#twoFactorResponse").html(response.data);

        $("#twoFactorResponse").removeClass("success");
        $("#twoFactorResponse").addClass("failure");
      },
    });
  });


  /**
   * delete webauthn
   */

  $("#webAuthnResponse").hide();
  $(".deleteWebAuthn").on("click", (event) => {
    // reset response
    $("#twoFactorResponse").hide();
    $("#twoFactorResponse").html("");

    // confirm deletion
    let isConfirmed = confirm("Are you sure you want to unenroll this WebAuthn device?")
    if (!isConfirmed) {
      return;
    }

    // the data to send
    var request = {
      credentialId: $(event.target).data("credentialid"),
    };

    // ajax request
    $.ajax("/api/internal/webAuthn/delete", {
      method: "POST",
      headers: { "Authorization": "Bearer " + frontendHash },

      contentType: "application/json",
      dataType: "json",

      data: JSON.stringify(request),

      success: (response) => {
        $("#credentialId-" + request.credentialId).hide();
      },

      error: (response) => {
        $("#webAuthnResponse").show();
        $("#webAuthnResponse").html(response.data);

        $("#webAuthnResponse").removeClass("success");
        $("#webAuthnResponse").addClass("failure");
      },
    });
  });


  /**
   * suggest a passphrase
   */

  $("#createPassphrase").on("click", () => {
    // ajax request
    $.ajax("/api/internal/createPassphrase", {
      method: "GET",
      headers: { "Authorization": "Bearer " + frontendHash },

      contentType: "application/json",
      dataType: "json",

      //data: JSON.stringify(request),

      success: (response) => {
        $("#suggestedPassphrase").val(response.data);
      },

      error: (response) => {
        $("#suggestedPassphrase").val(response.data);
      },
    });
  });


  /**
   * createBearerToken
   */

  $("#createBearerToken").on("click", () => {
    // the data to send
    var request = {
      tokenName: $("#tokenName").val(),
    };

    // ajax request
    $.ajax("/api/internal/createBearerToken", {
      method: "POST",
      headers: { "Authorization": "Bearer " + frontendHash },

      contentType: "application/json",
      dataType: "json",

      data: JSON.stringify(request),

      success: (response) => {
        $("#newTokenMessage").html(response.data);
      },

      error: (response) => {
        $("#newTokenMessage").html(response.data);
      },
    });
  });


  /**
   * deleteBearerToken
   */

  $(".deleteBearerToken").on("click", (event) => {
    // confirm deletion
    let isConfirmed = confirm("Are you sure you want to delete this token?")
    if (!isConfirmed) {
      return;
    }

    // the data to send
    var request = {
      tokenId: $(event.target).data("tokenid"),
    };

    // ajax request
    $.ajax("/api/internal/deleteBearerToken", {
      method: "POST",
      headers: { "Authorization": "Bearer " + frontendHash },

      contentType: "application/json",
      dataType: "json",

      data: JSON.stringify(request),

      success: (response) => {
        $("#tokenId-" + request.tokenId).hide();
      },

      error: (response) => {
        console.log(response);
      },
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

})();
