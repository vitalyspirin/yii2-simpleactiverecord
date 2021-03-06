<?php

namespace vitalyspirin\yii2\simpleactiverecord;

class MySqlTableSchemaParser
{
    protected $maximumValidation;
    protected $tableName;
    protected $tableSchema;
    protected $columnTypes;

    public static $describeTable;
    public static $showCreateTable;


    public function __construct(TableSchema $tableSchema, $tableName,
        $tableSchemaRowList, $maximumValidation
    ) {
        $this->tableSchema = $tableSchema;
        $this->maximumValidation = $maximumValidation;
        $this->tableName = $tableName;

        foreach ($tableSchemaRowList as $schemaRow) {
            $this->columnTypes[$schemaRow['Field']] = $schemaRow['Type'];

            $this->checkForRequired($schemaRow);
            $this->checkForNumericOrString($schemaRow);

            $this->checkForRange($schemaRow);

            // Note: Dates can be in different format and converted into MySQL format after validation
            $this->checkForDate($schemaRow);

            if (self::contains($schemaRow['Extra'], 'auto') === false) {
                $this->checkIntegerType($schemaRow);
            }
            $this->checkNumberType($schemaRow);
            $this->checkForPositive($schemaRow);

            $this->checkForDefault($schemaRow);

            $this->checkForComments($schemaRow);
        }

        $this->checkForUnique();
    }


    protected function checkForRequired($schemaRow)
    {
        if (self::contains($schemaRow['Null'], 'NO') &&
            !self::contains($schemaRow['Extra'], 'auto') // exclude auto incremented PK
            && $schemaRow['Default'] == null  // ex: TIMESTAMP
            && (!self::contains($schemaRow['Type'], 'BIT') // Yii Gii doesn't add bit to required
                || $this->maximumValidation)
           ) {
            $this->tableSchema->requiredColumnList[] = $schemaRow['Field'];
        }
    }


