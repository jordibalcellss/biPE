<?php

$db = new DB();

if ($_POST['submit'] == log_task) {
  //log entries
  
  //if duration is zero declare as weekend/nothing regardless of task selection
  if ($_POST['duration'] == 0) {
    $task = 1;
  }
  else {
    $task = $_POST['task'];
  }
  
  //if task is weekend/nothing or leave declare as no time spent
  //regardless of selection
  if ($_POST['task'] == 1 || $_POST['task'] == 4) {
    $duration = 0;
  }
  else {
    $duration = $_POST['duration'];
  }
  
  //detect a 0 entry
  $stmt = $db->prepare('
    SELECT COUNT(*) FROM time_log WHERE user_id = ?
    AND NOT saved AND duration = 0
  ');
  $stmt->execute(array($_SESSION['id']));
  $count_0 = $stmt->fetchColumn();

  //count value entries
  $stmt = $db->prepare('
    SELECT COUNT(*) FROM time_log WHERE user_id = ?
    AND NOT saved AND duration > 0
  ');
  $stmt->execute(array($_SESSION['id']));
  $count_non_0 = $stmt->fetchColumn();
  
  //log if there is no 0 entry
  if (!$count_0) {
    //we are adding a value entry or there are no value entries
    if ($duration > 0 || $count_non_0 == 0) {
      //and the task not repeated
      $stmt = $db->prepare('
        SELECT COUNT(*) FROM time_log WHERE user_id = ?
        AND NOT saved AND task_id = ?'
      );
      $stmt->execute(array($_SESSION['id'],$task));
      $repeated = $stmt->fetchColumn();
      if (!$repeated) {
        $stmt = $db->prepare('
          INSERT INTO time_log (user_id, task_id, day, duration)
          VALUES (?, ?, ?, ?)
        ');
        $stmt->execute(array($_SESSION['id'], $task, $_POST['target'],
          $duration));
      }
    }
  }
}
else if ($_POST['submit'] == log_discard) {
  //delete entries
  $stmt = $db->prepare('
    DELETE FROM time_log WHERE user_id = ?
    AND NOT saved ORDER BY id DESC LIMIT 1
  ');
  $stmt->execute(array($_SESSION['id']));
}
else if ($_POST['submit'] == log_save_next_day) {
  //save entries
  $stmt = $db->prepare('
    UPDATE time_log SET saved = 1 WHERE user_id = ? AND NOT saved');
  $stmt->execute(array($_SESSION['id']));  
}

?>
