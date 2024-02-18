<?php

declare(strict_types=1);

Gazelle\Http::csrf();

$app = Gazelle\App::go();
$discourse = new Discourse();


# filters
$allowedFilters = [
  # e.g., https://boards.torrents.bio/u/ohm/messages/sent
  "sent", "new", "unread", "archive",

  # custom pages for gazelle features
  "compose", "recommend", "staff",

  # empty bypass
  null,
];

if (!in_array($filter, $allowedFilters)) {
    Gazelle\Http::response(404);
}


# inbox/outbox
$inbox = $discourse->listUserPrivateMessages();
$outbox = $discourse->getUserSentPrivateMessages();
#!d($inbox, $$outbox);exit;


$app->twig->display(
    "discourse/messages/index.twig",
    [
        "breadcrumbs" => true,
        "sidebar" => true,
        "title" => "Messages",
        "inbox" => $inbox,
        "outbox" => $outbox,
    ]
);


/**
 * sample response
 *
{
  "users": [
    {
      "id": 0,
      "username": "string",
      "name": "string",
      "avatar_template": "string"
    }
  ],
  "primary_groups": [
    null
  ],
  "topic_list": {
    "can_create_topic": true,
    "draft": "string",
    "draft_key": "string",
    "draft_sequence": 0,
    "per_page": 0,
    "topics": [
      {
        "id": 0,
        "title": "string",
        "fancy_title": "string",
        "slug": "string",
        "posts_count": 0,
        "reply_count": 0,
        "highest_post_number": 0,
        "image_url": "string",
        "created_at": "string",
        "last_posted_at": "string",
        "bumped": true,
        "bumped_at": "string",
        "archetype": "string",
        "unseen": true,
        "last_read_post_number": 0,
        "unread_posts": 0,
        "pinned": true,
        "unpinned": "string",
        "visible": true,
        "closed": true,
        "archived": true,
        "notification_level": 0,
        "bookmarked": true,
        "liked": true,
        "views": 0,
        "like_count": 0,
        "has_summary": true,
        "last_poster_username": "string",
        "category_id": "string",
        "pinned_globally": true,
        "featured_link": "string",
        "allowed_user_count": 0,
        "posters": [
          {
            "extras": "string",
            "description": "string",
            "user_id": 0,
            "primary_group_id": "string"
          }
        ],
        "participants": [
          {
            "extras": "string",
            "description": "string",
            "user_id": 0,
            "primary_group_id": "string"
          }
        ]
      }
    ]
  }
}
 *
 */
