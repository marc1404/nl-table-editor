<?php
/**
 * Created by IntelliJ IDEA.
 * User: Marc
 * Date: 29.04.2015
 * Time: 09:22
 */

$action = param('action');
$config = parse_ini_file('config.ini');
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
    case 'delete':
        handleDeleteAction($db);
        break;
    case 'foreign-keys':
        handleForeignKeysAction($db);
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
        $param = escape($db, $param);
    }

    return $param;
}

function handleTablesAction($db){
    $result = $db->query('SHOW TABLES;');
    $rows = array();

    while(($row = $result->fetch_row()) != null){
        array_push($rows, '"'.$row[0].'"');
    }

    echo '['.implode(',', $rows).']';

    $result->close();
}

function handleColumnsAction($db){
    $table = param('table', $db);
    $result = $db->query('DESCRIBE '.$table.';');
    $columns = array();
    $primary = 'id';

    while(($row = $result->fetch_assoc()) != null){
        $column = '{"name":"'.$row['Field'].'","type":"'.$row['Type'].'"}';

        array_push($columns, $column);

        if($row['Key'] === 'PRI'){
            $primary = $row['Field'];
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
    $rows = array();

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

function handleForeignKeysAction($db){
    $table = param('table', $db);
    $result = $db->query('SELECT COLUMN_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = '.wrapInTickMark(escape($db, $table)).' AND REFERENCED_TABLE_NAME IS NOT NULL AND REFERENCED_COLUMN_NAME IS NOT NULL;');
    $foreignKeys = '{';

    while(($row = $result->fetch_assoc()) != null){
        $column = $row['COLUMN_NAME'];
        $referencedTable = $row['REFERENCED_TABLE_NAME'];
        $referencedColumn = $row['REFERENCED_COLUMN_NAME'];
        $referencedValues = getForeignKeyValues($db, $referencedTable, $referencedColumn);
        $foreignKeys .= '"'.$column.'":['.implode(',', $referencedValues).'],';
    }

    $foreignKeys = trim($foreignKeys, ',').'}';

    echo $foreignKeys;

    $result->close();
}

function handleSaveAction($db){
    $table = param('table', $db);
    $primary = param('primary', $db);
    $row = json_decode(param('row'), true);
    $columns = array();
    $insertValues = array();
    $updateValues = array();

    foreach($row as $column => $value){
        $column = escape($db, $column);
        $value = escape($db, $value);

        array_push($columns, $column);

        if(!is_numeric($value)){
            $value = wrapInTickMark($value);
        }

        array_push($insertValues, $value);

        if($column !== $primary){
            $updateValue = $column.' = '.$value;

            array_push($updateValues, $updateValue);
        }
    }

    $query = 'INSERT INTO '.$table.' ('.implode(',', $columns).') VALUES ('.implode(',', $insertValues).') ON DUPLICATE KEY UPDATE '.implode(',', $updateValues).';';

    $result = $db->query($query);

    if(!$result){
        echo $db->error;
    }
}

function handleDeleteAction($db){
    $table = param('table', $db);
    $primary = param('primary', $db);
    $value = param('value', $db);

    $query = 'DELETE FROM '.$table.' WHERE '.$primary.' = '.$value.';';

    $db->query($query);
}

function handleUnknownAction(){
    echo 'Unknown action!';
}

function escape($db, $value){
    return $db->real_escape_string($value);
}

function wrapInTickMark($string){
    return '\''.$string.'\'';
}

function getForeignKeyValues($db, $table, $column){
    $result = $db->query('SELECT * FROM '.$table.';');
    $rows = array();

    while(($row = $result->fetch_assoc()) != null){
        $id = $row[$column];
        $name = getNameFromRow($row);

        array_push($rows, '{"id":'.$id.',"name":"'.$name.'"}');
    }

    $result->close();

    return $rows;
}

function getNameFromRow($row){
    $name = '';

    foreach($row as $column => $value){
        if(strpos($column, 'name') !== false){
            $name .= $value.' ';
        }
    }

    if(strlen($name) > 0){
        return trim($name);
    }

    foreach($row as $column => $value){
        $name .= $value.' ';
    }

    return trim($name);
}