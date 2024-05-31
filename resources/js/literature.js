(() => {
    "use strict";

    /**
     * autofill by doi number
     */

    $("#searchSemanticScholar").on("change", () => {
        // show ajax spinner
        $("#autofillLoader").show();

        // the data to send
        var request = {
            query: $("#searchSemanticScholar").val(),
        };

        // sanity check
        if (!request.query || request.query.length === 0) {
            $("#autofillLoader").hide();
            return false;
        }

        // ajax request
        $.ajax("/api/internal/searchSemanticScholar", {
            method: "POST",
            headers: { "Authorization": "Bearer " + frontendHash },

            contentType: "application/vnd.api+json",
            dataType: "json",

            data: JSON.stringify(request),

            success: (response) => {
                $("#autofillLoader").hide();
                console.log(response);

                /*
                $("#identifierFormField").val($("#doiNumberInput").val());

                $("#title").val(response.data.title);
                $("#groupDescription").html(response.data.groupDescription);
                $("#groupDescription").trigger("change", () => { });
                $("#year").val(response.data.year);
                $("#literature").val(response.data.literature.join("\n"));
                $("#creatorList").val(response.data.creatorList.join("\n"));
                $("#workgroup").val(response.data.workgroup);
                */
            },

            error: (response) => {
                $("#autofillLoader").hide();
            },
        });
    });

})();
