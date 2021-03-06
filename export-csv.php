<?php

require_once "classes/models/Test.class.php";

session_start();

require "includes/db.php";


function put_data_in_csv(array &$array, $delimiter, $enclosure, $escapechar)
{
   if (count($array) == 0) {
     return null;
   }
   ob_start();
   $df = fopen("php://output", 'a');
   foreach ($array as $row) {
      fputcsv($df, $row, $delimiter, $enclosure, $escapechar);
   }
   fclose($df);
   return ob_get_clean();
}

function download_send_headers($filename) {
    // disable caching
    $now = gmdate("D, d M Y H:i:s");
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
    header("Last-Modified: {$now} GMT");

    // force download
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");

    // disposition / encoding on response body
    header("Content-Disposition: attachment;filename={$filename}");
    header("Content-Transfer-Encoding: binary");
}

// Get test ID

$test_id = $_GET["id"] ?? "0";
$test_id = (int)$test_id;
$delimiter = $_POST["csv-delimiter"] ?? ",";
$enclosure = $_POST["csv-enclosure"] ?? '"';
$escapechar = $_POST["csv-escapechar"] ?? "\\";

$delimiter = strlen($delimiter) !== 1 ? "," : $delimiter;
$enclosure = strlen($enclosure) !== 1 ? '"' : $enclosure;
$escapechar = strlen($escapechar) !== 1 ? "\\" : $escapechar;

if (!$test_id) {
    $view = new View("not_found", "Not Found");
    $view->send();
}

// Get test info

$test = new Test($db);
$test->id = $test_id;

if (!$test->fetch()) {
    $view = new View("not_found", "Not Found");
    $view->send();
}

if (!$test->fetchQuestions()) {
    $view = new View("database_error", "Database error!");
    $view->send();
}


$arr = array();

foreach ($test->questions as $ind_q => $question) {
    $row = array();
    $row[] = $test->title;
    $row[] = $question->type;
    $row[] = "";
    $row[] = "";
    $row[] = $question->text;

    if (!$question->answers || empty($question->answers)) {
        $row[] = 0;  // satisfying the min column limit of 6
    } else {
        foreach ($question->answers as $ind_a => $answer) {
            if ($answer->is_correct) {
                $row[] = 100;
            } else{
                $row[] = 0;
            }
            $row[] = $answer->text;
        }
    }
    $arr[] = $row;
}

download_send_headers($test->title . "_exported.csv");
echo put_data_in_csv($arr, $delimiter, $enclosure, $escapechar);
