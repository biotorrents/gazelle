/**
 * https://simplewebauthn.dev/docs/packages/browser/#startauthentication
 */

(() => {
    "use strict";

    const { startAuthentication } = SimpleWebAuthnBrowser;

    const assertWebAuthn = document.getElementById("assertWebAuthn");
    const webAuthnResponse = document.getElementById("webAuthnResponse");

    // start authentication when the user clicks a button
    assertWebAuthn.addEventListener("click", async () => {
        // reset success/error messages
        webAuthnResponse.innerText = "";

        // grab the username from the form for now (todo: webauthn autofill)
        // https://simplewebauthn.dev/docs/packages/browser/#browsersupportswebauthnautofill
        let username = $("#username").val();
        if (!username) {
            webAuthnResponse.classList.remove("success");
            webAuthnResponse.classList.add("failure");

            webAuthnResponse.innerText = "Please fill out the username field before attempting a WebAuthn login";

            return;
        }

        // GET authentication options from the endpoint that calls
        // @simplewebauthn/server -> generateAuthenticationOptions()
        const assertionRequest = await fetch("/api/internal/webAuthn/assertionRequest/" + username);

        let assertionRequestJson;
        try {
            // pass the options to the authenticator and wait for a response
            assertionRequestJson = await startAuthentication(await assertionRequest.json());
        } catch (error) {
            // some basic error handling
            webAuthnResponse.classList.remove("success");
            webAuthnResponse.classList.add("failure");

            webAuthnResponse.innerText = error;

            throw error;
        }

        // POST the response to the endpoint that calls
        // @simplewebauthn/server -> verifyAuthenticationResponse()
        const assertionResponse = await fetch("/api/internal/webAuthn/assertionResponse", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(assertionRequestJson),
        });

        // wait for the results of verification
        const assertionResponseJson = await assertionResponse.json();

        // show UI appropriate for the `verified` status
        if (assertionResponseJson && assertionResponseJson.publicKeyCredentialId) {
            // redirect to homepage
            setTimeout(function () {
                webAuthnResponse.classList.remove("failure");
                webAuthnResponse.classList.add("success");

                webAuthnResponse.innerText = "Success! Logging you in...";
                window.location = "/";
            }, 1000);
        } else {
            // show an error message
            webAuthnResponse.classList.remove("success");
            webAuthnResponse.classList.add("failure");

            webAuthnResponse.innerText = assertionResponseJson.data;
        }
    });
})();
