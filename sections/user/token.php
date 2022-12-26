<?php

declare(strict_types=1);

/**
 * Adapted from
 * https://github.com/OPSnet/Gazelle/blob/master/sections/user/token.php
 */

if (!apcu_exists('DBKEY')) {
    error(403);
}

$ENV = ENV::go();
$userId = (int) ($_GET['user_id'] ?? $user['ID']);

$tokenId = (int) ($_GET['token_id'] ?? 0);
$error = null;
$token = null;
$tokenName = '';

$_GET['do'] = $_GET['do'] ?? '';
if (!empty($_GET['do']) && $userId !== $user['ID'] && !check_perms('users_mod')) {
    error(403);
}

if ($_GET['do'] === 'revoke') {
    User::revokeApiTokenById($userId, $tokenId);
    header('Location: user.php?action=edit&userid=' . $userId);
    die();
} elseif ($_GET['do'] === 'generate') {
    $tokenName = $_POST['token_name'] ?? '';
    if (empty(trim($tokenName))) {
        $error = 'You must supply a name for the token.';
    } elseif (User::hasTokenByName($userId, $tokenName)) {
        $error = 'You have already generated a token with that name.';
    } else {
        $token = User::createApiToken($userId, $tokenName, $ENV->getPriv('siteCryptoKey'));
    }
}

View::header('Generate API Token');

if (is_null($token)) {
    if ($error) {
        echo $HTML = <<<HTML
        <div class="token_error">
          <p>$error</p>
        </div>
HTML;
    }

    echo $HTML = <<<HTML
    <div class="box pad">
      <p>
        Use this page to generate new API tokens.
        When the token is shown to you is the only time the token will be visible, so be sure to copy it down.
        You, nor staff, will be able to view the value for any previously generation token.
      </p>
      <p>
        <strong class="important_text">Treat your tokens like passwords and keep them secret.</strong>
      </p>

      <div class="center pad">
        <form action="user.php?action=token&amp;do=generate&amp;user_id=$userId" method="POST">
          <input type="text" name="token_name" value="$tokenName"
          placeholder="New API token name" required />
          <input type='submit' value='Generate' />
        </form>
      </div>
    </div>
HTML;
} else {
    echo $HTML = <<<HTML
    <div class="box pad">
      <p>
        This is the only time this token value you will be shown to you, so be sure to copy it down!
        Neither you, nor staff, will be able to view the value for any previously generated token.
      </p>

      <p>
        In case of doubt, you should <strong>always</strong> revoke a token and generate a new one.
        <strong class="important_text">Treat your tokens like passwords and keep them secret.</strong>
      </p>

      <table>
        <tr class="colhead">
          <th style="text-align: center;">Name</th>
          <th>Token</th>
        </tr>

        <tr>
          <td style="text-align: center;">$tokenName</td>
          <td>
            <textarea rows="2" cols="50" onclick="this.select();" readonly>$token</textarea>
          </td>
        </tr>
      </table>

      <div class='center pad'>
        <a href='user.php?action=edit&userid=$userId'>Go back to user settings</a>
      </div>
    </div>
HTML;
}

View::footer();
