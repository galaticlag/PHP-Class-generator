<?php

/**********************************************************************
 * ClassGenerator.class.php
 **********************************************************************/

define('PERMISSION_EXCEPTION', 'Permission error : No permission to write on ' . CLASSGENERATOR_DIR . '.');
define('SERVER_EXCEPTION', 'Host error : Enter a valid host.');
define('BASE_EXCEPTION', 'Database error : Enter a valid database.');
define('AUTH_EXCEPTION', 'Authentication error : Enter a valid user name and password.');

class ClassGenerator
{

    private $exception;
    private $str_replace = array('-');
    private $str_replace_file = array();
    private $str_replace_column = array(' ', '-');
    private $skip_table = array();

    public function ClassGenerator()
    {
        $this->generateClasses($this->getTables());
    }

    private function generateClasses($tables)
    {
        foreach ($tables as $table => $table_type) {
            if (!in_array($table, $this->skip_table)) {
                $class = str_replace($this->str_replace, '', $table);
                $class = preg_replace('/[0-9]+/', '', $class);
                if ($table == 'produit') {
                    $this->str_replace_column = array(' ', '-');
                } else {
                    $this->str_replace_column = array(' ', 'fld_', '-');
                }
                $content = '<?php' . NL . NL;

                $content .= '/**' . NL;
                $content .= ' * ' . str_replace($this->str_replace_file, '', $table) . '.class.php' . NL;
                $content .= ' * ' . $this->getTableComment($table) . NL;
                $content .= ' **/' . NL;

                /***********************************************************************
                 * CLASS
                 ************************************************************************/
                $type = ($table_type == 'BASE TABLE') ? 'Table' : 'View';
                $prefixe = ($table_type == 'BASE TABLE') ? '' : 'V_';
                $content .= 'class ' . $class . ' extends ' . $type . ' {' . NL . NL;


                /***********************************************************************
                 * VARIABLES
                 ************************************************************************/
                $list_columns = array();
                $columns = $this->getColumns($table);
                $columns_info = $this->getColumnsInfo($table);
                $foreignKeys = $this->getForeignKeys($table);
                $foreignKeyTable = $this->getForeignKeyTable($table);
                $pKeys = $this->getPrimaryKeys($table);
                $content .= TAB . 'public static $DATABASE_NAME = \'' . dbdatabase . '\';' . NL;
                $content .= TAB . 'public static $TABLE_NAME = \'' . $table . '\';' . NL;
                $and = '';
                $primary_key = '';
                foreach ($pKeys as $key => $pKey) {
                    $str_column = str_replace($this->str_replace_column, '', $pKey);
                    $primary_key .= $and . '\'' . $str_column . '\'=>' . '\'' . $pKey . '\'';
                    $and = ',';
                }
                $content .= TAB . 'public static $PRIMARY_KEY = array(' . $primary_key . ');' . NL;
                $and = '';
                $columns_name = '';
                //$columns_modified = '';
                foreach ($columns as $key => $value) {
                    $str_column = str_replace($this->str_replace_column, '', $value);
                    $columns_name .= $and . '\'' . $str_column . '\'=>' . '\'' . $value . '\'';
                    //$columns_modified .= $and.'\''.$value.'\'=>0';
                    $and = ',';
                }
                $content .= TAB . 'public static $FIELD_NAME = array(' . $columns_name . ');' . NL;
                $content .= TAB . 'protected $FIELD_MODIFIED = array();' . NL;
                $content .= TAB . 'protected $RESULT = array();' . NL;
                $content .= TAB . 'protected static $FOREIGN_KEYS = array(';
                if (!empty($foreignKeyTable)) {

                    $and = '';
                    foreach ($columns as $column) {
                        if (!empty($foreignKeyTable[$column])) {
                            $content .= $and . '\'' . $column . '\'=>array(\'TABLE_NAME\'=>\'' . $foreignKeyTable[$column]['TABLE_NAME'] . '\', \'COLUMN_NAME\'=>\'' . $foreignKeyTable[$column]['COLUMN_NAME'] . '\', \'DATABASE_NAME\'=>\'' . $foreignKeyTable[$column]['DATABASE_NAME'] . '\')';
                            $and = ',';
                        }
                    }

                }
                $content .= ');';
                $content .= NL . NL . NL;

                foreach ($columns as $column) {
                    if (!empty($columns_info[$column]['Comment'])) {
                        $content .= TAB . '/**' . NL;
                        $content .= TAB . ' * @var ' . utf8_encode($columns_info[$column]['Comment']) . NL;
                        $content .= TAB . ' */' . NL;
                    }
                    $str_column = str_replace($this->str_replace_column, '', $column);
                    $content .= TAB . 'protected $' . $str_column . ' = null;' . NL;
                    if (!empty($foreignKeys[$column])) {
                        $content .= TAB . 'protected $FK_' . $foreignKeys[$column] . str_replace($this->str_replace, '', $column) . ';' . NL;
                    }
                    $list_columns[] = $column;
                }

                //$content .= TAB.'public function __construct($array = array()) {'.NL;
                //$content .= TAB.TAB.'if (!empty($array)) { $this = '.$class.'::readArray($array); }'.NL;
                //$content .= TAB.'}'.NL.NL;

                /***********************************************************************
                 * SETTERS
                 ************************************************************************/
                foreach ($columns as $column) {
                    $str_column = str_replace($this->str_replace_column, '', $column);
                    $content .= TAB . 'public function set_' . $str_column . '($pArg=\'0\') {' . NL;
                    $content .= TAB . TAB . 'IF ( $this->' . $str_column . ' !== $pArg){' . NL;
                    $content .= TAB . TAB . TAB . '$this->' . $str_column . '=$pArg; $this->FIELD_MODIFIED[\'' . $str_column . '\']=1;' . NL;
                    $content .= TAB . TAB . '}' . NL;
                    $content .= TAB . '}' . NL;
                    if (!empty($foreignKeys[$column])) {
                        $content .= TAB . 'protected function set_FK_' . $foreignKeys[$column] . str_replace($this->str_replace, '', $column) . '($pArg=\'0\') {$this->FK_' . $foreignKeys[$column] . str_replace($this->str_replace, '', $column) . '=$pArg; }' . NL;
                    }
                }
                $content .= NL;


                /***********************************************************************
                 * GETTERS
                 ************************************************************************/
                foreach ($columns as $column) {
                    $str_column = str_replace($this->str_replace_column, '', $column);
                    $content .= TAB . 'public function get_' . $str_column . '() { return (' . $this->mapMysqlTypeWithPhpType($columns_info[$column]['Type']) . ') $this->' . $str_column . '; }' . NL;
                    if (!empty($foreignKeys[$column])) {
                        $content .= TAB . 'public function get_FK_' . $foreignKeys[$column] . str_replace($this->str_replace, '', $column) . '($force_get=TRUE) { ';
                        $content .= 'if ($this->FK_' . $foreignKeys[$column] . str_replace($this->str_replace, '', $column) . '!== null || $force_get === FALSE) { return $this->FK_' . $foreignKeys[$column] . str_replace($this->str_replace, '', $column) . '; } else {';
                        $content .= '$this->FK_' . $foreignKeys[$column] . str_replace($this->str_replace, '', $column) . ' = new ' . $foreignKeys[$column] . '();';
                        $content .= '$this->FK_' . $foreignKeys[$column] . str_replace($this->str_replace, '', $column) . '->load(array(self::$FOREIGN_KEYS[\'' . $column . '\'][\'COLUMN_NAME\'] => $this->' . $column . '));';
                        $content .= 'return $this->FK_' . $foreignKeys[$column] . str_replace($this->str_replace, '', $column) . '; } }' . NL;
                    }
                }
                $content .= NL;

                $content .= '}' . NL;

                // Write file
                $this->createClassFile($prefixe . str_replace($this->str_replace_file, '', $table), $content);
            }
        }
    }