    protected function checkForNumericOrString($schemaRow)
    {
        if (self::contains($schemaRow['Type'], 'bit(1)')) {
            $this->tableSchema->booleanColumnList[] = $schemaRow['Field'];
        } elseif (self::startsWith($schemaRow['Type'], ['tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'bit'])) {
            if (stripos($schemaRow['Extra'], 'auto') === false) {
                $this->tableSchema->integerColumnList[] = $schemaRow['Field'];
            }
        } elseif (self::contains($schemaRow['Type'], ['decimal', 'float', 'double'])) {
            $this->tableSchema->numericColumnList[] = $schemaRow['Field'];
        } elseif (self::contains($schemaRow['Type'], ['char', 'text', 'blob', 'binary'])) {
            $stringLength = filter_var($schemaRow['Type'], FILTER_SANITIZE_NUMBER_INT);
            if ($stringLength == '') {
                $this->tableSchema->stringColumnList[TableSchema::DEFAULT_LEGNTH_STRINGS][]
                    = $schemaRow['Field'];
            } else {
                $this->tableSchema->stringColumnList[$stringLength][] = $schemaRow['Field'];
            }
        } elseif (self::contains($schemaRow['Type'], ['enum', 'set']) &&
                    !$this->maximumValidation) {
            $this->tableSchema->stringColumnList[TableSchema::DEFAULT_LEGNTH_STRINGS][]
                = $schemaRow['Field'];
        } elseif (self::contains($schemaRow['Type'], ['point'])) { // MySql 5.7
            $this->tableSchema->stringColumnList[TableSchema::DEFAULT_LEGNTH_STRINGS][]
                = $schemaRow['Field'];
        } else {
            $this->tableSchema->otherColumnList[] = $schemaRow['Field'];
        }
    }


    protected function checkForUnique()
    {
        if (isset(self::$showCreateTable[$this->tableName])) {
            $pattern = '/UNIQUE KEY .*?\((.*?)\)/sm';
            preg_match_all($pattern, self::$showCreateTable[$this->tableName], $matches);

            foreach ($matches[1] as $uniqueConstraint) {
                $pattern = '/(`([^`]+)`,?)+?/sm';
                preg_match_all($pattern, $uniqueConstraint, $matches2);

                // insure that POINT type column is not in unique constraint (because
                // it can not be used in WHERE clauze without Geo function)
                $addToIndex = true;
                foreach ($matches2[2] as $columnName) {
                    if ($this->columnTypes[$columnName] == 'point') {
                        $addToIndex = false;
                        break;
                    }
                }

                if ($addToIndex) {
                    $this->tableSchema->uniqueColumnList[] = $matches2[2];
                }
            }
        }
    }


    protected function checkForRange($schemaRow)
    {
        if (self::contains($schemaRow['Type'], ['enum', 'set'])) {
            $startPosition = strpos($schemaRow['Type'], '(') + 1;
            $valueListStr = substr($schemaRow['Type'], $startPosition, -1);

            $this->tableSchema->rangeColumnList[$valueListStr][] = $schemaRow['Field'];


            preg_match_all("/'(.*?)'/", $valueListStr, $matches);
            $this->tableSchema->enumValuesColumnList[$schemaRow['Field']] = $matches[1];
        }
    }


    protected function checkForDate($schemaRow)
    {
        if (static::startsWith($schemaRow['Type'], 'datetime')) {
            $this->tableSchema->dateColumnList['datetime'][0][] = $schemaRow['Field'];
        } elseif (static::startsWith($schemaRow['Type'], 'timestamp')) {
            $this->tableSchema->dateColumnList['timestamp'][0][] = $schemaRow['Field'];
        } elseif (static::startsWith($schemaRow['Type'], 'date')) {
            $this->tableSchema->dateColumnList['date'][0][] = $schemaRow['Field'];
        } elseif (static::startsWith($schemaRow['Type'], 'time')) {
            $this->tableSchema->timeColumnList[] = $schemaRow['Field'];
        }
    }


    protected function checkIntegerType($schemaRow)
    {
        if (self::contains($schemaRow['Type'], ['tinyint'])) {
            if (self::contains($schemaRow['Type'], ['unsigned'])) {
                $this->tableSchema->integerWithRangeColumnList['tinyint unsigned'][0][] = $schemaRow['Field'];
            } else {
                $this->tableSchema->integerWithRangeColumnList['tinyint'][0][] = $schemaRow['Field'];
            }
        } elseif (self::contains($schemaRow['Type'], ['smallint'])) {
            if (self::contains($schemaRow['Type'], ['unsigned'])) {
                $this->tableSchema->integerWithRangeColumnList['smallint unsigned'][0][] = $schemaRow['Field'];
            } else {
                $this->tableSchema->integerWithRangeColumnList['smallint'][0][] = $schemaRow['Field'];
            }
        } elseif (self::contains($schemaRow['Type'], ['mediumint'])) {
            if (self::contains($schemaRow['Type'], ['unsigned'])) {
                $this->tableSchema->integerWithRangeColumnList['mediumint unsigned'][0][] =
                    $schemaRow['Field'];
            } else {
                $this->tableSchema->integerWithRangeColumnList['mediumint'][0][] = $schemaRow['Field'];
            }
        } elseif (self::contains($schemaRow['Type'], ['bigint'])) {
            if (self::contains($schemaRow['Type'], ['unsigned'])) {
                $this->tableSchema->integerWithRangeColumnList['bigint unsigned'][0][] = $schemaRow['Field'];
            } else {
                $this->tableSchema->integerWithRangeColumnList['bigint'][0][] = $schemaRow['Field'];
            }
        } elseif (self::startsWith($schemaRow['Type'], 'int')) {
            if (self::contains($schemaRow['Type'], ['unsigned'])) {
                $this->tableSchema->integerWithRangeColumnList['int unsigned'][0][] =
                    $schemaRow['Field'];
            } else {
                $this->tableSchema->integerWithRangeColumnList['int'][0][] =
                    $schemaRow['Field'];
            }
        }
    }


    protected function checkNumberType($schemaRow)
    {
        if (self::contains($schemaRow['Type'], ['float'])) {
            if (self::contains($schemaRow['Type'], ['unsigned'])) {
                $this->tableSchema->numberWithRangeColumnList['float unsigned'][0][] = $schemaRow['Field'];
            } else {
                $this->tableSchema->numberWithRangeColumnList['float'][0][] = $schemaRow['Field'];
            }
        } elseif (self::contains($schemaRow['Type'], ['double'])) {
            if (self::contains($schemaRow['Type'], ['unsigned'])) {
                $this->tableSchema->numberWithRangeColumnList['double unsigned'][0][] = $schemaRow['Field'];
            } else {
                $this->tableSchema->numberWithRangeColumnList['double'][0][] = $schemaRow['Field'];
            }
        } elseif (self::contains($schemaRow['Type'], ['decimal'])) {
            if (self::contains($schemaRow['Type'], ['unsigned'])) {
                $this->tableSchema->numberWithRangeColumnList['decimal unsigned'][0][] = $schemaRow['Field'];
            } else {
                $this->tableSchema->numberWithRangeColumnList['decimal'][0][] = $schemaRow['Field'];
            }
        }
    }


    protected function checkForPositive($schemaRow)
    {
        if (self::contains($schemaRow['Type'], 'unsigned')) {
            $this->tableSchema->positiveColumnList[] = $schemaRow['Field'];
        }
    }


    protected function checkForDefault($schemaRow)
    {
        if (!($schemaRow['Default'] == null && $schemaRow['Null'] == 'NO')) {
            $this->tableSchema->defaultColumnList[ $schemaRow['Default'] ][] =
                $schemaRow['Field'];
        }
    }


    protected function checkForComments($schemaRow)
    {
        $this->tableSchema->commentColumnList[ $schemaRow['Field'] ] = $schemaRow['Comment'];
    }


    protected static function contains($haystack, $needleList /* can be string or array of strings */)
    {
        if (!is_array($needleList)) {
            $needleList = [$needleList];
        }

        $result = false;
        foreach ($needleList as $needle) {
            $result = (stripos($haystack, $needle) !== false);
            if ($result === true) {
                break;
            }
        }

        return $result;
    }


    protected static function startsWith($haystack, $needle)
    {
        if (!is_array($needle)) {
            $needleList = [$needle];
        } else {
            $needleList = $needle;
        }

        $result = false;
        foreach ($needleList as $needle) {
            if (substr($haystack, 0, strlen($needle)) === $needle) {
                $result = true;
                break;
            }
        }

        return $result;
    }
}
