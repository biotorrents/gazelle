/**
 * https://simplewebauthn.dev/docs/packages/browser/#startregistration
 */

(() => {
    "use strict";

    const { startRegistration } = SimpleWebAuthnBrowser;

    const elemBegin = document.getElementById("createWebAuthn");
    const elemSuccess = document.getElementById("webAuthnResponse");
    const elemError = document.getElementById("webAuthnResponse");

    // start registration when the user clicks a button
    elemBegin.addEventListener("click", async () => {
        // reset success/error messages
        elemSuccess.innerHTML = "";
        elemError.innerHTML = "";

        // GET registration options from the endpoint that calls
        // @simplewebauthn/server -> generateRegistrationOptions()
        const resp = await fetch("/api/internal/webAuthn/creationRequest");

        let attResp;
        try {
            // pass the options to the authenticator and wait for a response
            attResp = await startRegistration(await resp.json());
        } catch (error) {
            // some basic error handling
            if (error.name === "InvalidStateError") {
                elemError.innerText = "Error: Authenticator was probably already registered by user";
            } else {
                elemError.innerText = error;
            }

            throw error;
        }

        // POST the response to the endpoint that calls
        // @simplewebauthn/server -> verifyRegistrationResponse()
        const verificationResp = await fetch("/api/internal/webAuthn/creationResponse", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(attResp),
        });

        // wait for the results of verification
        const verificationJSON = await verificationResp.json();

        // show UI appropriate for the `verified` status
        if (verificationJSON && verificationJSON.verified) {
            elemSuccess.innerHTML = "Success!";
        } else {
            elemError.innerHTML = `Oh no, something went wrong! Response: <pre>${JSON.stringify(
                verificationJSON,
            )}</pre>`;
        }
    });
})();
