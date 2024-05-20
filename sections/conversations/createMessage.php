<?php

declare(strict_types=1);


/**
 * create a new conversation message
 */

$app = Gazelle\App::go();

# https://github.com/paragonie/anti-csrf
Gazelle\Http::csrf();

# request variables
$post = Gazelle\Http::post();

try {
    # instantiate the conversation
    $conversation = new Gazelle\Conversations($post["conversationId"] ?? null);
    if (!$conversation->id) {
        throw new Exception("Conversation not found");
    }

    # validate the data
    if (empty($post["userId"])) {
        $post["userId"] = $app->user->core["id"];
    }

    if (empty($post["replyToId"])) {
        $post["replyToId"] = null;
    }

    # this throws
    if (empty($post["messageBody"])) {
        throw new Exception("Message body is required");
    }

    # create the message
    $conversation->createMessage([
        "userId" => $post["userId"] ?? $app->user->core["id"],
        "replyToId" => $post["replyToId"] ?? null,
        "body" => Gazelle\Text::esc($post["messageBody"]),
    ]);

    # redirect back to the original page
    Gazelle\Http::redirect($post["redirectToUri"] ?? "/");
} catch (Throwable $e) {
    $app->error($e->getMessage());
}
