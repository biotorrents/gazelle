/**
 * user profile javascript
 */

(() => {
  "use strict";

  // toggle passkey
  $("#displayPassKey").hide();
  $("#togglePassKey").click(() => {
    $("#displayPassKey").toggle();
  });

  // toggle authkey
  $("#displayAuthKey").hide();
  $("#toggleAuthKey").click(() => {
    $("#displayAuthKey").toggle();
  });

  // toggle rss key
  $("#displayRssKey").hide();
  $("#toggleRssKey").click(() => {
    $("#displayRssKey").toggle();
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