    private function getTableComment($table)
    {
        $result = Database::select('SHOW TABLE STATUS WHERE NAME="' . $table . '"');

        foreach ($result as $key => $column) {
            if (!empty($column['Comment'])) {
                return $column['Comment'];
            } else
                return '';
        }

        return '';
    }

    private function getColumns($table)
    {
        $result = Database::select('SHOW COLUMNS FROM `' . $table . '`');
        $columns = array();

        foreach ($result as $key => $column)
            $columns[$key] = $column['Field'];

        return $columns;
    }

    private function getColumnsInfo($table)
    {
        $result = Database::select('SHOW FULL COLUMNS FROM `' . $table . '`');
        $columns = array();

        foreach ($result as $key => $column) {
            $columns[$column['Field']]['Comment'] = $column['Comment'];
            $columns[$column['Field']]['Type'] = $column['Type'];
        }
        return $columns;
    }

    private function getForeignKeys($table)
    {
        $result = Database::select('SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME IS NOT NULL AND TABLE_NAME = :table', [':table' => $table]);
        $columns = array();

        foreach ($result as $key => $column) {
            if ($column['REFERENCED_TABLE_SCHEMA'] == dbdatabase)
                $columns[$column['COLUMN_NAME']] = str_replace($this->str_replace, '', $column['REFERENCED_TABLE_NAME']);
        }

        return $columns;
    }

