/**
 * https://simplewebauthn.dev/docs/packages/browser/#startregistration
 */

(() => {
    "use strict";

    const { startRegistration } = SimpleWebAuthnBrowser;

    const createWebAuthn = document.getElementById("createWebAuthn");
    const webAuthnResponse = document.getElementById("webAuthnResponse");

    // start registration when the user clicks a button
    createWebAuthn.addEventListener("click", async () => {
        // reset success/error messages
        $("webAuthnResponse").hide();
        webAuthnResponse.innerText = "";

        // GET registration options from the endpoint that calls
        // @simplewebauthn/server -> generateRegistrationOptions()
        const creationRequest = await fetch("/api/internal/webAuthn/creationRequest", {
            method: "GET",
            headers: {
                "Content-Type": "application/vnd.api+json",
                "Authorization": "Bearer " + frontendHash,
            },
        });

        let creationRequestJson;
        try {
            // pass the options to the authenticator and wait for a response
            creationRequestJson = await startRegistration(await creationRequest.json());
        } catch (error) {
            // some basic error handling
            webAuthnResponse.classList.remove("success");
            webAuthnResponse.classList.add("failure");

            if (error.name === "InvalidStateError") {
                webAuthnResponse.innerText = "The authenticator was probably already registered";
            } else {
                webAuthnResponse.innerText = error;
            }

            throw error;
        }

        // POST the response to the endpoint that calls
        // @simplewebauthn/server -> verifyRegistrationResponse()
        const creationResponse = await fetch("/api/internal/webAuthn/creationResponse", {
            method: "POST",
            headers: {
                "Content-Type": "application/vnd.api+json",
                "Authorization": "Bearer " + frontendHash,
            },
            body: JSON.stringify(creationRequestJson),
        });

        // wait for the results of verification
        const creationResponseJson = await creationResponse.json();

        // show UI appropriate for the `verified` status
        if (creationResponseJson && creationResponseJson.publicKeyCredentialId) {
            $("webAuthnResponse").show();

            webAuthnResponse.classList.remove("failure");
            webAuthnResponse.classList.add("success");

            webAuthnResponse.innerHTML = "Added a new WebAuthn device";
        } else {
            // show an error message
            $("webAuthnResponse").show();

            webAuthnResponse.classList.remove("success");
            webAuthnResponse.classList.add("failure");

            webAuthnResponse.innerText = creationResponseJson.data;
        }
    });
})();
