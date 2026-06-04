<?php

declare(strict_types=1);

namespace Humblee\Model;

use Humblee\Foundation\Core;

class Personalization
{

    /**
     * Returns an array of p13n database table rows
     *
     * $test_results (optional) BOOL  if TRUE, test criteria against current user and only return matching rows
     * $id_only      (optional) BOOL  if TRUE, returned array is just list of p13n row IDs
     */
    public function getAll(bool $test_results = false, bool $id_only = false): array
    {
        $p13n_versions = [];

        if ($test_results) {
            $p13n = \ORM::for_table(_table_content_p13n)->order_by_desc('priority')->find_many();
        } else {
            $p13n = \ORM::for_table(_table_content_p13n)->order_by_asc('priority')->find_many();
        }

        if (!$p13n) {
            return $p13n_versions;
        }

        foreach ($p13n as $criteria) {
            if ($test_results) {
                if ($criteria->active == 0) {
                    continue;
                }

                if ($this->testCriteria($criteria->criteria)) {
                    $p13n_versions[$criteria->id] = $id_only ? $criteria->id : $criteria;
                }
            } else {
                $p13n_versions[$criteria->id] = $id_only ? $criteria->id : $criteria;
            }
        }

        return $p13n_versions;
    }

    /**
     * Test a given criteria against the current user session
     *
     * Criteria structure (stored as JSON in DB):
     * First level = OR operators: any group can match to pass
     * Second level = AND operators: all conditions in a group must match
     *
     * Supported types: required_role, i18n, time_of_day
     */
    public function testCriteria(array|string $criteria): bool
    {
        $criteria = is_array($criteria) ? $criteria : json_decode($criteria);

        if (!is_array($criteria)) {
            return false;
        }

        foreach ($criteria as $criterium_OR => $criterium_AND) {
            $pass_AND = 0;
            foreach ($criterium_AND as $criterium) {
                switch ($criterium->type) {

                    case 'required_role':
                        if ($criterium->operator === "=" && Core::auth($criterium->value)) {
                            $pass_AND++;
                        } elseif ($criterium->operator === "!=" && !Core::auth($criterium->value)) {
                            $pass_AND++;
                        }
                        break;

                    case 'i18n':
                        $uri_parts = Core::getURIparts(true);
                        if ($criterium->operator === "=" && strtolower($uri_parts[0]) === strtolower($criterium->value)) {
                            $pass_AND++;
                        } elseif ($criterium->operator === "!=" && strtolower($uri_parts[0]) !== strtolower($criterium->value)) {
                            $pass_AND++;
                        }
                        break;

                    case 'time_of_day':
                        if ($criterium->operator === "<" && strtotime(date("H:i")) < strtotime($criterium->value)) {
                            $pass_AND++;
                        } elseif ($criterium->operator === ">" && strtotime(date("H:i")) > strtotime($criterium->value)) {
                            $pass_AND++;
                        }
                        break;
                }
            }

            if ($pass_AND === count($criterium_AND)) {
                return true;
            }
        }

        return false;
    }
}
