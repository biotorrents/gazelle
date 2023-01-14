/**
 * user profile javascript
 */

(() => {
  "use strict";

  // toggle passkey
  $("#displayPassKey").hide();
  $("#togglePassKey").on("click", () => {
    $("#displayPassKey").toggle();
  });

  // toggle authkey
  $("#displayAuthKey").hide();
  $("#toggleAuthKey").on("click", () => {
    $("#displayAuthKey").toggle();
  });

  // toggle rss key
  $("#displayRssKey").hide();
  $("#toggleRssKey").on("click", () => {
    $("#displayRssKey").toggle();
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
})();

/** legacy code */

/**
* UncheckIfDisabled
*/
function UncheckIfDisabled(checkbox) {
  if (checkbox.disabled) {
    checkbox.checked = false;
  }
}

/**
* ToggleWarningAdjust
*/
function ToggleWarningAdjust(selector) {
  if (selector.options[selector.selectedIndex].value == "---") {
    $("#ReduceWarningTR").gshow();
    $("#ReduceWarning").raw().disabled = false;
  } else {
    $("#ReduceWarningTR").ghide();
    $("#ReduceWarning").raw().disabled = true;
  }
}
