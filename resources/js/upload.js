(() => {
    "use strict";

    /**
     * change fields on category select
     */

    $(() => {
        // trigger categoryId change on load
        $("#categoryId").trigger("change", (event) => {
            $(event.target).val(1);
        });

        // hide ajax spinner
        $("#autofillLoader").hide();
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
                $(element + " select:first").prop("disabled", true);
                $(element).hide();
            } else {
                $(element + " select:first").prop("disabled", false);
                $(element).show();
            }
        });

        // platforms
        platforms.forEach((element, index) => {
            let categoryIndex = categoryId - 1;
            if (categoryIndex !== index) {
                $(element + " select:first").prop("disabled", true);
                $(element).hide();
            } else {
                $(element + " select:first").prop("disabled", false);
                $(element).show();
            }
        });

        // scopes
        scopes.forEach((element, index) => {
            let categoryIndex = categoryId - 1;
            if (categoryIndex !== index) {
                $(element + " select:first").prop("disabled", true);
                $(element).hide();
            } else {
                $(element + " select:first").prop("disabled", false);
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
        // show ajax spinner
        $("#autofillLoader").show();

        // the data to send
        var request = {
            paperId: $("#doiNumberInput").val(),
        };

        // sanity check
        if (!request.paperId || request.paperId.length === 0) {
            return false;
        }

        // ajax request
        $.ajax("/api/internal/deleteBookmark", {
            method: "POST",
            headers: { "Authorization": "Bearer " + frontendHash },

            contentType: "application/vnd.api+json",
            dataType: "json",

            data: JSON.stringify(request),

            success: (response) => {
                // hide ajax spinner
                $("#autofillLoader").hide();
            },

            error: (response) => {
                // hide ajax spinner
                $("#autofillLoader").hide();

                $("#title").val(response.data.title);
                $("#groupDescription").html(response.data.groupDescription);
                $("#groupDescription").trigger("change", () => { });
                $("#year").val(response.data.year);
                $("#literature").val(response.data.literature.join("\n"));
                $("#creatorList").val(response.data.creatorList.join("\n"));
                $("#workgroup").val(response.data.workgroup);
            },
        });
    });


    /**
     * frontend form validation
     * todo: this is kinda stupid
     */

    $("#validateForm").on("click", () => {
        let requiredElements = [
            "#archive",
            "#categoryId",
            "#creatorList",
            "#format",
            "#groupDescription",
            "#license",
            "#platform",
            "#scope",
            "#tagList",
            "#title",
            "#torrentFile",
            "#workgroup",
            "#year",
        ];

        requiredElements.forEach(element => {
            let value = $(element).val();
            if (value.length === 0) {
                console.log("required element " + element + " isn't filled in");
            }
        });
    });


    /**
     * AddScreenshotField
     */

    /*
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
    */


    /**
     * RemoveScreenshotField
     */

    /*
    function RemoveScreenshotField(el) {
        var sss = $('[name="screenshots[]"]');
        el.parentElement.remove();
    }
    */


    /**
     * SetResolution
     */

    /*
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
    */
})();
