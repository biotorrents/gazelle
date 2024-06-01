(() => {
    "use strict";

    /**
     * search semantic scholar
     */

    $("#searchSemanticScholar").on("change", () => {
        // show ajax spinner
        $("#autofillLoader").show();

        // the data to send
        var request = {
            query: $("#searchSemanticScholar").val(),
        };

        // sanity check
        if (!request.query || request.query.length < 5) {
            $("#autofillLoader").hide();
            return false;
        }

        // ajax request
        $.ajax("https://api.semanticscholar.org/graph/v1/paper/autocomplete?query=" + request.query, {
            method: "GET",

            success: (response) => {
                $("#autofillLoader").hide();

                /*
                let children = $("#liveLiteratureResultEntries").children();
                children.forEach(function(element) {
                    console.log(
                        element
                    );
                
                });
                */

                $("input.liveSearchResult:not(:checked)").each(function () {
                    console.log(this);
                    //$(this).parent().remove();
                });

                response.matches.forEach((match) => {
                    // oh no
                    $("#liveLiteratureSearchResults > tbody:last-child")
                        .append("<tr><td><input type='checkbox' id='" + match.id + "' name='literatureIds[]' class='liveSearchResult'></td><td><label for='" + match.id + "'>" + match.title + "</label></td><td>" + match.authorsYear + "</td></tr>");
                });
                $("#liveLiteratureSearchResults").slideDown();

                //console.log($("#liveLiteratureResultEntries").children());
            },

            error: (response) => {
                $("#autofillLoader").hide();
            },
        });
    });

})();
