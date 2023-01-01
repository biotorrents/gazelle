<?php

declare(strict_types=1);


/**
 * Gazelle\Sphinx
 *
 * @see https://github.com/FoolCode/SphinxQL-Query-Builder
 */

namespace Gazelle;

class Sphinx
{
    # sphinx connection
    private $connection = null;

    # sphinx query language
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
            "host" => $app->env->sphinxHost,
            "port" => $app->env->sphinxPort,
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
