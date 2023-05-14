/**
 * https://simplewebauthn.dev/docs/packages/browser/#startauthentication
 */

(() => {
    "use strict";

    const { startAuthentication } = SimpleWebAuthnBrowser;

    const elemBegin = document.getElementById("assertWebAuthn");
    const elemSuccess = document.getElementById("webAuthnResponse");
    const elemError = document.getElementById("webAuthnResponse");

    // start authentication when the user clicks a button
    elemBegin.addEventListener("click", async () => {
        // grab the username
        const username = $("#username").val();
        if (!username) {
            elemError.innerHTML = "please fill out the username field before attempting a WebAuthn login";
            return;
        }

        // reset success/error messages
        elemSuccess.innerHTML = "";
        elemError.innerHTML = "";

        // GET authentication options from the endpoint that calls
        // @simplewebauthn/server -> generateAuthenticationOptions()
        const resp = await fetch("/api/internal/webAuthn/assertionRequest/" + username);

        let asseResp;
        try {
            // pass the options to the authenticator and wait for a response
            asseResp = await startAuthentication(await resp.json());
        } catch (error) {
            // some basic error handling
            elemError.innerText = error;
            throw error;
        }

        // POST the response to the endpoint that calls
        // @simplewebauthn/server -> verifyAuthenticationResponse()
        const verificationResp = await fetch("/api/internal/webAuthn/assertionResponse", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(asseResp),
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
