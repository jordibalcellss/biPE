<?php

$target_date = getTargetDate($_SESSION['id']);
$today = new DateTime(null, new DateTimeZone(TIMEZONE));

//we will display the form once the day is over
if ($target_date->format('Y-m-d') == $today->format('Y-m-d')) {
  echo '      <h3>'.log_not_needed."</h3>\n";
}
else {
  //TODO: consider timezone behaviour
  $target_date_fmt = new IntlDateFormatter(LOCALE, null, null, TIMEZONE, null,
    'EEEE d MMMM');
  echo '      <h2>'.strtolower($target_date_fmt->format($target_date)).
    "</h2>\n";
  echo '      <form id="log" enctype="application/x-www-form-urlencoded"
    method="post" action="index.php?module=log">'."\n";
  echo '        <div><label for="task">'.log_what_tasks_today."</label>
    </div>\n";
  echo "        <div><select name=\"task\">\n";
  
  $tasks = getTasks('active');
  foreach ($tasks as $task) {
    if (strlen($task['code']) == 0) {
      $entry = $task['name'];
    }
    else {
      $entry = $task['code'].' '.$task['name'];
    }
    echo '          <option value="'.$task['id']."\">$entry</option>\n";
  }
  echo "        </select></div>\n";
  echo '        <div><label for="duration">'.log_for_how_long."</label>
    </div>\n";
  echo "        <div><select name=\"duration\">\n";
  echo "          <option value=\"0\"></option>\n";

  $durations = getDurations();
  foreach ($durations as $duration) {
    echo '          <option value="'.$duration.'">'.floor($duration).' '.
      hours.decimalPartToFrac($duration)."</option>\n";
  }
  echo "        </select></div>\n";
  echo '        <input name="target" type="hidden"
    value="'.$target_date->format('Y-m-d')."\" />\n";
  
  $records = getUnsavedRecords($_SESSION['id']);
  echo "        <ul id=\"unsaved-records\">\n";
  foreach ($records as $record) {
    echo '          <li>'.$record."</li>\n";
  }
  echo "        </ul>\n";
  echo '        <input name="submit" type="submit" value="'.log_task."\" />\n";
  if (count($records) > 0) {
    echo '        <input name="submit" type="submit"
      value="'.log_discard."\" />\n";
    echo '        <input name="submit" type="submit"
      value="'.log_save_next_day."\" />\n";
  }
  echo "      </form>\n";
}

?>
