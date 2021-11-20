(() => {
  "use strict";

  /**
   * Categories
   *
   * Toggle category metadata.
   * Displays dynamic selects on upload.php.
   * These change with each category.
   */
  function Categories() {
    let def = [
      "javdb", // Accession Number
      "audio", // Version
      "title", // Torrent Title
      "title_rj", // Organism
      "title_jp", // Strain/Variety
      "artists", // Authors(s)
      "studio", // Department/Lab
      "series", // Location
      "year", // Year
      "codec", // License
      // Platform *changes below*
      "resolution", // Scope *changes below*
      // Format *changes below*
      "archive", // Archive
      "tags", // Tags
      "cover", // Picture
      "mirrors", // Mirrors
      "screenshots", // Publications
      //'seqhash', // Seqhash
      "group_desc", // Torrent Group Description
      "release_desc", // Torrent Description
      "censored", // Aligned/Annotated
      "anon", // Upload Anonymously
    ];

    let cats = [
      {
        // Sequences
        media: {}, // Platform
        container: {}, // Format
        seqhash: {}, // Seqhash
      },
      {
        // Graphs
        media_graphs: {}, // Platform
        container_graphs: {}, // Format
      },
      {
        // Systems
        media_graphs: {}, // Platform
        container_graphs: {}, // Format
      },
      {
        // Geometric
        media_graphs: {}, // Platform
        container_graphs: {}, // Format
      },
      {
        // Scalars/Vectors
        media_scalars_vectors: {}, // Platform
        container_scalars_vectors: {}, // Format
      },
      {
        // Patterns
        media_graphs: {}, // Platform
        container_graphs: {}, // Format
      },
      {
        // Constraints
        media_graphs: {}, // Platform
        container_graphs: {}, // Format
      },
      {
        // Images
        media_images: {}, // Platform
        container_images: {}, // Format
      },
      {
        // Spatial
        media_graphs: {}, // Platform
        container_spatial: {}, // Format
      },
      {
        // Models
        media_graphs: {}, // Platform
        container_spatial: {}, // Format
      },
      {
        // Documents
        media_documents: {}, // Platform
        container_documents: {}, // Format
      },
      {
        // Machine Data
        media_machine_data: {}, // Platform
        container: {}, // Format
      },
    ];

    let active = {};
    for (let field of def) active[field] = {};

    let category = 0;
    if ($('input[name="type"]').raw())
      category = $('input[name="type"]').raw().value;
    if ($("#categories").raw()) category = $("#categories").raw().value;
    active = Object.assign(active, cats[category]);

    let hide = (el) => {
      Array.from(
        $(`#${el.id} input, #${el.id} select, #${el.id} textarea`)
      ).forEach((inp) => (inp.disabled = true));
      $(el).ghide();
    };

    let show = (el) => {
      Array.from(
        $(`#${el.id} input, #${el.id} select, #${el.id} textarea`)
      ).forEach((inp) => (inp.disabled = false));
      $(el).gshow();
    };

    let trs = $("#dynamic_form tr");
    for (let tr of trs) {
      let field = tr.id.slice(0, -3);
      if (active[field]) {
        if (active[field].name) {
          tr.children[0].innerHTML = active[field].name;
        }

        let notes = $(`#${tr.id} p.notes`).raw();
        if (notes) notes.innerHTML = active[field].notes || "";
        show(tr);
      } else {
        hide(tr);
      }
    }
  }

  /**
   * add_tag
   */
  function add_tag() {
    if ($("#tags").raw().value == "") {
      $("#tags").raw().value =
        $("#genre_tags").raw().options[
          $("#genre_tags").raw().selectedIndex
        ].value;
    } else if (
      $("#genre_tags").raw().options[$("#genre_tags").raw().selectedIndex]
        .value == "---"
    ) {
    } else {
      $("#tags").raw().value =
        $("#tags").raw().value +
        ", " +
        $("#genre_tags").raw().options[$("#genre_tags").raw().selectedIndex]
          .value;
    }
  }

  /**
   * AddArtistField
   */
  var ArtistCount = 1;
  function AddArtistField() {
    window.getSelection().removeAllRanges();
    ArtistCount = $('input[name="artists[]"]').length;

    if (ArtistCount >= 200) {
      return;
    }

    var ArtistField = document.createElement("input");
    ArtistField.type = "text";
    ArtistField.id = "artist_" + ArtistCount;
    ArtistField.name = "artists[]";
    ArtistField.size = 45;

    var x = $("#artistfields").raw();
    x.appendChild(document.createElement("br"));
    x.appendChild(ArtistField);
    x.appendChild(document.createTextNode("\n"));

    if ($("#artist_0").data("gazelle-autocomplete")) {
      $(ArtistField).on("focus", function () {
        $(ArtistField).autocomplete({
          serviceUrl: ARTIST_AUTOCOMPLETE_URL,
        });
      });
    }
    ArtistCount++;
  }

  /**
   * RemoveArtistField
   */
  function RemoveArtistField() {
    window.getSelection().removeAllRanges();
    ArtistCount = $('input[name="artists[]"]').length;

    if (ArtistCount == 1) {
      return;
    }

    var x = $("#artistfields").raw();
    for (i = 0; i < 3; i++) {
      x.removeChild(x.lastChild);
    }
    ArtistCount--;
  }

  /**
   * AddScreenshotField
   */
  function AddScreenshotField() {
    var sss = $('[name="screenshots[]"]');
    if (sss.length >= 10) return;

    var ScreenshotField = document.createElement("input");
    ScreenshotField.type = "text";
    ScreenshotField.id = "ss_" + sss.length;
    ScreenshotField.name = "screenshots[]";
    ScreenshotField.size = 45;

    var a = document.createElement("a");
    a.className = "brackets";
    a.innerHTML = "−";
    a.onclick = function () {
      RemoveScreenshotField(this);
    };

    var x = $("#screenshots").raw();
    var y = document.createElement("div");
    y.appendChild(ScreenshotField);
    y.appendChild(document.createTextNode("\n"));
    y.appendChild(a);
    x.appendChild(y);
  }

  /**
   * RemoveScreenshotField
   */
  function RemoveScreenshotField(el) {
    var sss = $('[name="screenshots[]"]');
    el.parentElement.remove();
  }

  /**
   * AnimeAutofill
   */
  function AnimeAutofill() {
    var map = {
      artist: "artist_0",
      title: "title",
      title_rj: "title_rj",
      title_jp: "title_jp",
      year: "year",
      description: "album_desc",
    };

    var aid = $("#anidb").raw().value;
    $.getJSON("/api.php?action=autofill&cat=anime&aid=" + aid, function (data) {
      if (data.status != "success") return;
      for (i in data.response) {
        if (map[i] && !$("#" + map[i]).raw().value) {
          $("#" + map[i]).raw().value = data.response[i];
        }
      }
    });
  }

  /**
   * JavAutofill
   */
  function JavAutofill() {
    var map = {
      cn: "javdb",
      artists: "artists",
      title: "title",
      title_jp: "title_jp",
      year: "year",
      studio: "studio",
      image: "image",
      tags: "tags",
      description: "album_desc",
    };

    var cn = $("#javdb_tr #catalogue").raw().value.toUpperCase();
    $.getJSON("/api.php?action=autofill&cat=jav&cn=" + cn, function (data) {
      if (data.status != "success") {
        $("#catalogue").raw().value = "Failed";
        return;
      } else {
        $("#catalogue").raw().value = data.response.cn;
      }

      for (i in data.response) {
        if (Array.isArray(data.response[i])) {
          for (j in data.response[i]) {
            if (i == "artists") {
              if (!$("#" + map[i] + "_" + j).raw()) {
                AddArtistField();
              }
              $("#" + map[i] + "_" + j).raw().value = data.response[i][j];
            }
            if (map[i] == "tags" && !$("#" + map[i]).raw().value) {
              $("#" + map[i]).raw().value = data.response[i].join(", ");
            }
          }
        }

        if (map[i] && $("#" + map[i]).raw() && !$("#" + map[i]).raw().value) {
          $("#" + map[i]).raw().value = data.response[i];
        }
      }

      if (data.response.screens.length) {
        $("#album_desc").raw().value =
          "[spoiler=Automatically located thumbs][img]" +
          data.response.screens.join("[/img][img]") +
          "[/img][/spoiler]\n\n" +
          $("#album_desc").raw().value;
      }
    });
  }

  /**
   * MangaAutofill
   */
  function MangaAutofill() {
    var map = {
      artists: "artists",
      title: "title",
      title_jp: "title_jp",
      year: "year",
      tags: "tags",
      lang: "lang",
      cover: "image",
      circle: "series",
      pages: "pages",
      description: "release_desc",
    };

    var nh = $("#ehentai_tr #catalogue").raw().value;
    $.getJSON("/api.php?action=autofill&cat=manga&url=" + nh, function (data) {
      if (data.status != "success") {
        $("#catalogue").raw().value = "Failed";
        return;
      }

      for (i in data.response) {
        if (Array.isArray(data.response[i])) {
          for (j in data.response[i]) {
            if (i == "artists") {
              if (!$("#" + map[i] + "_" + j).raw()) {
                AddArtistField();
              }
              $("#" + map[i] + "_" + j).raw().value = data.response[i][j];
            }
            if (map[i] == "tags" && !$("#" + map[i]).raw().value) {
              $("#" + map[i]).raw().value = data.response[i].join(", ");
            }
          }
        }

        if (
          map[i] &&
          $("#" + map[i]).raw() &&
          (!$("#" + map[i]).raw().value || $("#" + map[i]).raw().value == "---")
        ) {
          $("#" + map[i]).raw().value = data.response[i];
        }
      }
    });
  }

  /**
   * SetResolution
   */
  function SetResolution() {
    if ($("#ressel").raw().value != "Other") {
      $("#resolution").raw().value = $("#ressel").raw().value;
      $("#resolution").ghide();
    } else {
      $("#resolution").raw().value = "";
      $("#resolution").gshow();
      $("#resolution").raw().readOnly = false;
    }
  }

  /**
   * initAutofill
   */
  function initAutofill() {
    $("[autofill]").each(function (i, el) {
      el.addEventListener("click", function (event) {
        ({ douj: MangaAutofill, anime: AnimeAutofill, jav: JavAutofill }[
          el.attributes["autofill"].value
        ]());
      });
    });
  }

  /**
   * jQuery
   */
  $(function () {
    Categories();
    initAutofill();
    $(document).on("click", ".add_artist_button", AddArtistField);
    $(document).on("click", ".remove_artist_button", RemoveArtistField);
  });
})();
