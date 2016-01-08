<?php

DEFINE ('INCLUDE_DIR', 'include');
DEFINE ('COMMON_FILE', 'common.php');

class db
{

    public $mysqli;

    public function __construct($config)
    {
        $this->mysqli = new MySQLi($config['dbhost'], $config['dbuser'], $config['dbpass'], $config['dbname']);
        if ($this->mysqli->connect_errno) {
            throw new mysqli_sql_exception('Не удалось подключиться к MySQL: (' . $this->mysqli->connect_errno . ') ' . $this->mysqli->connect_error);
        }
        $this->mysqli->set_charset("utf8");
    }

    public function query($query)
    {
        $query = $this->mysqli->real_escape_string($query);
        $result = $this->mysqli->query($query);

        if (!$result) {
            throw new mysqli_sql_exception('Не удалось выполнить запрос (' . $this->mysqli->errno . ") " . $this->mysqli->error);
        }

        return $result;
    }

    public function getTablesList()
    {
        $result = NULL;
        $getTablesListQuery = "SELECT id, name_table as name FROM cb_tables ORDER BY cat_id, table_num";
        try {
            $result = $this->mysqli->query($getTablesListQuery);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getFieldsList($tableId)
    {
        $result = NULL;
        $getFieldsListQuery = "SELECT id, name_field as name FROM cb_fields WHERE table_id=$tableId ORDER BY field_num";
        try {
            $result = $this->mysqli->query($getFieldsListQuery);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getLinesList($tableId)
    {
        $result = NULL;
        $getFieldsListQuery = "SELECT id, id as name FROM cb_data$tableId WHERE status=0 ORDER BY id DESC";
        try {
            $result = $this->mysqli->query($getFieldsListQuery);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function setFileName($tableId, $fieldId, $lineId, $fileName)
    {
        $result = NULL;
        $setFileNameQuery = "UPDATE cb_data$tableId SET f$fieldId = '$fileName' WHERE id = $lineId";
        try {
            $result = $this->mysqli->query($setFileNameQuery);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return $result;
    }
}

function showMainPage(){
    global $csrf;
?>

<!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
            <meta name="description" content="">
            <meta name="author" content="">
            <link rel="icon" href="/favicon.ico">

            <title>Files upload addon</title>
        </head>
        <body>
            <form method="POST" enctype="multipart/form-data">
                <select name="tableId" id="tablesSelect" onchange="getFieldsFromAPI(this);">
                    <option value="0" selected>Выберите Табилцу</option>
                </select>
                <select name="fieldId" id="fieldsSelect" onchange="getLinesFromAPI();" disabled>
                    <option value="0" selected>Выберите Поле</option>
                </select>
                <select name="lineId" id="linesSelect" onchange="enableFileUploadInput();" disabled>
                    <option value="0" selected>Выберите Запись</option>
                </select>
                <input type="file" name="file" id="fileUploadInput" placeholder="Выберите файл" onchange="enableSubmitButton();" disabled>
                <input type="hidden" name="csrf" value="<?=$csrf?>">
                <input type="submit" id="submitButton" disabled>
            </form>
            <script src="https://code.jquery.com/jquery.min.js"></script>
            <script>
                var tablesSelect         = $("#tablesSelect");
                var fieldsSelect         = $("#fieldsSelect");
                var linesSelect          = $("#linesSelect");
                var fileUploadInput      = $("#fileUploadInput");
                var submitButton         = $("#submitButton");

                function getTablesFromAPI() {
                    $.getJSON('/upload.php', {
                        request: 'tables',
                    }).done(function(json) {
                        setTables(json);
                    }).fail(function( jqXHR, textStatus, error ) {
                        alert( "Request to tables API failed: " + jqXHR + "," + textStatus + "," + error);
                    });
                }

                function getFieldsFromAPI() {
                    $.getJSON('/upload.php', {
                        request: 'fields',
                        id: tablesSelect.val(),
                    }).done(function(json) {
                        setFields(json);
                    }).fail(function( jqXHR, textStatus, error ) {
                        alert( "Request to fields API failed: " + jqXHR + "," + textStatus + "," + error);
                    });
                }

                function getLinesFromAPI() {
                    $.getJSON('/upload.php', {
                        request: 'lines',
                        id: tablesSelect.val(),
                    }).done(function(json) {
                        setLines(json);
                    }).fail(function( jqXHR, textStatus, error ) {
                        alert( "Request to lines API failed: " + jqXHR + "," + textStatus + "," + error);
                    });
                }

                function setTables(tablesListJson) {
                    tablesSelect.empty();
                    tablesListJson.forEach(function(item) {
                        tablesSelect.append(
                            $("<option>")
                                .val(item.id)
                                .append(item.name)
                        );
                    });
                }

                function setFields(fieldsListJson) {
                    enableFieldsSelect();
                    fieldsSelect.empty();
                    fieldsListJson.forEach(function(item) {
                        fieldsSelect.append(
                            $("<option>")
                                .val(item.id)
                                .append(item.name)
                        );
                    });
                    enableLinesSelect();
                }

                function setLines(linesListJson) {
                    enableLinesSelect();
                    linesSelect.empty();
                    linesListJson.forEach(function(item) {
                        linesSelect.append(
                            $("<option>")
                                .val(item.id)
                                .append(item.name)
                        );
                    });
                    enableFileUploadInput();
                }

                function enableFieldsSelect()
                {
                    fieldsSelect.removeAttr('disabled');
                }

                function enableLinesSelect()
                {
                    linesSelect.removeAttr('disabled');
                }

                function enableFileUploadInput() {
                    fileUploadInput.removeAttr('disabled');
                }

                function enableSubmitButton() {
                    submitButton.removeAttr('disabled');
                }

                $(document).ready(function() {
                    getTablesFromAPI();
                });

            </script>

        </body>
    </html>
<?php
}

$commonFilePath = INCLUDE_DIR . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . COMMON_FILE;
if (file_exists($commonFilePath)) include_once($commonFilePath);

if (!isset($user)) {
    die('only authorized users allowed');
}

if (!isset($config)) {
    die('$config variable is not set');
}

foreach ($_POST as $postVarName=>$postVarValue) {
    $$postVarName = $postVarValue;
}

foreach ($_GET as $getVarName=>$getVarValue) {
    $$getVarName = $getVarValue;
}


try {
    $DB = new DB($config);
} catch (Exception $e) {
    echo $e->getMessage();
    die();
}

if (isset($request)) {
    header('Content-Type: application/json; charset=UTF-8');
    switch ($request) {
        case 'tables':
            echo json_encode($DB->getTablesList());
            break;
        case 'fields':
            echo json_encode($DB->getFieldsList($id));
            break;
        case 'lines';
            echo json_encode($DB->getLinesList($id));
        default:

    }
    die();
}

if (isset($_FILES['file'])) {
    $uploadedFileArray = $_FILES['file'];
    if ($uploadedFileArray['error'] != UPLOAD_ERR_OK) {
        echo 'Ошибка загрузки файла';
    } else {
        if (!isset($tableId) || !isset($fieldId) || !isset($lineId)) {
            echo 'Должны быть выбраны Таблица, Поле, Строка';
        } else {
            $fileName = $uploadedFileArray['name'];
            $tmpFilePath = $uploadedFileArray['tmp_name'];
            $newFilePath = get_file_path($fieldId, $lineId, $fileName);
            $DB->setFileName($tableId, $fieldId, $lineId, $fileName);
            create_data_file_dirs($fieldId, $lineId, $fileName);

            if (!move_uploaded_file($tmpFilePath, $newFilePath)) {
                echo 'Ошибка загрузки файла';
            } else {
                echo 'Файл загружен ';
            }
        }

    }
} else {
    showMainPage();
}
