(() => {
  "use strict";

  /**
   * No cookies
   */
  if ($("#no-cookies")) {
    cookie.set("cookie_test", 1, 1);

    if (cookie.get("cookie_test") != null) {
      cookie.del("cookie_test");
    } else {
      $("#no-cookies").gshow();
    }
  }
})();
