/**
 * How to Build a Responsive Navigation Bar With Flexbox
 * https://webdesign.tutsplus.com/tutorials/how-to-build-a-responsive-navigation-bar-with-flexbox--cms-33535
 */

(() => {
  "use strict";

  /**
   * 5. Add the Toggle Functionality with JavaScript
   */

  const toggle = document.querySelector(".toggle");
  const menu = document.querySelector(".menu");

  // Toggle mobile menu
  function toggleMenu() {
    if (menu.classList.contains("active")) {
      menu.classList.remove("active");

      // Adds the menu (hamburger) icon
      toggle.querySelector("a").innerHTML = "<i class='fal fa-bars'></i>";
    } else {
      menu.classList.add("active");

      // Adds the close (x) icon
      toggle.querySelector("a").innerHTML = "<i class='fal fa-times'></i>";
    }
  }

  // Event listener
  toggle.addEventListener("click", toggleMenu, false);

  /**
   * 6. Add the Dropdown Functionality with JavaScript
   */

  const items = document.querySelectorAll(".item");

  // Activate submenu
  function toggleItem() {
    if (this.classList.contains("submenu-active")) {
      this.classList.remove("submenu-active");
    } else if (menu.querySelector(".submenu-active")) {
      menu.querySelector(".submenu-active").classList.remove("submenu-active");
      this.classList.add("submenu-active");
    } else {
      this.classList.add("submenu-active");
    }
  }

  // Event listeners
  for (let item of items) {
    if (item.querySelector(".submenu")) {
      item.addEventListener("click", toggleItem, false);
      item.addEventListener("keypress", toggleItem, false);
    }
  }

  /**
   * 9. Let Users Close the Submenu By Clicking Anywhere on the Page
   */

  // Close submenu from anywhere
  function closeSubmenu(e) {
    let isClickInside = menu.contains(e.target);

    if (!isClickInside && menu.querySelector(".submenu-active")) {
      menu.querySelector(".submenu-active").classList.remove("submenu-active");
    }
  }

  // Event listener
  document.addEventListener("click", closeSubmenu, false);

  /**
   * Change the search bar target
   * https://stackoverflow.com/a/16750165
   */
  $("#select_search").change(function () {
    var action = $("#search_what").val();
    $("#select_search").attr("action", action + ".php");
  });
})();
