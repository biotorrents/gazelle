<?php

declare(strict_types=1);


/**
 * Gazelle\Manticore
 * 
 * Uses Sphinx as the backend for now.
 * Plans to replace with the Manticore fork.
 *
 * @see https://github.com/FoolCode/SphinxQL-Query-Builder
 * @see https://manual.manticoresearch.com/Introduction
 * @see https://github.com/biotorrents/gazelle/issues/41
 */

namespace Gazelle;

class Manticore
{
    # manticore connection
    private $connection = null;

    # manticore query language
    public $queryLanguage = null;

    /**
     * __construct
     */
    public function __construct(array $options = [])
    {
        $app = \App::go();

        # establish connection
        $this->connection = new \Foolz\SphinxQL\Drivers\Pdo\Connection();
        $this->connection->setParams([
            "host" => $app->env->manticoreHost,
            "port" => $app->env->manticorePort,
        ]);

        # instantiate query language
        $this->queryLanguage = new \Foolz\SphinxQL\SphinxQL($this->connection);

        /*
        $query = (new SphinxQL($conn))->select('column_one', 'colume_two')
            ->from('index_ancient', 'index_main', 'index_delta')
            ->match('comment', 'my opinion is superior to yours')
            ->where('banned', '=', 1);

        $result = $query->execute();
        */
    }
} # classs
