<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_privileges.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/Response.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/server_privileges.lib.php';

/**
 * PMA_ServerPrivileges_Test class
 *
 * this class is for testing server_privileges.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_ServerPrivileges_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public function setUp()
    {
        //$_REQUEST
        $_REQUEST['log'] = "index1";
        $_REQUEST['pos'] = 3;

        //$GLOBALS
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = array();
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['AllowThirdPartyFraming'] = false;
        $GLOBALS['cfg']['Server']['pmadb'] = 'pmadb';
        $GLOBALS['cfg']['Server']['usergroups'] = 'usergroups';
        $GLOBALS['cfg']['Server']['users'] = 'users';
        $GLOBALS['cfg']['ActionLinksMode'] = "both";
        $GLOBALS['cfg']['DefaultTabDatabase'] = 'db_structure.php';
        $GLOBALS['cfg']['QueryWindowHeight'] = 100;
        $GLOBALS['cfg']['QueryWindowWidth'] = 100;
        $GLOBALS['cfg']['PmaAbsoluteUri'] = "PmaAbsoluteUri";
        $GLOBALS['cfg']['DefaultTabTable'] = "db_structure.php";
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable'] = "db_structure.php";
        $GLOBALS['cfg']['Confirm'] = "Confirm";
        $GLOBALS['cfg']['ShowHint'] = true;

        $GLOBALS['cfgRelation'] = array();
        $GLOBALS['cfgRelation']['menuswork'] = false;
        $GLOBALS['table'] = "table";
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['server'] = 1;
        $GLOBALS['hostname'] = "hostname";
        $GLOBALS['username'] = "username";
        $GLOBALS['collation_connection'] = "collation_connection";
        $GLOBALS['text_dir'] = "text_dir";

        //$_POST
        $_POST['pred_password'] = 'none';
        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();

        $pmaconfig = $this->getMockBuilder('PMA_Config')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['PMA_Config'] = $pmaconfig;

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('fetchResult')
            ->will(
                $this->returnValue(
                    array(
                        'grant user1 select',
                        'grant user2 delete'
                    )
                )
            );

        $fetchSingleRow = array(
            'password' => 'pma_password',
            'Table_priv' => 'pri1, pri2',
            'Type' => 'Type',
        );
        $dbi->expects($this->any())->method('fetchSingleRow')
            ->will($this->returnValue($fetchSingleRow));

        $fetchValue = array('key1' => 'value1');
        $dbi->expects($this->any())->method('fetchValue')
            ->will($this->returnValue($fetchValue));

        $dbi->expects($this->any())->method('tryQuery')
            ->will($this->returnValue(true));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Test for PMA_getDataForDBInfo
     *
     * @return void
     */
    public function testPMAGetDataForDBInfo()
    {
        $_REQUEST['username'] = "PMA_username";
        $_REQUEST['hostname'] = "PMA_hostname";
        $_REQUEST['tablename'] = "PMA_tablename";
        $_REQUEST['dbname'] = "PMA_dbname";
        list(
            $username, $hostname, $dbname, $tablename,
            $db_and_table, $dbname_is_wildcard
        ) = PMA_getDataForDBInfo();
        $this->assertEquals(
            "PMA_username",
            $username
        );
        $this->assertEquals(
            "PMA_hostname",
            $hostname
        );
        $this->assertEquals(
            "PMA_dbname",
            $dbname
        );
        $this->assertEquals(
            "PMA_tablename",
            $tablename
        );
        $this->assertEquals(
            "`PMA_dbname`.`PMA_tablename`",
            $db_and_table
        );
        $this->assertEquals(
            true,
            $dbname_is_wildcard
        );

        //pre variable have been defined
        $_REQUEST['pred_tablename'] = "PMA_pred__tablename";
        $_REQUEST['pred_dbname'] = "PMA_pred_dbname";
        list(
            $username, $hostname, $dbname, $tablename,
            $db_and_table, $dbname_is_wildcard
        ) = PMA_getDataForDBInfo();
        $this->assertEquals(
            "PMA_pred_dbname",
            $dbname
        );
        $this->assertEquals(
            "PMA_pred__tablename",
            $tablename
        );
        $this->assertEquals(
            "`PMA_pred_dbname`.`PMA_pred__tablename`",
            $db_and_table
        );
        $this->assertEquals(
            true,
            $dbname_is_wildcard
        );

    }

    /**
     * Test for PMA_wildcardEscapeForGrant
     *
     * @return void
     */
    public function testPMAWildcardEscapeForGrant()
    {
        $dbname = '';
        $tablename = '';
        $db_and_table = PMA_wildcardEscapeForGrant($dbname, $tablename);
        $this->assertEquals(
            '*.*',
            $db_and_table
        );

        $dbname = 'dbname';
        $tablename = '';
        $db_and_table = PMA_wildcardEscapeForGrant($dbname, $tablename);
        $this->assertEquals(
            '`dbname`.*',
            $db_and_table
        );

        $dbname = 'dbname';
        $tablename = 'tablename';
        $db_and_table = PMA_wildcardEscapeForGrant($dbname, $tablename);
        $this->assertEquals(
            '`dbname`.`tablename`',
            $db_and_table
        );
    }

    /**
     * Test for PMA_rangeOfUsers
     *
     * @return void
     */
    public function testPMARangeOfUsers()
    {
        $ret = PMA_rangeOfUsers("INIT");
        $this->assertEquals(
            " WHERE `User` LIKE 'INIT%' OR `User` LIKE 'init%'",
            $ret
        );

        $ret = PMA_rangeOfUsers();
        $this->assertEquals(
            '',
            $ret
        );
    }

    /**
     * Test for PMA_getTableGrantsArray
     *
     * @return void
     */
    public function testPMAGetTableGrantsArray()
    {
        $GLOBALS['strPrivDescDelete'] = "strPrivDescDelete";
        $GLOBALS['strPrivDescCreateTbl'] = "strPrivDescCreateTbl";
        $GLOBALS['strPrivDescDropTbl'] = "strPrivDescDropTbl";
        $GLOBALS['strPrivDescIndex'] = "strPrivDescIndex";
        $GLOBALS['strPrivDescAlter'] = "strPrivDescAlter";
        $GLOBALS['strPrivDescCreateView'] = "strPrivDescCreateView";
        $GLOBALS['strPrivDescShowView'] = "strPrivDescShowView";
        $GLOBALS['strPrivDescTrigger'] = "strPrivDescTrigger";

        $ret = PMA_getTableGrantsArray();
        $this->assertEquals(
            array(
                'Delete',
                'DELETE',
                $GLOBALS['strPrivDescDelete']
            ),
            $ret[0]
        );
        $this->assertEquals(
            array(
                'Create',
                'CREATE',
                $GLOBALS['strPrivDescCreateTbl']
            ),
            $ret[1]
        );
    }

    /**
     * Test for PMA_getGrantsArray
     *
     * @return void
     */
    public function testPMAGetGrantsArray()
    {
        $ret = PMA_getGrantsArray();
        $this->assertEquals(
            array(
                'Select_priv',
                'SELECT',
                __('Allows reading data.')
            ),
            $ret[0]
        );
        $this->assertEquals(
            array(
                'Insert_priv',
                'INSERT',
                __('Allows inserting and replacing data.')
            ),
            $ret[1]
        );
    }

    /**
     * Test for PMA_getHtmlForColumnPrivileges
     *
     * @return void
     */
    public function testPMAGetHtmlForColumnPrivileges()
    {
        $columns = array(
            'row1' => 'name1'
        );
        $row = array(
            'name_for_select' => 'Y'
        );
        $name_for_select = 'name_for_select';
        $priv_for_header = 'priv_for_header';
        $name = 'name';
        $name_for_dfn = 'name_for_dfn';
        $name_for_current = 'name_for_current';

        $html = PMA_getHtmlForColumnPrivileges(
            $columns, $row, $name_for_select,
            $priv_for_header, $name, $name_for_dfn, $name_for_current
        );
        //$name
        $this->assertContains(
            $name,
            $html
        );
        //$name_for_dfn
        $this->assertContains(
            $name_for_dfn,
            $html
        );
        //$priv_for_header
        $this->assertContains(
            $priv_for_header,
            $html
        );
        //$name_for_select
        $this->assertContains(
            $name_for_select,
            $html
        );
        //$columns and $row
        $this->assertContains(
            htmlspecialchars('row1'),
            $html
        );
        //$columns and $row
        $this->assertContains(
            _pgettext('None privileges', 'None'),
            $html
        );

    }

    /**
     * Test for PMA_getHtmlForUserGroupDialog
     *
     * @return void
     */
    public function testPMAGetHtmlForUserGroupDialog()
    {
        $username = "pma_username";
        $is_menuswork = true;
        $_REQUEST['edit_user_group_dialog'] = "edit_user_group_dialog";
        $GLOBALS['is_ajax_request'] = false;

        //PMA_getHtmlForUserGroupDialog
        $html = PMA_getHtmlForUserGroupDialog($username, $is_menuswork);
        $this->assertContains(
            '<form class="ajax" id="changeUserGroupForm"',
            $html
        );
        //PMA_URL_getHiddenInputs
        $params = array('username' => $username);
        $html_output = PMA_URL_getHiddenInputs($params);
        $this->assertContains(
            $html_output,
            $html
        );
        //__('User group')
        $this->assertContains(
            __('User group'),
            $html
        );
    }

    /**
     * Test for PMA_getHtmlToChooseUserGroup
     *
     * @return void
     */
    public function testPMAGetHtmlToChooseUserGroup()
    {
        $username = "pma_username";

        //PMA_getHtmlToChooseUserGroup
        $html = PMA_getHtmlToChooseUserGroup($username);
        $this->assertContains(
            '<form class="ajax" id="changeUserGroupForm"',
            $html
        );
        //PMA_URL_getHiddenInputs
        $params = array('username' => $username);
        $html_output = PMA_URL_getHiddenInputs($params);
        $this->assertContains(
            $html_output,
            $html
        );
        //__('User group')
        $this->assertContains(
            __('User group'),
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForResourceLimits
     *
     * @return void
     */
    public function testPMAGetHtmlForResourceLimits()
    {
        $row = array(
            'max_questions' => 'max_questions',
            'max_updates' => 'max_updates',
            'max_connections' => 'max_connections',
            'max_user_connections' => 'max_user_connections',
        );

        //PMA_getHtmlForResourceLimits
        $html = PMA_getHtmlForResourceLimits($row);
        $this->assertContains(
            '<legend>' . __('Resource limits') . '</legend>',
            $html
        );
        $this->assertContains(
            __('Note: Setting these options to 0 (zero) removes the limit.'),
            $html
        );
        $this->assertContains(
            'MAX QUERIES PER HOUR',
            $html
        );
        $this->assertContains(
            $row['max_connections'],
            $html
        );
        $this->assertContains(
            $row['max_updates'],
            $html
        );
        $this->assertContains(
            $row['max_connections'],
            $html
        );
        $this->assertContains(
            $row['max_user_connections'],
            $html
        );
        $this->assertContains(
            __('Limits the number of simultaneous connections the user may have.'),
            $html
        );
        $this->assertContains(
            __('Limits the number of simultaneous connections the user may have.'),
            $html
        );
    }

    /**
     * Test for PMA_getSqlQueryForDisplayPrivTable
     *
     * @return void
     */
    public function testPMAGetSqlQueryForDisplayPrivTable()
    {
        $username = "pma_username";
        $db = '*';
        $table = "pma_table";
        $hostname = "pma_hostname";

        //$db == '*'
        $ret = PMA_getSqlQueryForDisplayPrivTable(
            $db, $table, $username, $hostname
        );
        $sql = "SELECT * FROM `mysql`.`user`"
            ." WHERE `User` = '" . PMA_Util::sqlAddSlashes($username) . "'"
            ." AND `Host` = '" . PMA_Util::sqlAddSlashes($hostname) . "';";
        $this->assertEquals(
            $sql,
            $ret
        );

        //$table == '*'
        $db = "pma_db";
        $table = "*";
        $ret = PMA_getSqlQueryForDisplayPrivTable(
            $db, $table, $username, $hostname
        );
        $sql = "SELECT * FROM `mysql`.`db`"
            ." WHERE `User` = '" . PMA_Util::sqlAddSlashes($username) . "'"
            ." AND `Host` = '" . PMA_Util::sqlAddSlashes($hostname) . "'"
            ." AND '" . PMA_Util::unescapeMysqlWildcards($db) . "'"
            ." LIKE `Db`;";
        $this->assertEquals(
            $sql,
            $ret
        );

        //$table == 'pma_table'
        $db = "pma_db";
        $table = "pma_table";
        $ret = PMA_getSqlQueryForDisplayPrivTable(
            $db, $table, $username, $hostname
        );
        $sql = "SELECT `Table_priv`"
            ." FROM `mysql`.`tables_priv`"
            ." WHERE `User` = '" . PMA_Util::sqlAddSlashes($username) . "'"
            ." AND `Host` = '" . PMA_Util::sqlAddSlashes($hostname) . "'"
            ." AND `Db` = '" . PMA_Util::unescapeMysqlWildcards($db) . "'"
            ." AND `Table_name` = '" . PMA_Util::sqlAddSlashes($table) . "';";
        $this->assertEquals(
            $sql,
            $ret
        );
    }

    /**
     * Test for PMA_getDataForChangeOrCopyUser
     *
     * @return void
     */
    public function testPMAGetDataForChangeOrCopyUser()
    {
        //$_REQUEST['change_copy'] not set
        list($queries, $password) = PMA_getDataForChangeOrCopyUser();
        $this->assertEquals(
            null,
            $queries
        );
        $this->assertEquals(
            null,
            $queries
        );

        //$_REQUEST['change_copy'] is set
        $_REQUEST['change_copy'] = true;
        $_REQUEST['old_username'] = 'PMA_old_username';
        $_REQUEST['old_hostname'] = 'PMA_old_hostname';
        list($queries, $password) = PMA_getDataForChangeOrCopyUser();
        $this->assertEquals(
            'pma_password',
            $password
        );
        $this->assertEquals(
            array(),
            $queries
        );
        unset($_REQUEST['change_copy']);
    }


    /**
     * Test for PMA_getListForExportUserDefinition
     *
     * @return void
     */
    public function testPMAGetHtmlForExportUserDefinition()
    {
        $username = "PMA_username";
        $hostname = "PMA_hostname";
        $GLOBALS['cfg']['Server']['pmadb'] = 'pmadb';
        $GLOBALS['cfg']['Server']['usergroups'] = 'usergroups';
        $GLOBALS['cfg']['Server']['users'] = 'users';
        $GLOBALS['cfg']['TextareaCols'] = 'TextareaCols';
        $GLOBALS['cfg']['TextareaRows'] = 'TextareaCols';

        list($title, $export)
            = PMA_getListForExportUserDefinition($username, $hostname);

        //validate 1: $export
        $result = '<textarea class="export" cols="' . $GLOBALS['cfg']['TextareaCols']
        . '" rows="' . $GLOBALS['cfg']['TextareaRows'];
        $this->assertContains(
            'grant user2 delete',
            $export
        );
        $this->assertContains(
            'grant user1 select',
            $export
        );
        $this->assertContains(
            $result,
            $export
        );

        //validate 2: $title
        $title_user = __('User') . ' `' . htmlspecialchars($username)
            . '`@`' . htmlspecialchars($hostname) . '`';
        $this->assertContains(
            $title_user,
            $title
        );
    }

    /**
     * Test for PMA_getSqlQueriesForDisplayAndAddUser
     *
     * @return void
     */
    public function testPMAGetSqlQueriesForDisplayAndAddNewUser()
    {
        $dbname = 'pma_dbname';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $dbname = 'pma_dbname';
        $password = 'pma_password';
        $_REQUEST['adduser_submit'] = true;
        $_POST['pred_username'] = 'any';
        $_POST['pred_hostname'] = 'localhost';
        $_REQUEST['createdb-3'] = true;
        list($create_user_real, $create_user_show, $real_sql_query, $sql_query)
            = PMA_getSqlQueriesForDisplayAndAddUser(
                $username, $hostname,
                (isset ($password) ? $password : '')
            );
        $this->assertEquals(
            "CREATE USER 'pma_username'@'pma_hostname';",
            $create_user_real
        );
        $this->assertEquals(
            "CREATE USER 'pma_username'@'pma_hostname';",
            $create_user_show
        );
        $this->assertEquals(
            "GRANT USAGE ON *.* TO 'pma_username'@'pma_hostname';",
            $real_sql_query
        );
        $this->assertEquals(
            "GRANT USAGE ON *.* TO 'pma_username'@'pma_hostname';",
            $sql_query
        );
    }

    /**
     * Test for PMA_addUser
     *
     * @return void
     */
    public function testPMAAddUser()
    {
        $dbname = 'pma_dbname';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $tablename = 'pma_tablename';
        $password = 'pma_password';
        $_REQUEST['adduser_submit'] = true;
        $_POST['pred_username'] = 'any';
        $_POST['pred_hostname'] = 'localhost';
        $_REQUEST['createdb-3'] = true;
        $_REQUEST['userGroup'] = "username";

        list(
            $ret_message, $ret_queries,
            $queries_for_display, $sql_query,
            $_add_user_error
        ) = PMA_addUser(
            $dbname,
            $username,
            $hostname,
            $dbname,
            true
        );
        $this->assertEquals(
            'You have added a new user.',
            $ret_message->getMessage()
        );
        $this->assertEquals(
            "CREATE USER ''@'localhost';GRANT USAGE ON *.* TO ''@'localhost';"
            . "GRANT ALL PRIVILEGES ON `pma_dbname`.* TO ''@'localhost';",
            $sql_query
        );
        $this->assertEquals(
            false,
            $_add_user_error
        );
    }

    /**
     * Test for PMA_updatePassword
     *
     * @return void
     */
    public function testPMAUpdatePassword()
    {
        $dbname = 'pma_dbname';
        $db_and_table = 'pma_dbname.pma_tablename';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $tablename = 'pma_tablename';
        $password = 'pma_password';
        $err_url = "error.php";
        $_POST['pma_pw'] = 'pma_pw';

        $message = PMA_updatePassword(
            $err_url, $username, $hostname
        );

        $this->assertEquals(
            "The password for 'pma_username'@'pma_hostname' "
            . "was changed successfully.",
            $message->getMessage()
        );
    }

    /**
     * Test for PMA_getMessageAndSqlQueryForPrivilegesRevoke
     *
     * @return void
     */
    public function testPMAGetMessageAndSqlQueryForPrivilegesRevoke()
    {
        $dbname = 'pma_dbname';
        $db_and_table = 'pma_dbname.pma_tablename';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $tablename = 'pma_tablename';
        $password = 'pma_password';
        $_REQUEST['adduser_submit'] = true;
        $_POST['pred_username'] = 'any';
        $_POST['pred_hostname'] = 'localhost';
        $_REQUEST['createdb-3'] = true;
        $_POST['Grant_priv'] = 'Y';
        $_POST['max_questions'] = 1000;
        list ($message, $sql_query)
            = PMA_getMessageAndSqlQueryForPrivilegesRevoke(
                $db_and_table, $dbname, $tablename, $username, $hostname
            );

        $this->assertEquals(
            "You have revoked the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage()
        );
        $this->assertEquals(
            "REVOKE ALL PRIVILEGES ON `pma_dbname`.`pma_tablename` "
            . "FROM 'pma_username'@'pma_hostname'; "
            . "REVOKE GRANT OPTION ON `pma_dbname`.`pma_tablename` "
            . "FROM 'pma_username'@'pma_hostname';",
            $sql_query
        );
    }

    /**
     * Test for PMA_updatePrivileges
     *
     * @return void
     */
    public function testPMAUpdatePrivileges()
    {
        $dbname = 'pma_dbname';
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $tablename = 'pma_tablename';
        $password = 'pma_password';
        $_REQUEST['adduser_submit'] = true;
        $_POST['pred_username'] = 'any';
        $_POST['pred_hostname'] = 'localhost';
        $_REQUEST['createdb-3'] = true;
        $_POST['Grant_priv'] = 'Y';
        $_POST['max_questions'] = 1000;
        list($sql_query, $message) = PMA_updatePrivileges(
            $username, $hostname, $tablename, $dbname
        );

        $this->assertEquals(
            "You have updated the privileges for 'pma_username'@'pma_hostname'.",
            $message->getMessage()
        );
        $this->assertEquals(
            "REVOKE ALL PRIVILEGES ON `pma_dbname`.`pma_tablename` "
            . "FROM 'pma_username'@'pma_hostname';  ",
            $sql_query
        );
    }

    /**
     * Test for PMA_getHtmlToDisplayPrivilegesTable
     *
     * @return void
     */
    public function testPMAGetHtmlToDisplayPrivilegesTable()
    {
        $dbi_old = $GLOBALS['dbi'];
        $GLOBALS['hostname'] = "hostname";
        $GLOBALS['username'] = "username";

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $fetchSingleRow = array(
            'password' => 'pma_password',
            'max_questions' => 'max_questions',
            'max_updates' => 'max_updates',
            'max_connections' => 'max_connections',
            'max_user_connections' => 'max_user_connections',
            'Table_priv' => 'Select,Insert,Update,Delete,File,Create,Alter,Index,'
                . 'Drop,Super,Process,Reload,Shutdown,Create_routine,Alter_routine,'
                . 'Show_db,Repl_slave,Create_tmp_table,Show_view,Execute,'
                . 'Repl_client,Lock_tables,References,Grant,dd'
                . 'Create_user,Repl_slave,Repl_client',
            'Type' => "'Super1','Select','Insert','Update','Create','Alter','Index',"
                . "'Drop','Delete','File','Super','Process','Reload','Shutdown','"
                . "Show_db','Repl_slave','Create_tmp_table',"
                . "'Show_view','Create_routine','"
                . "Repl_client','Lock_tables','References','Alter_routine','"
                . "Create_user','Repl_slave','Repl_client','Execute','Grant','ddd",
        );
        $dbi->expects($this->any())->method('fetchSingleRow')
            ->will($this->returnValue($fetchSingleRow));

        $dbi->expects($this->any())->method('tryQuery')
            ->will($this->returnValue(true));

        $columns = array('val1', 'replace1', 5);
        $dbi->expects($this->at(0))
            ->method('fetchRow')
            ->will($this->returnValue($columns));
        $dbi->expects($this->at(1))
            ->method('fetchRow')
            ->will($this->returnValue(false));

        $GLOBALS['dbi'] = $dbi;

        $html = PMA_getHtmlToDisplayPrivilegesTable();
        $GLOBALS['username'] = "username";

        //validate 1: fieldset
        $this->assertContains(
            '<fieldset id="fieldset_user_privtable_footer" ',
            $html
        );

        //validate 2: button
        $this->assertContains(
            __('Go'),
            $html
        );

        //validate 3: PMA_getHtmlForGlobalOrDbSpecificPrivs
        $this->assertContains(
            '<fieldset id="fieldset_user_global_rights"><legend '
            . 'data-submenu-label="' . __('Global') . '">',
            $html
        );
        $this->assertContains(
            __('Global privileges'),
            $html
        );
        $this->assertContains(
            __('Check All'),
            $html
        );
        $this->assertContains(
            __('Note: MySQL privilege names are expressed in English'),
            $html
        );

        //validate 4: PMA_getHtmlForGlobalPrivTableWithCheckboxes items
        //Select_priv
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Select_priv"',
            $html
        );
        //Create_user_priv
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Create_user_priv"',
            $html
        );
        //Insert_priv
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Insert_priv"',
            $html
        );
        //Update_priv
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Update_priv"',
            $html
        );
        //Create_priv
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Create_priv"',
            $html
        );
        //Create_routine_priv
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Create_routine_priv"',
            $html
        );
        //Execute_priv
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Execute_priv"',
            $html
        );

        //validate 5: PMA_getHtmlForResourceLimits
        $this->assertContains(
            '<legend>' . __('Resource limits') . '</legend>',
            $html
        );
        $this->assertContains(
            __('Note: Setting these options to 0 (zero) removes the limit.'),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Test for PMA_getSqlQueriesForDisplayAndAddUser
     *
     * @return void
     */
    public function testPMAGetSqlQueriesForDisplayAndAddUser()
    {
        $username = "PMA_username";
        $hostname = "PMA_hostname";
        $password = "PMA_password";
        $dbname = "PMA_db";

        list($create_user_real, $create_user_show, $real_sql_query, $sql_query)
            = PMA_getSqlQueriesForDisplayAndAddUser($username, $hostname, $password);

        //validate 1: $create_user_real
        $this->assertEquals(
            "CREATE USER 'PMA_username'@'PMA_hostname';",
            $create_user_real
        );

        //validate 2: $create_user_show
        $this->assertEquals(
            "CREATE USER 'PMA_username'@'PMA_hostname';",
            $create_user_show
        );

        //validate 3:$real_sql_query
        $this->assertEquals(
            "GRANT USAGE ON *.* TO 'PMA_username'@'PMA_hostname';",
            $real_sql_query
        );

        //validate 4:$sql_query
        $this->assertEquals(
            "GRANT USAGE ON *.* TO 'PMA_username'@'PMA_hostname';",
            $sql_query
        );


        //test for PMA_addUserAndCreateDatabase
        list($sql_query, $message) = PMA_addUserAndCreateDatabase(
            false, $real_sql_query, $sql_query, $username, $hostname, $dbname
        );

        //validate 5: $sql_query
        $this->assertEquals(
            "GRANT USAGE ON *.* TO 'PMA_username'@'PMA_hostname';",
            $sql_query
        );

        //validate 6: $message
        $this->assertEquals(
            "You have added a new user.",
            $message->getMessage()
        );
    }

    /**
     * Test for PMA_getHtmlForTableSpecificPrivileges
     *
     * @return void
     */
    public function testPMAGetHtmlForTableSpecificPrivileges()
    {
        $GLOBALS['strPrivDescCreate_viewTbl'] = "strPrivDescCreate_viewTbl";
        $GLOBALS['strPrivDescShowViewTbl'] = "strPrivDescShowViewTbl";
        $username = "PMA_username";
        $hostname = "PMA_hostname";
        $db = "PMA_db";
        $table = "PMA_table";
        $columns = array(
            'row1' => 'name1'
        );
        $row = array(
            'Select_priv' => 'Y',
            'Insert_priv' => 'Y',
            'Update_priv' => 'Y',
            'References_priv' => 'Y',
            'Create_view_priv' => 'Y',
            'ShowView_priv' => 'Y',
        );

        $html = PMA_getHtmlForTableSpecificPrivileges(
            $username, $hostname, $db, $table, $columns, $row
        );

        //validate 1: PMA_getHtmlForAttachedPrivilegesToTableSpecificColumn
        $item = PMA_getHtmlForAttachedPrivilegesToTableSpecificColumn(
            $columns, $row
        );
        $this->assertContains(
            $item,
            $html
        );
        $this->assertContains(
            __('Allows reading data.'),
            $html
        );
        $this->assertContains(
            __('Allows inserting and replacing data'),
            $html
        );
        $this->assertContains(
            __('Allows changing data.'),
            $html
        );
        $this->assertContains(
            __('Has no effect in this MySQL version.'),
            $html
        );

        //validate 2: PMA_getHtmlForNotAttachedPrivilegesToTableSpecificColumn
        $item = PMA_getHtmlForNotAttachedPrivilegesToTableSpecificColumn(
            $row
        );
        $this->assertContains(
            $item,
            $html
        );
        $this->assertContains(
            'Create_view_priv',
            $html
        );
        $this->assertContains(
            'ShowView_priv',
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForLoginInformationFields
     *
     * @return void
     */
    public function testPMAGetHtmlForLoginInformationFields()
    {
        $GLOBALS['username'] = 'pma_username';

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = array(
            "Host" => array(
                "Field" => "host",
                "Type" => "char(60)",
                "Null" => "NO",
            )
        );
        $dbi->expects($this->any())->method('getColumns')
            ->will($this->returnValue($fields_info));

        $fetchValue = "fetchValue";
        $dbi->expects($this->any())->method('fetchValue')
            ->will($this->returnValue($fetchValue));

        $GLOBALS['dbi'] = $dbi;

        $html = PMA_getHtmlForLoginInformationFields();
        list($username_length, $hostname_length)
            = PMA_getUsernameAndHostnameLength();

        //validate 1: __('Login Information')
        $this->assertContains(
            __('Login Information'),
            $html
        );
        $this->assertContains(
            __('User name:'),
            $html
        );
        $this->assertContains(
            __('Any user'),
            $html
        );
        $this->assertContains(
            __('Use text field'),
            $html
        );

        $output = PMA_Util::showHint(
            __(
                'When Host table is used, this field is ignored '
                . 'and values stored in Host table are used instead.'
            )
        );
        $this->assertContains(
            $output,
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Test for PMA_getWithClauseForAddUserAndUpdatePrivs
     *
     * @return void
     */
    public function testPMAGetWithClauseForAddUserAndUpdatePrivs()
    {
        $_POST['Grant_priv'] = 'Y';
        $_POST['max_questions'] = 10;
        $_POST['max_connections'] = 20;
        $_POST['max_updates'] = 30;
        $_POST['max_user_connections'] = 40;

        $sql_query = PMA_getWithClauseForAddUserAndUpdatePrivs();
        $expect = "WITH GRANT OPTION MAX_QUERIES_PER_HOUR 10 "
            . "MAX_CONNECTIONS_PER_HOUR 20"
            . " MAX_UPDATES_PER_HOUR 30 MAX_USER_CONNECTIONS 40";
        $this->assertContains(
            $expect,
            $sql_query
        );

    }

    /**
     * Test for PMA_getListOfPrivilegesAndComparedPrivileges
     *
     * @return void
     */
    public function testPMAGetListOfPrivilegesAndComparedPrivileges()
    {
        list($list_of_privileges, $list_of_compared_privileges)
            = PMA_getListOfPrivilegesAndComparedPrivileges();
        $expect = "`User`, `Host`, `Select_priv`, `Insert_priv`";
        $this->assertContains(
            $expect,
            $list_of_privileges
        );
        $expect = "`Select_priv` = 'N' AND `Insert_priv` = 'N'";
        $this->assertContains(
            $expect,
            $list_of_compared_privileges
        );
        $expect = "`Create_routine_priv` = 'N' AND `Alter_routine_priv` = 'N'";
        $this->assertContains(
            $expect,
            $list_of_compared_privileges
        );
    }

    /**
     * Test for PMA_getHtmlForAddUser
     *
     * @return void
     */
    public function testPMAGetHtmlForAddUser()
    {
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = array(
            "Host" => array(
                "Field" => "host",
                "Type" => "char(60)",
                "Null" => "NO",
            )
        );
        $dbi->expects($this->any())->method('getColumns')
            ->will($this->returnValue($fields_info));

        $GLOBALS['dbi'] = $dbi;

        $dbname = "pma_dbname";

        $html = PMA_getHtmlForAddUser($dbname);

        //validate 1: PMA_URL_getHiddenInputs
        $this->assertContains(
            PMA_URL_getHiddenInputs('', ''),
            $html
        );

        //validate 2: PMA_getHtmlForLoginInformationFields
        $this->assertContains(
            PMA_getHtmlForLoginInformationFields('new'),
            $html
        );

        //validate 3: Database for user
        $this->assertContains(
            __('Database for user'),
            $html
        );

        $item = PMA_Util::getCheckbox(
            'createdb-2',
            __('Grant all privileges on wildcard name (username\\_%).'),
            false, false
        );
        $this->assertContains(
            $item,
            $html
        );

        //validate 4: PMA_getHtmlToDisplayPrivilegesTable
        $this->assertContains(
            PMA_getHtmlToDisplayPrivilegesTable('*', '*', false),
            $html
        );

        //validate 5: button
        $this->assertContains(
            __('Go'),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Test for PMA_getHtmlForSpecificDbPrivileges
     *
     * @return void
     */
    public function testPMAGetHtmlForSpecificDbPrivileges()
    {
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = array(
            "Host" => array(
                "Field" => "host",
                "Type" => "char(60)",
                "Null" => "NO",
            )
        );
        $dbi->expects($this->any())->method('getColumns')
            ->will($this->returnValue($fields_info));

        $GLOBALS['dbi'] = $dbi;

        $db = "pma_dbname";

        $html = PMA_getHtmlForSpecificDbPrivileges($db);

        //validate 1: PMA_URL_getCommon
        $this->assertContains(
            PMA_URL_getCommon($db),
            $html
        );

        //validate 2: htmlspecialchars
        $this->assertContains(
            htmlspecialchars($db),
            $html
        );

        //validate 3: items
        $this->assertContains(
            __('User'),
            $html
        );
        $this->assertContains(
            __('Host'),
            $html
        );
        $this->assertContains(
            __('Type'),
            $html
        );
        $this->assertContains(
            __('Privileges'),
            $html
        );
        $this->assertContains(
            __('Grant'),
            $html
        );
        $this->assertContains(
            __('Action'),
            $html
        );

        //_pgettext('Create new user', 'New')
        $this->assertContains(
            _pgettext('Create new user', 'New'),
            $html
        );
        $this->assertContains(
            PMA_URL_getCommon(array('checkprivsdb' => $db)),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Test for PMA_getHtmlForSpecificTablePrivileges
     *
     * @return void
     */
    public function testPMAGetHtmlForSpecificTablePrivileges()
    {
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = array(
            "Host" => array(
                "Field" => "host",
                "Type" => "char(60)",
                "Null" => "NO",
            )
        );
        $dbi->expects($this->any())->method('getColumns')
            ->will($this->returnValue($fields_info));

        $GLOBALS['dbi'] = $dbi;

        $db = "pma_dbname";
        $table = "pma_table";

        $html = PMA_getHtmlForSpecificTablePrivileges($db, $table);

        //validate 1: $db, $table
        $this->assertContains(
            htmlspecialchars($db) . '.' . htmlspecialchars($table),
            $html
        );

        //validate 2: PMA_URL_getCommon
        $item = PMA_URL_getCommon(
            array(
                'db' => $db,
                'table' => $table,
            )
        );
        $this->assertContains(
            $item,
            $html
        );

        //validate 3: items
        $this->assertContains(
            __('User'),
            $html
        );
        $this->assertContains(
            __('Host'),
            $html
        );
        $this->assertContains(
            __('Type'),
            $html
        );
        $this->assertContains(
            __('Privileges'),
            $html
        );
        $this->assertContains(
            __('Grant'),
            $html
        );
        $this->assertContains(
            __('Action'),
            $html
        );

        //_pgettext('Create new user', 'New')
        $this->assertContains(
            _pgettext('Create new user', 'New'),
            $html
        );
        $this->assertContains(
            PMA_URL_getCommon(
                array('checkprivsdb' => $db, 'checkprivstable' => $table)
            ),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Test for PMA_getHtmlTableBodyForSpecificDbOrTablePrivs
     *
     * @return void
     */
    public function testPMAGetHtmlTableBodyForSpecificDbOrTablePrivss()
    {
        $privMap = null;
        $db = "pma_dbname";

        //$privMap = null
        $html = PMA_getHtmlTableBodyForSpecificDbOrTablePrivs($privMap, $db);
        $this->assertContains(
            __('No user found.'),
            $html
        );

        //$privMap != null
        $privMap = array(
            "user1" => array(
                "hostname1" => array(
                    array('Type'=>'g', 'Grant_priv'=>'Y'),
                    array('Type'=>'d', 'Db'=>"dbname", 'Grant_priv'=>'Y'),
                    array('Type'=>'t', 'Grant_priv'=>'N'),
                )
            )
        );

        $html = PMA_getHtmlTableBodyForSpecificDbOrTablePrivs($privMap, $db);

        //validate 1: $current_privileges
        $current_privileges = $privMap["user1"]["hostname1"];
        $current_user = "user1";
        $current_host = "hostname1";
        $this->assertContains(
            count($current_privileges) . "",
            $html
        );
        $this->assertContains(
            htmlspecialchars($current_user),
            $html
        );
        $this->assertContains(
            htmlspecialchars($current_host),
            $html
        );

        //validate 2: privileges[0]
        $current = $current_privileges[0];
        $this->assertContains(
            __('global'),
            $html
        );

        //validate 3: privileges[1]
        $current = $current_privileges[1];
        $this->assertContains(
            __('wildcard'),
            $html
        );
        $this->assertContains(
            htmlspecialchars($current['Db']),
            $html
        );

        //validate 4: privileges[2]
        $current = $current_privileges[2];
        $this->assertContains(
            __('table-specific'),
            $html
        );
    }

    /**
     * Test for PMA_getUserEditLink
     *
     * @return void
     */
    public function testPMAGetUserEditLink()
    {
        $username = "pma_username";
        $hostname = "pma_hostname";
        $dbname = "pma_dbname";
        $tablename = "pma_tablename";

        //PMA_getUserEditLink
        $html = PMA_getUserEditLink($username, $hostname, $dbname, $tablename);

        $url_html = PMA_URL_getCommon(
            array(
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $dbname,
                'tablename' => $tablename,
            )
        );
        $this->assertContains(
            $url_html,
            $html
        );
        $this->assertContains(
            __('Edit Privileges'),
            $html
        );

        //PMA_getUserRevokeLink
        $html = PMA_getUserRevokeLink($username, $hostname, $dbname, $tablename);

        $url_html = PMA_URL_getCommon(
            array(
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $dbname,
                'tablename' => $tablename,
                'revokeall' => 1,
            )
        );
        $this->assertContains(
            $url_html,
            $html
        );
        $this->assertContains(
            __('Revoke'),
            $html
        );

        //PMA_getUserExportLink
        $html = PMA_getUserExportLink($username, $hostname);

        $url_html = PMA_URL_getCommon(
            array(
                'username' => $username,
                'hostname' => $hostname,
                'initial' => "",
                'export' => 1,
            )
        );
        $this->assertContains(
            $url_html,
            $html
        );
        $this->assertContains(
            __('Export'),
            $html
        );
    }

    /**
     * Test for PMA_getExtraDataForAjaxBehavior
     *
     * @return void
     */
    public function testPMAGetExtraDataForAjaxBehavior()
    {
        $password = "pma_password";
        $sql_query = "pma_sql_query";
        $username = "pma_username";
        $hostname = "pma_hostname";
        $GLOBALS['dbname'] = "pma_dbname";
        $_REQUEST['adduser_submit'] = "adduser_submit";
        $_REQUEST['change_copy'] = "change_copy";
        $_REQUEST['validate_username'] = "validate_username";
        $_REQUEST['username'] = "username";
        $_POST['update_privs'] = "update_privs";

        //PMA_getExtraDataForAjaxBehavior
        $extra_data = PMA_getExtraDataForAjaxBehavior(
            $password, $sql_query, $hostname, $username
        );

        //user_exists
        $this->assertEquals(
            false,
            $extra_data['user_exists']
        );

        //db_wildcard_privs
        $this->assertEquals(
            true,
            $extra_data['db_wildcard_privs']
        );

        //user_exists
        $this->assertEquals(
            false,
            $extra_data['db_specific_privs']
        );

        //new_user_initial
        $this->assertEquals(
            'P',
            $extra_data['new_user_initial']
        );

        //sql_query
        $this->assertEquals(
            PMA_Util::getMessage(null, $sql_query),
            $extra_data['sql_query']
        );

        //new_user_string
        $this->assertContains(
            htmlspecialchars($hostname),
            $extra_data['new_user_string']
        );
        $this->assertContains(
            htmlspecialchars($username),
            $extra_data['new_user_string']
        );

        //new_privileges
        $this->assertContains(
            join(', ', PMA_extractPrivInfo('', true)),
            $extra_data['new_privileges']
        );
    }

    /**
     * Test for PMA_getChangeLoginInformationHtmlForm
     *
     * @return void
     */
    public function testPMAGetChangeLoginInformationHtmlForm()
    {
        $username = "pma_username";
        $hostname = "pma_hostname";

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = array(
            "Host" => array(
                "Field" => "host",
                "Type" => "char(60)",
                "Null" => "NO",
            )
        );
        $dbi->expects($this->any())->method('getColumns')
            ->will($this->returnValue($fields_info));

        $fetchValue = "fetchValue";
        $dbi->expects($this->any())->method('fetchValue')
            ->will($this->returnValue($fetchValue));

        $GLOBALS['dbi'] = $dbi;

        //PMA_getChangeLoginInformationHtmlForm
        $html = PMA_getChangeLoginInformationHtmlForm($username, $hostname);

        //PMA_URL_getHiddenInputs
        $this->assertContains(
            PMA_URL_getHiddenInputs('', ''),
            $html
        );

        //$username & $username
        $this->assertContains(
            htmlspecialchars($username),
            $html
        );
        $this->assertContains(
            htmlspecialchars($username),
            $html
        );

        //PMA_getHtmlForLoginInformationFields
        $this->assertContains(
            PMA_getHtmlForLoginInformationFields('change'),
            $html
        );

        //Create a new user with the same privileges
        $this->assertContains(
            "Create a new user with the same privileges",
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
    }

    /**
     * Test for PMA_getLinkToDbAndTable
     *
     * @return void
     */
    public function testPMAGetLinkToDbAndTable()
    {
        $url_dbname = "url_dbname";
        $dbname = "dbname";
        $tablename = "tablename";

        $html = PMA_getLinkToDbAndTable($url_dbname, $dbname, $tablename);

        //$dbname
        $this->assertContains(
            __('Database'),
            $html
        );
        $this->assertContains(
            $GLOBALS['cfg']['DefaultTabDatabase'],
            $html
        );
        $item = PMA_URL_getCommon(
            array(
                'db' => $url_dbname,
                'reload' => 1
            )
        );
        $this->assertContains(
            $item,
            $html
        );
        $this->assertContains(
            htmlspecialchars($dbname),
            $html
        );
        $item = PMA_Util::getTitleForTarget(
            $GLOBALS['cfg']['DefaultTabDatabase']
        );

        //$tablename
        $this->assertContains(
            __('Table'),
            $html
        );
        $this->assertContains(
            $GLOBALS['cfg']['DefaultTabTable'],
            $html
        );
        $item = PMA_URL_getCommon(
            array(
                'db' => $url_dbname,
                'table' => $tablename,
                'reload' => 1,
            )
        );
        $this->assertContains(
            $item,
            $html
        );
        $this->assertContains(
            htmlspecialchars($tablename),
            $html
        );
        $item = PMA_Util::getTitleForTarget(
            $GLOBALS['cfg']['DefaultTabTable']
        );
        $this->assertContains(
            $item,
            $html
        );
    }

    /**
     * Test for PMA_getUsersOverview
     *
     * @return void
     */
    public function testPMAGetUsersOverview()
    {
        $result = array();
        $db_rights = array();
        $pmaThemeImage = "pmaThemeImage";
        $text_dir = "text_dir";
        $GLOBALS['cfgRelation']['menuswork'] = true;

        $html = PMA_getUsersOverview(
            $result, $db_rights, $pmaThemeImage, $text_dir
        );

        //PMA_URL_getHiddenInputs
        $this->assertContains(
            PMA_URL_getHiddenInputs('', ''),
            $html
        );

        //items
        $this->assertContains(
            __('User'),
            $html
        );
        $this->assertContains(
            __('Host'),
            $html
        );
        $this->assertContains(
            __('Password'),
            $html
        );
        $this->assertContains(
            __('Global privileges'),
            $html
        );

        //PMA_Util::showHint
        $this->assertContains(
            PMA_Util::showHint(
                __('Note: MySQL privilege names are expressed in English')
            ),
            $html
        );

        //__('User group')
        $this->assertContains(
            __('User group'),
            $html
        );
        $this->assertContains(
            __('Grant'),
            $html
        );
        $this->assertContains(
            __('Action'),
            $html
        );

        //$pmaThemeImage
        $this->assertContains(
            $pmaThemeImage,
            $html
        );

        //$text_dir
        $this->assertContains(
            $text_dir,
            $html
        );

        //PMA_getFieldsetForAddDeleteUser
        $this->assertContains(
            PMA_getFieldsetForAddDeleteUser(),
            $html
        );
    }

    /**
     * Test for PMA_getFieldsetForAddDeleteUser
     *
     * @return void
     */
    public function testPMAGetFieldsetForAddDeleteUser()
    {
        $result = array();
        $db_rights = array();
        $pmaThemeImage = "pmaThemeImage";
        $text_dir = "text_dir";
        $GLOBALS['cfgRelation']['menuswork'] = true;

        $html = PMA_getUsersOverview(
            $result, $db_rights, $pmaThemeImage, $text_dir
        );

        //PMA_URL_getCommon
        $this->assertContains(
            PMA_URL_getCommon(array('adduser' => 1)),
            $html
        );

        //labels
        $this->assertContains(
            __('Add user'),
            $html
        );
        $this->assertContains(
            __('Remove selected users'),
            $html
        );
        $this->assertContains(
            __('Drop the databases that have the same names as the users.'),
            $html
        );
        $this->assertContains(
            __('Drop the databases that have the same names as the users.'),
            $html
        );
    }

    /**
     * Test for PMA_getDataForDeleteUsers
     *
     * @return void
     */
    public function testPMAGetDataForDeleteUsers()
    {
        $_REQUEST['change_copy'] = "change_copy";
        $_REQUEST['old_hostname'] = "old_hostname";
        $_REQUEST['old_username'] = "old_username";

        $queries = array();

        $ret = PMA_getDataForDeleteUsers($queries);

        $item = array(
            "# Deleting 'old_username'@'old_hostname' ...",
            "DROP USER 'old_username'@'old_hostname';",
        );
        $this->assertEquals(
            $item,
            $ret
        );
    }

    /**
     * Test for PMA_getAddUserHtmlFieldset
     *
     * @return void
     */
    public function testPMAGetAddUserHtmlFieldset()
    {
        $html = PMA_getAddUserHtmlFieldset();

        $this->assertContains(
            PMA_URL_getCommon(array('adduser' => 1)),
            $html
        );
        $this->assertContains(
            PMA_Util::getIcon('b_usradd.png'),
            $html
        );
        $this->assertContains(
            __('Add user'),
            $html
        );
    }

    /**
     * Test for PMA_getHtmlHeaderForUserProperties
     *
     * @return void
     */
    public function testPMAGetHtmlHeaderForUserProperties()
    {
        $dbname_is_wildcard = true;
        $url_dbname = "url_dbname";
        $dbname = "dbname";
        $username = "username";
        $hostname = "hostname";
        $tablename = "tablename";
        $_REQUEST['tablename'] = "tablename";

        $html = PMA_getHtmlHeaderForUserProperties(
            $dbname_is_wildcard, $url_dbname, $dbname,
            $username, $hostname, $tablename
        );

        //title
        $this->assertContains(
            __('Edit Privileges:'),
            $html
        );
        $this->assertContains(
            __('User'),
            $html
        );

        //PMA_URL_getCommon
        $item = PMA_URL_getCommon(
            array(
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => '',
                'tablename' => '',
            )
        );
        $this->assertContains(
            $item,
            $html
        );

        //$username & $hostname
        $this->assertContains(
            htmlspecialchars($username),
            $html
        );
        $this->assertContains(
            htmlspecialchars($hostname),
            $html
        );

        //$dbname_is_wildcard = true
        $this->assertContains(
            __('Databases'),
            $html
        );

        //$dbname_is_wildcard = true
        $this->assertContains(
            __('Databases'),
            $html
        );

        //PMA_URL_getCommone
        $item = PMA_URL_getCommon(
            array(
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $url_dbname,
                'tablename' => '',
            )
        );
        $this->assertContains(
            $item,
            $html
        );
        $this->assertContains(
            $dbname,
            $html
        );
    }
}
