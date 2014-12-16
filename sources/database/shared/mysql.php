<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core_database_drivers
 */

/**
 * Base class for MySQL database drivers.
 * @package    core_database_drivers
 */
class Database_super_mysql
{
    /**
     * Find whether the database may run GROUP BY unfettered with restrictions on the SELECT'd fields having to be represented in it or aggregate functions
     *
     * @return boolean                  Whether it can
     */
    public function can_arbitrary_groupby()
    {
        return true;
    }

    /**
     * Get the default user for making db connections (used by the installer as a default).
     *
     * @return string                   The default user for db connections
     */
    public function db_default_user()
    {
        return 'root';
    }

    /**
     * Get the default password for making db connections (used by the installer as a default).
     *
     * @return string                   The default password for db connections
     */
    public function db_default_password()
    {
        return '';
    }

    /**
     * Create a table index.
     *
     * @param  ID_TEXT                  $table_name The name of the table to create the index on
     * @param  ID_TEXT                  $index_name The index name (not really important at all)
     * @param  string                   $_fields Part of the SQL query: a comma-separated list of fields to use on the index
     * @param  array                    $db The DB connection to make on
     */
    public function db_create_index($table_name, $index_name, $_fields, $db)
    {
        if ($index_name[0] == '#') {
            if ($this->using_innodb()) {
                return;
            }
            $index_name = substr($index_name, 1);
            $type = 'FULLTEXT';
        } else {
            $type = 'INDEX';
        }
        $this->db_query('ALTER TABLE ' . $table_name . ' ADD ' . $type . ' ' . $index_name . ' (' . $_fields . ')', $db);
    }

    /**
     * Change the primary key of a table.
     *
     * @param  ID_TEXT                  $table_name The name of the table to create the index on
     * @param  array                    $new_key A list of fields to put in the new key
     * @param  array                    $db The DB connection to make on
     */
    public function db_change_primary_key($table_name, $new_key, $db)
    {
        $this->db_query('ALTER TABLE ' . $table_name . ' DROP PRIMARY KEY', $db);
        $this->db_query('ALTER TABLE ' . $table_name . ' ADD PRIMARY KEY (' . implode(',', $new_key) . ')', $db);
    }

    /**
     * Assemble part of a WHERE clause for doing full-text search
     *
     * @param  string                   $content Our match string (assumes "?" has been stripped already)
     * @param  boolean                  $boolean Whether to do a boolean full text search
     * @return string                   Part of a WHERE clause for doing full-text search
     */
    public function db_full_text_assemble($content, $boolean)
    {
        if (!$boolean) {
            $content = str_replace('"', '', $content);
            if ((strtoupper($content) == $content) && (!is_numeric($content))) {
                return 'MATCH (?) AGAINST (_latin1\'' . $this->db_escape_string($content) . '\' COLLATE latin1_general_cs)';
            }
            return 'MATCH (?) AGAINST (\'' . $this->db_escape_string($content) . '\')';
        }

        return 'MATCH (?) AGAINST (\'' . $this->db_escape_string($content) . '\' IN BOOLEAN MODE)';
    }

    /**
     * Get the ID of the first row in an auto-increment table (used whenever we need to reference the first).
     *
     * @return integer                  First ID used
     */
    public function db_get_first_id()
    {
        return 1;
    }

    /**
     * Get a map of ocPortal field types, to actual mySQL types.
     *
     * @return array                    The map
     */
    public function db_get_type_remap()
    {
        $type_remap = array(
            'AUTO' => 'integer unsigned auto_increment',
            'AUTO_LINK' => 'integer', // not unsigned because it's useful to have -ve for temporary usage whilst importing
            'INTEGER' => 'integer',
            'UINTEGER' => 'integer unsigned',
            'SHORT_INTEGER' => 'tinyint',
            'REAL' => 'real',
            'BINARY' => 'tinyint(1)',
            'MEMBER' => 'integer', // not unsigned because it's useful to have -ve for temporary usage whilst importing
            'GROUP' => 'integer', // not unsigned because it's useful to have -ve for temporary usage whilst importing
            'TIME' => 'integer unsigned',
            'LONG_TRANS' => 'integer unsigned',
            'SHORT_TRANS' => 'integer unsigned',
            'LONG_TRANS__COMCODE' => 'integer',
            'SHORT_TRANS__COMCODE' => 'integer',
            'SHORT_TEXT' => 'varchar(255)',
            'LONG_TEXT' => 'longtext',
            'ID_TEXT' => 'varchar(80)',
            'MINIID_TEXT' => 'varchar(40)',
            'IP' => 'varchar(40)', // 15 for ip4, but we now support ip6
            'LANGUAGE_NAME' => 'varchar(5)',
            'URLPATH' => 'varchar(255) BINARY',
            'MD5' => 'varchar(33)'
        );
        return $type_remap;
    }

