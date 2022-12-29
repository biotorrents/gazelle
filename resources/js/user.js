/**
 * user profile javascript
 */

(() => {
  "use strict";
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

document.addEventListener("DOMContentLoaded", ToggleIdenticons);

/**
 * ToggleIdenticons
 */
function ToggleIdenticons() {
  var disableAvatars = $("#disableavatars");
  if (disableAvatars.length) {
    var selected = disableAvatars[0].selectedIndex;
    if (selected == 2 || selected == 3) {
      $("#identicons").gshow();
    } else {
      $("#identicons").ghide();
    }
  }
}

/**
 * userform_submit
 */
function userform_submit() {
  if ($("#resetpasskey").is(":checked")) {
    if (!confirm("Are you sure you want to reset your passkey?")) {
      return false;
    }
  }
  return formVal();
}

/**
 * togglePassKey
 */
function togglePassKey(key) {
  if ($("#passkey").raw().innerHTML == "View") {
    $("#passkey").raw().innerHTML = key;
  } else {
    $("#passkey").raw().innerHTML = "View";
  }
}
