<?php
/**
 * Created by PhpStorm.
 * User: umutcanguney
 * Date: 23/01/2017
 * Time: 10:21
 */

namespace App\Connection;

use PDO;

class ExtendedPDO extends PDO
{
    public function quoteArray(array $value, $parameter_type = self::PARAM_STR, $includeParenthesis = true)
    {
        // quote array values, not keys, then combine with commas
        foreach ($value as $k => $v) {
            $value[$k] = $this->quote($v, $parameter_type);
        }

        if ($includeParenthesis) {
            return '(' . implode(', ', $value) . ')';
        }

        return implode(', ', $value);
    }
}
