<?php
/**
 * Created by IntelliJ IDEA.
 * User: Marc
 * Date: 29.04.2015
 * Time: 09:22
 */

$action = param('action');
$config = parse_ini_file('nlTableEditor.ini');
$db = new mysqli('localhost', $config['db.user'], $config['db.password'], $config['db.database']);

if($db->connect_errno){
    die('Could not connect to the MySQL database!');
}

switch($action){
    case 'tables':
        handleTablesAction($db);
        break;
    case 'columns':
        handleColumnsAction($db);
        break;
    case 'rows':
        handleRowsAction($db);
        break;
    case 'save':
        handleSaveAction($db);
        break;
    default:
        handleUnknownAction();
}

$db->close();

function param($name, $db = null){
    if(!isset($_GET[$name])){
        die('Missing parameter: '.$name.'!');
    }

    $param = $_GET[$name];

    if(isset($db)){
        $param = $db->real_escape_string($param);
    }

    return $param;
}

function handleTablesAction($db){
    $result = $db->query('SHOW TABLES;');
    $rows = [];

    while(($row = $result->fetch_row()) != null){
        array_push($rows, '"'.$row[0].'"');
    }

    echo '['.implode(',', $rows).']';

    $result->close();
}

function handleColumnsAction($db){
    $table = param('table', $db);
    $result = $db->query('DESCRIBE '.$table.';');
    $columns = [];
    $primary = 'id';

    while(($row = $result->fetch_assoc()) != null){
        $column = $row['Field'];

        array_push($columns, '"'.$column.'"');

        if($row['Key'] === 'PRI'){
            $primary = $column;
        }
    }
    echo '{"primary":"'.$primary.'","columns":';
    echo '['.implode(',', $columns).']}';

    $result->close();
}

function handleRowsAction($db){
    $table = param('table', $db);
    $page = param('page');
    $rowsPerPage = 100;
    $offset = ($page - 1) * $rowsPerPage;
    $result = $db->query('SELECT * FROM '.$table.' LIMIT '.$rowsPerPage.' OFFSET '.$offset.';');
    $rows = [];

    while(($row = $result->fetch_assoc()) != null){
        $keys = array_keys($row);
        $rowJson = '{';

        foreach($keys as $key){
            $rowJson .= '"'.$key.'":"'.$row[$key].'",';
        }

        $rowJson = rtrim($rowJson, ',');
        $rowJson .= '}';

        array_push($rows, $rowJson);
    }

    echo '['.implode(',', $rows).']';

    $result->close();
}

function handleSaveAction($db){
    $table = param('table', $db);
    $row = param('row', $db);

    var_dump($row);
}

function handleUnknownAction(){
    echo 'Unknown action!';
}