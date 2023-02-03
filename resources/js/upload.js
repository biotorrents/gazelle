(() => {
    "use strict";

    /**
     * change fields on category select
     */

    // trigger categoryId change on load
    $(() => {
        $("#categoryId").trigger("change", (event) => {
            $(event.target).val(1);
        });
    });

    // update select elements on category change
    $("#categoryId").on("change", (event) => {
        // category { id: name } from env
        let categories = [];

        // changing selectors
        let formats = [];
        let platforms = [];
        let scopes = [];

        Object.values(env.CATS).forEach(element => {
            categories.push(
                { [element.ID]: _.camelCase(element.Name) }
            );

            formats.push("#" + _.camelCase(element.Name) + "Format");
            platforms.push("#" + _.camelCase(element.Name) + "Platform");
            scopes.push("#" + _.camelCase(element.Name) + "Scope");
        });

        // hide all but selected
        let categoryId = $(event.target).val();

        // formats
        formats.forEach((element, index) => {
            let categoryIndex = categoryId - 1;
            if (categoryIndex !== index) {
                $(element).hide();
            } else {
                $(element).show();
            }
        });

        // platforms
        platforms.forEach((element, index) => {
            let categoryIndex = categoryId - 1;
            if (categoryIndex !== index) {
                $(element).hide();
            } else {
                $(element).show();
            }
        });

        // scopes
        scopes.forEach((element, index) => {
            let categoryIndex = categoryId - 1;
            if (categoryIndex !== index) {
                $(element).hide();
            } else {
                $(element).show();
            }
        });

        // hardcoded seqhash handling
        if (categoryId === "1") {
            $("#seqhashRow").show();
        } else {
            $("#seqhashRow").hide();
        }

        // display the correct category description
        $("#categoryDescription").html(env.CATS[categoryId].Description);
    });


    /**
     * autofill by doi number
     */

    $("#doiNumberAutofill").on("click", () => {
        // the data to send
        var request = {
            frontendHash: frontendHash,
            paperId: $("#doiNumberInput").val(),
        };

        // sanity check
        if (!request.paperId || request.paperId.length === 0) {
            return false;
        }

        // ajax request
        $.post("/api/internal/doiNumberAutofill", request, (response) => {
            if (response.status === "success") {
                $("#title").val(response.data.title);
                $("#groupDescription").html(response.data.groupDescription);
                $("#groupDescription").trigger("change", () => { });
                $("#year").val(response.data.year);
                $("#literature").val(response.data.literature.join("\n"));
                $("#creatorList").val(response.data.creatorList.join("\n"));
                $("#workgroup").val(response.data.workgroup);
            }

            if (response.status === "failure") {
                // do something
            }
        });
    });


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


})();
