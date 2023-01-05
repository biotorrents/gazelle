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
