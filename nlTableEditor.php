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
    case 'seed':
        handleSeedAction($db);
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
        array_push($rows, $row[0]);
    }

    echo '['.implode(',', $rows).']';

    $result->close();
}

function handleColumnsAction($db){
    $table = param('table', $db);
    $result = $db->query('DESCRIBE '.$table.';');
    $columns = [];

    while(($row = $result->fetch_assoc()) != null){
        array_push($columns, $row['Field']);
    }

    echo '['.implode(',', $columns).']';

    $result->close();
}

function handleRowsAction($db){
    $table = param('table', $db);
    $page = param('page');
    $rowsPerPage = 100;
    $limitStart = ($page - 1) * $rowsPerPage;
    $limitEnd = $limitStart + $rowsPerPage;
    $result = $db->query('SELECT * FROM '.$table.' LIMIT '.$limitStart.', '.$limitEnd.';');
    $rows = [];

    while(($row = $result->fetch_assoc()) != null){
        var_dump(array_keys($row));
    }

    $result->close();
}

function handleSeedAction($db){
    $db->query('TRUNCATE TABLE nl;');
    $values = [];

    for($i = 0; $i < 10000; $i++){
        $number = rand(0, 1000);
        $text = substr(md5(rand()), 0, 7);

        array_push($values, '('.$number.','.$text.',NOW())');
    }

    $query = 'INSERT INTO nl (number, text, thetime) VALUES '.implode(',', $values).';';
    echo $query;
    $result = $db->query($query);
    echo '"'.$result.'"';
}

function handleUnknownAction(){
    echo 'Unknown action!';
}