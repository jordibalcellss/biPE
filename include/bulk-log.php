<?php

if ($_SESSION['role'] != 'accountant') {

  if ($_POST) {
    if (strlen(trim($_POST['duration'])) == 0) {
      $err[] = value_cannot_be_empty;
    }
    else if (trim($_POST['duration']) >= 1000) {
      $err[] = bulk_log_excess;
    }
    if (!count($err)) {
      $duration = formatNumberR(trim($_POST['duration']));

      $db = new DB();
      $stmt = $db->prepare('INSERT INTO time_log
        (user_id, task_id, duration, day, saved)
        VALUES (?, ?, ?, ?, ?)');
      $stmt->execute(array( $_SESSION['id'], $_POST['task'], $duration,
        $_POST['target'], true
      ));
      if ($stmt->rowCount() == 1) {
        $err[] = add_success.' - <a href="index.php?module=timesheet">'.
          timesheet.'</a>';
      }
    }
  }

  if (!getLastRecord($_SESSION['id'])) {
    $target_date = new DateTime(null, new DateTimeZone(TIMEZONE));
  }
  else {
    $target_date = getLastRecord($_SESSION['id']);
  }

?>
      <h2><?=bulk_log?></h2>
      <div class="one-quarter alpha">
        <form id="bulk-log" enctype="application/x-www-form-urlencoded"
        method="post"
        action="index.php?module=bulk-log">
        <div><label for="task"><?=task?></label></div>
        <div><select name="task">
<?php
  $tasks = getTasks('editable');
  foreach ($tasks as $task) {
    if (strlen($task['code']) == 0) {
      $entry = $task['name'];
    }
    else {
      $entry = $task['code'].' '.$task['name'];
    }
    echo '            <option value="'.$task['id']."\">$entry</option>\n";
  }
?>
          </select></div>

          <div><label for="duration"><?=hours_word?></label></div>
          <div><input name="duration" type="text" class="shorter"
            value="" /></div>

          <input name="target" type="hidden"
            value="<?=$target_date->format('Y-m-d')?>" />

          <input name="submit" type="submit" value="<?=log_task?>" />
<?php
  printMessages($err);
?>
        </form>
      </div>
      <div class="one-quarter">
        <p class="advice"><?=bulk_log_advice?></p>
      </div>
<?php

}

?>