    private function getForeignKeyTable($table)
    {
        $result = Database::select('SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME IS NOT NULL AND TABLE_NAME = :table', [':table' => $table]);
        $columns = array();

        foreach ($result as $key => $column) {
            if ($column['REFERENCED_TABLE_SCHEMA'] == dbdatabase) {
                //$columns[$column['COLUMN_NAME']] = $column['REFERENCED_TABLE_SCHEMA'].'.'.$column['REFERENCED_TABLE_NAME'].'.'.$column['REFERENCED_COLUMN_NAME'];
                $columns[$column['COLUMN_NAME']]['TABLE_NAME'] = $column['REFERENCED_TABLE_NAME'];
                $columns[$column['COLUMN_NAME']]['COLUMN_NAME'] = $column['REFERENCED_COLUMN_NAME'];
                $columns[$column['COLUMN_NAME']]['DATABASE_NAME'] = $column['REFERENCED_TABLE_SCHEMA'];
            }
        }

        return $columns;
    }

    public function getPrimaryKeys($table)
    {
        $result = Database::select('SHOW COLUMNS FROM `' . $table . '`');
        $pKeys = array();

        foreach ($result as $key => $column) {
            if ($column['Key'] == 'PRI') {
                $pKeys[$key] = $column['Field'];
            }
        }

        return $pKeys;
    }

    private function mapMysqlTypeWithPhpType($type)
    {
        if (strpos($type, 'int') !== FALSE) {
            return 'integer';
        } elseif (strpos($type, 'float') !== FALSE) {
            return 'float';
        } elseif (strpos($type, 'decimal') !== FALSE) {
            return 'float';
        } elseif (strpos($type, 'bit(1)') !== FALSE) {
            return 'boolean';
        } else {
            return 'string';
        }
    }

    private function createClassFile($file_to_save, $text_to_save)
    {
        $file = CLASSGENERATOR_DIR . $file_to_save . '.class.php';
        chmod(CLASSGENERATOR_DIR, 0777);
        if (!file_exists($file))
            if (!touch($file))
                $this->exception = PERMISSION_EXCEPTION;
            else
                chmod($file, 0777);
        $fp = fopen($file, 'w');
        fwrite($fp, $text_to_save);
        fclose($fp);
    }

    private function getTables()
    {
        $result = Database::select('SHOW FULL TABLES');
        $tables = array();

        foreach ($result as $key => $table) {
            $tables[$table['Tables_in_' . dbdatabase]] = $table['Table_type'];
        }

        return $tables;
    }

    public function getException()
    {
        return $this->exception;
    }
}

?>