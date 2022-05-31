<?php
declare(strict_types=1);

# https://github.com/paragonie/anti-csrf
$csrf = new ParagonIE\AntiCSRF\AntiCSRF;
if (!empty($_POST)) {
    if (!$csrf->validateRequest()) {
        Http::response(403);
    }
}


$app = App::go();
$auth = new Auth();

# variables
$post = Http::query("post");
$server = Http::query("server");

$username = $post["username"] ?? ""; # MariaDB
$email = $post["email"] ?? ""; # MariaDB
$passphrase = $post["passphrase"] ?? ""; # Auth::makeHash
$confirmPassphrase = $post["confirmPassphrase"] ?? "";

# gazelle switch
$success = false;


# delight-im/auth
if (!empty(["post"]) && isset($post["submit"])) {
    $response = $auth->register(
        email: $post["email"],
        passphrase: $post["passphrase"],
        confirmPassphrase: $post["confirmPassphrase"],
        username: $post["username"],
        invite: $invite ?? "",
        post: $post ?? []
    );

    # success
    if (is_int($response)) {
        $emailSent = true;
        $success = true;
        unset($response);
    }
}


/** GAZELLE INVITE */


try {
    if ($success === true && !empty($invite)) {
        $query = "select inviterId, email, reason from invites where inviteKey = ?";
        $row = $app->dbNew->row($query, [$invite]);

        if (empty($row)) {
            throw new Exception("Invalid invite code");
        } else {
            $inviteEmail = Crypto::decrypt($row["email"]);
        }

        # user created, delete invite
        $query = "delete from invites where inviteKey = ?";
        $app->dbNew->do($query, [$invite]);

        # manage invite trees
        if (!empty($row["inviterId"])) {
            $query = "select treePosition, treeId, treeLevel from invite_tree where userId = ?";
            $tree = $app->dbNew->row($query, [$inviterId]);

            $treePosition = $tree["treePosition"] ?? null;
            $treeId = $tree["treeId"] ?? null;
            $treeLevel = $tree["treeLevel"] ?? null;

            # if the inviter doesn't have an invite tree
            # note: this should never happen unless you've transferred from another database like What.CD did
            if (empty($tree)) {
                $query = "select max(treeId) + 1 from invite_tree";
                $treeId = $app->dbNew->single($query);

                $query = "
                    insert into invite_tree (userId, inviterId, treePosition, treeId, treeLevel)
                    values (?, ?, ?, ?, ?)
                ";
                $app->dbNew->do($query, [$inviterId, 0, 1, $treeId, 1]);

                $treePosition = 2;
                $treeLevel = 2;
            }
            
            # normal tree position calculation
            $query = "select treePosition from invite_tree where treePosition = ? and treeLevel = ? and treeId = ?";
            $treePosition = $app->dbNew->single($query, [$treePosition, $treeLevel, $treeId]);
            
            if (!empty($treePosition)) {
                $query = "update invite_tree set treePosition = treePosition + 1 where treeId = ? and treePosition >= ?";
                $app->dbNew->do($query, [$treeId, $treePosition]);
            } else {
                $query = "select treePosition + 1 from invite_tree where treeId = ? order by treePosition desc";
                $treePosition = $app->dbNew->single($query, [$treeId]);
                $treeLevel++;

                # create invite tree record
                $query = "
                    insert into invite_tree (userId, inviterId, treePosition, treeId, treeLevel)
                    values (?, ?, ?, ?, ?)
                ";
                $app->dbNew->do($query, [$userId, $inviterId, $treePosition, $treeId, $treeLevel]);
            }
        } # if inviterId
    } # if invite
} catch (Exception $e) {
    $response = $e->getMessage();
}


/** GAZELLE USERS_MAIN */


try {
    if ($success === true) {
        # generate keys and handle users_main
        $torrent_pass = Text::random();

        $query = "
            insert into users_main
            (username, email, passHash, torrent_pass, ip, permissionId, enabled, invites, flTokens, uploaded)
            values (:username, :email, :passHash, :torrent_pass, :ip, :permissionId, :enabled, :invites, :flTokens, :uploaded)
        ";

        $app->dbNew->do($query, [
            "username" => $username,
            "email" => $email,
            "passHash" => Auth::makeHash($passphrase),
            "torrent_pass" => $torrent_pass,
            "ip" => Crypto::encrypt($server['REMOTE_ADDR']),
            "permissionId" => USER, # todo: constant
            "enabled" => 0,
            "invites" => $app->env->STARTING_INVITES,
            "flTokens" => $app->env->STARTING_TOKENS,
            "uploaded" => $app->env->STARTING_UPLOAD,
        ]);

        # default stylesheet
        $query = "select id from stylesheets where `default` = 1";
        $styleId = $app->dbNew->do($query) ?? null;

        # users_info
        $adminComment ??= "";
        $query = "
            insert into users_info (userId, styleId, authKey, inviter, joinDate, adminComment)
            values (:userId, :styleId, :authKey, :inviter, :joinDate, :adminComment)
        ";

        $app->dbNew->do($query, [
            "userId" => $userId,
            "styleId" => $styleId,
            "authKey" => $authKey,
            "inviter" => $inviterId,
            "joinDate" => "now()",
            "adminComment" => $adminComment,
        ]);

        # users_notifications_settings
        $query = "insert into users_notifications_settings (userId) values (?)";
        $app->dbNew->do($query, [$userId]);

        # update ocelot with the new user
        Tracker::update_tracker('add_user', array('id' => $userId, 'passkey' => $torrent_pass));
    }
} catch (Exception $e) {
    $response = $e->getMessage();
}


/** TWIG TEMPLATE */


$app->twig->display("user/auth/register.twig", [
    "title" => "Register",
    "response" => $response ?? null,
    "emailSent" => $emailSent ?? null,
    "invite" => $invite ?? null,
    "post" => $post ?? null,
]);
