<?php declare(strict_types=1);

namespace Glider88\Fixturization\FileGenerator;

readonly class PostgresSqlTransformer implements SqlTransformerInterface
{
    public function sql(array $data): string
    {
        $sql = <<<SQL
SET session_replication_role = replica;
SET client_encoding = 'UTF8';
SQL;
        $sql .= PHP_EOL . PHP_EOL;
        foreach ($data as $table => $idToColumnToVal) {
            foreach ($idToColumnToVal as $colToVal) {
                $colsStr = implode(',', array_keys($colToVal));
                $fn = static function($val): string
                {
                    if (is_string($val)) {
                        return "'$val'";
                    }

                    if (is_bool($val)) {
                        return $val ? 'true' : 'false';
                    }

                    if (is_null($val)) {
                        return 'null';
                    }

                    return (string) $val;
                };
                $vals = array_map($fn, array_values($colToVal));
                $valsStr = implode(',', $vals);
                $sql .= "INSERT INTO $table($colsStr) VALUES($valsStr);" . PHP_EOL;
            }
        }

        $sql .= PHP_EOL . "SET session_replication_role = DEFAULT;" . PHP_EOL;

        return $sql;
    }
}
