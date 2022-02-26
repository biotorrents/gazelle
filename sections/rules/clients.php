<?php
declare(strict_types=1);

$twig = Twig::go();
$allowedClients = G::$Cache->get_value('allowed_clients') ?? [];

# get and cache clients list
if (empty($allowedClients)) {
    G::$DB->query("
        select peer_id, vstring from xbt_client_whitelist
        where vstring not like '//%' order by vstring asc
    ");

    $allowedClients = G::$DB->to_array();
    $allowedClients = array_combine(
        array_column($allowedClients, 'peer_id'),
        array_column($allowedClients, 'vstring'),
    );

    G::$Cache->cache_value('allowed_clients', $allowedClients, 0);
}


View::header('Client rules');

# get text for template
$text = $twig->render(
    'rules/clients.twig',
    [
        'allowedClients' => $allowedClients,
    ]
);

# the rules template itself
echo $twig->render('rules/rules.twig', ['text' => $text]);

View::footer();
