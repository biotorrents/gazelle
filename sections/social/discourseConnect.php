<?php
declare(strict_types=1);


/**
 * @see https://meta.discourse.org/t/discourseconnect-official-single-sign-on-for-discourse-sso/13045
 *
 * ""
 * Discourse will redirect clients to discourse_connect_url with a signed payload: (say discourse_connect_url is https://somesite.com/sso)
 * You will receive incoming traffic with the following
 * https://somesite.com/sso?sso=PAYLOAD&sig=SIG
 * The payload is a Base64 encoded string comprising of a nonce 1.2k, and a return_sso_url. The payload is always a valid querystring.
 * For example, if the nonce is ABCD. raw_payload will be:
 * nonce=ABCD&return_sso_url=https%3A%2F%2Fdiscourse_site%2Fsession%2Fsso_login, this raw payload is base 64 219 encoded.
 */

$app = App::go();

$payload ??= null;
$signature ??= null;


# 1. Validate the signature: ensure that HMAC-SHA256 of PAYLOAD (using discourse_connect_secret, as the key) is equal to the sig (sig will be hex encoded).
# 2. Perform whatever authentication it has to
# 3. Create a new url-encoded payload with at least nonce, email, and external_id. You can also provide some additional data, here’s a list of all keys that Discourse will understand:
# 4. Base64 encode payload
# 5. Calculate a HMAC-SHA256 hash of the payload using discourse_connect_secret as the key and Base64 encoded payload as text
# 6. Redirect back to the return_sso_url with an sso and sig query parameter (http://discourse_site/session/sso_login?sso=payload&sig=sig)