    /**
     * Whether to use InnoDB for mySQL. Change this function by hand - official only MyISAM supported
     *
     * @return boolean                  Answer
     */
    public function using_innodb()
    {
        return false;
    }

    /**
     * Create a new table.
     *
     * @param  ID_TEXT                  $table_name The table name
     * @param  array                    $fields A map of field names to ocPortal field types (with *#? encodings)
     * @param  array                    $db The DB connection to make on
     * @param  ID_TEXT                  $raw_table_name The table name with no table prefix
     */
    public function db_create_table($table_name, $fields, $db, $raw_table_name)
    {
        $type_remap = $this->db_get_type_remap();

        $_fields = '';
        $keys = '';
        foreach ($fields as $name => $type) {
            if ($type[0] == '*') { // Is a key
                $type = substr($type, 1);
                if ($keys != '') {
                    $keys .= ', ';
                }
                $keys .= $name;
            }

            if ($type[0] == '?') { // Is perhaps null
                $type = substr($type, 1);
                $perhaps_null = 'NULL';
            } else {
                $perhaps_null = 'NOT NULL';
            }

            $type = isset($type_remap[$type]) ? $type_remap[$type] : $type;

            $_fields .= '    ' . $name . ' ' . $type;
            if (substr($name, -13) == '__text_parsed') {
                $_fields .= ' DEFAULT \'\'';
            } elseif (substr($name, -13) == '__source_user') {
                $_fields .= ' DEFAULT ' . strval(db_get_first_id());
            }
            $_fields .= ' ' . $perhaps_null . ',' . "\n";
        }

        $innodb = $this->using_innodb();
        $table_type = ($innodb ? 'INNODB' : 'MyISAM');
        $type_key = 'engine';
        if ($raw_table_name == 'sessions') {
            $table_type = 'HEAP';
        }

        $query = 'CREATE TABLE ' . $table_name . ' (' . "\n" . $_fields . '
            PRIMARY KEY (' . $keys . ')
        )';

        $query .= ' CHARACTER SET=utf8';

        $query .= ' ' . $type_key . '=' . $table_type . ';';
        $this->db_query($query, $db, null, null);
    }

    /**
     * Encode an SQL statement fragment for a conditional to see if two strings are equal.
     *
     * @param  ID_TEXT                  $attribute The attribute
     * @param  string                   $compare The comparison
     * @return string                   The SQL
     */
    public function db_string_equal_to($attribute, $compare)
    {
        return $attribute . "='" . db_escape_string($compare) . "'";
    }

    /**
     * Encode an SQL statement fragment for a conditional to see if two strings are not equal.
     *
     * @param  ID_TEXT                  $attribute The attribute
     * @param  string                   $compare The comparison
     * @return string                   The SQL
     */
    public function db_string_not_equal_to($attribute, $compare)
    {
        return $attribute . "<>'" . db_escape_string($compare) . "'";
    }

    /**
     * Find whether expression ordering support is present
     *
     * @param  array                    $db A DB connection
     * @return boolean                  Whether it is
     */
    public function db_has_expression_ordering($db)
    {
        return true;
    }

    /**
     * This function is internal to the database system, allowing SQL statements to be build up appropriately. Some databases require IS NULL to be used to check for blank strings.
     *
     * @return boolean                  Whether a blank string IS NULL
     */
    public function db_empty_is_null()
    {
        return false;
    }

    /**
     * Delete a table.
     *
     * @param  ID_TEXT                  $table The table name
     * @param  array                    $db The DB connection to delete on
     */
    public function db_drop_table_if_exists($table, $db)
    {
        $this->db_query('DROP TABLE IF EXISTS ' . $table, $db);
    }

    /**
     * Determine whether the database is a flat file database, and thus not have a meaningful connect username and password.
     *
     * @return boolean                  Whether the database is a flat file database
     */
    public function db_is_flat_file_simple()
    {
        return false;
    }

    /**
     * Encode a LIKE string comparision fragement for the database system. The pattern is a mixture of characters and ? and % wilcard symbols.
     *
     * @param  string                   $pattern The pattern
     * @return string                   The encoded pattern
     */
    public function db_encode_like($pattern)
    {
        $ret = preg_replace('#([^\\\\])\\\\\\\\_#', '${1}\_', $this->db_escape_string($pattern));
        return $ret;
    }

    /**
     * Close the database connections. We don't really need to close them (will close at exit), just disassociate so we can refresh them.
     */
    public function db_close_connections()
    {
        $this->cache_db = array();
        $this->last_select_db = null;
    }
}
