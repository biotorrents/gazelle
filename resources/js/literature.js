(() => {
    "use strict";


    /**
     * search semantic scholar
     */

    // https://tom-select.js.org/examples/remote/
    new TomSelect("#searchSemanticScholar", {
        valueField: "url",
        labelField: "name",
        searchField: "name",

        // fetch remote data
        load: function (query, callback) {

            var url = "https://api.semanticscholar.org/graph/v1/paper/autocomplete?query=" + encodeURIComponent(query);
            fetch(url)
                .then(response => response.json())
                .then(json => {
                    console.log(json);
                    callback(json.matches);
                }).catch(() => {
                    callback();
                });

        },

        // custom rendering functions for options and items
        render: {
            option: function (item, escape) {
                console.log(item, escape);

                return `<div class="py-2 d-flex">
							<div>
								<div class="mb-1">
									<span class="h4">
										${escape(item.title)}
									</span>
									<span class="text-muted">by ${escape(item.authorYear)}</span>
								</div>
							</div>
						</div>`;
            },

            item: function (item, escape) {
                console.log(item, escape);

                return `<div class="py-2 d-flex">
							<div>
								<div class="mb-1">
									<span class="h4">
										${escape(item.title)}
									</span>
									<span class="text-muted">by ${escape(item.authorYear)}</span>
								</div>
							</div>
						</div>`;
            }
        },
    });


    /*
    // https://tom-select.js.org/plugins/virtual_scroll/
    new TomSelect("#searchSemanticScholar", {
        valueField: "permalink",
        labelField: "title",
        searchField: ["title"],
        plugins: ["virtual_scroll"],
        maxOptions: 200,

        // fetch remote data
        firstUrl: function (query) {
            return "https://api.semanticscholar.org/graph/v1/paper/autocomplete?query=" + encodeURIComponent(query);
        },

        load: function (query, callback) {
            // retrieve the appropriate url
            const url = this.getUrl(query);

            fetch(url)
                .then(response => response.json())
                .then(json => {
                    // log the json response
                    console.log(json.matches);

                    // add data to the results
                    let data = json.json.matches.map((element, index) => {
                        this.addOption({
                            id: element.id,
                            title: element.title,
                            url: element.authorYear
                        });
                    });

                    // add data to the results
                    //let data = json.data.children.map(row => row.data);
                    //callback(data);
                }).catch((e) => {
                    callback();
                });
        },
    });
    */


    /*
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
                * /

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
    */

})();
