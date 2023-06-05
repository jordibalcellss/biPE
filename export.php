<?php

require 'config.php';
require 'locale/'.LOCALE.'.php';
ini_set('error_reporting',ERROR_REPORTING);
ini_set('display_errors',DISPLAY_ERRORS);

session_start();
if (!isset($_SESSION['id'])) {
  header("Location: login.php");
}

require 'include/functions.php';

$db = new DB();
$stmt = $db->prepare("SELECT DATE_FORMAT(day,'%d-%m-%Y') AS day,
                      (CASE
                        WHEN task_id=1 THEN '".task_weekend_nothing."'
                        WHEN task_id=2 THEN '".task_holiday."'
                        WHEN task_id=3 THEN '".task_off_sick."'
                        WHEN task_id=4 THEN '".task_leave."'
                        ELSE
                        CASE
                          WHEN tasks.code IS NULL OR tasks.code='' THEN tasks.name
                          ELSE CONCAT(tasks.code, \" \", tasks.name)
                        END
                      END) AS task, duration
                      FROM time_log LEFT JOIN tasks ON tasks.id = time_log.task_id
                      WHERE user_id=? AND saved=1
                      ORDER BY time_log.day DESC, time_log.duration DESC");
$stmt->execute(array($_SESSION['id']));
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-type: text/csv');
header('Content-Disposition: attachment; filename='.$_SESSION['id'].'.csv');

foreach ($records as $record) {
  if ($record['duration'] > 0) {
    $duration = formatNumberP($record['duration']);
  }
  else {
    $duration = '';
  }
  echo $record['day'].';'.$record['task'].';'.$duration."\n";
}

?>
