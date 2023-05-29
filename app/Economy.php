<?php

declare(strict_types=1);


/**
 * Gazelle\Economy
 */

namespace Gazelle;

class Economy
{
    /**
     * bonusPoints
     *
     * Put the bonus point calculation here,
     * so it can be used in multiple places.
     *
     * @param array $data
     * @return int
     */
    public static function bonusPoints(array $data = []): int
    {
        $app = \Gazelle\App::go();

        $bonusPoints = ($app->env->bonusPointsCoefficient + (0.55 * ($data["torrentCount"] * (sqrt(($data["dataSize"] / $data["torrentCount"]) / 1073741824) * pow(1.5, ($data["seedTime"] / $data["torrentCount"]) / (24 * 365))))) / (max(1, sqrt(($data["seederCount"] / $data["torrentCount"]) + 4) / 3))) ** 0.95;
        $bonusPoints = intval(max(min($bonusPoints, ($bonusPoints * 2) - ($data["bonusPoints"] / 1440)), 0));

        # reset points after 100k
        if ($bonusPoints > 100000) {
            $bonusPoints = 0;
        }

        return $bonusPoints;
    }

} # class
