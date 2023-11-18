<?php

if ($_SESSION['role'] != 'accountant') {

  $db = new DB();

  if (isset($_GET['action'])) {
    if ($_GET['action'] == 'remove' && isset($_GET['id'])) { 
      removeRecord($_GET['id'],$_SESSION['id']);
    }
    else if ($_GET['action'] == 'export') {
      ob_clean();
      $stmt = $db->prepare("    
        SELECT DATE_FORMAT(day,'%d-%m-%Y') AS day,
        (CASE
          WHEN task_id = 1 THEN '".task_weekend_nothing."'
          WHEN task_id = 2 THEN '".task_holiday."'
          WHEN task_id = 3 THEN '".task_off_sick."'
          WHEN task_id = 4 THEN '".task_leave."'
          WHEN task_id = 5 THEN '".task_off."'
          WHEN task_id = 6 THEN '".task_unpaid."'
          ELSE
          CASE
            WHEN tasks.code IS NULL OR tasks.code = '' THEN tasks.name
            ELSE CONCAT(tasks.code, \" \", tasks.name)
          END
        END) AS task, duration
        FROM time_log LEFT JOIN tasks
        ON tasks.id = time_log.task_id
        WHERE user_id = ? AND saved
        ORDER BY time_log.day DESC, time_log.duration DESC
      ");
      $stmt->execute(array($_SESSION['id']));
      $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

      header('Content-type: text/csv');
      header('Content-Disposition: attachment; filename='.str_replace(
        ' ', '_', timesheet).'_'.
        bin2hex(openssl_random_pseudo_bytes(2)).'.csv');

      foreach ($records as $record) {
        if ($record['duration'] > 0) {
          $duration = formatNumberP($record['duration']);
        }
        else {
          $duration = '';
        }
        echo $record['day'].';'.$record['task'].';'.$duration."\n";
      }
      exit;
    }
  }

  //prepare pagination
  if (!isset($_GET['page'])) {
    $page = 1;
  }
  else {
    $page = $_GET['page'];
  }
  $stmt = $db->prepare("SELECT COUNT(*)
    FROM time_log WHERE user_id = ? AND saved");
  $stmt->execute(array($_SESSION['id']));
  $num_records = $stmt->fetchColumn();

  $num_pages = ceil($num_records / RECORDS_PAGE);

  if ($num_pages <= 1) {
    $switcher = [];
  }
  else {
    if ($page > 1) {
      $switcher[] = '<a href="?module=timesheet&page='.($page - 1).'">'.
        next_page.'</a>';
    }
    if ($page < $num_pages) {
      $switcher[] = '<a href="?module=timesheet&page='.($page + 1).'">'.
        previous_page.'</a>';
    }
  }
  $first = RECORDS_PAGE * ($page - 1);

  $stmt = $db->prepare("
    SELECT DATE_FORMAT(day,'%d-%m-%Y') AS day,
    (CASE
      WHEN task_id = 1 THEN '".task_weekend_nothing."'
      WHEN task_id = 2 THEN '".task_holiday."'
      WHEN task_id = 3 THEN '".task_off_sick."'
      WHEN task_id = 4 THEN '".task_leave."'
      WHEN task_id = 5 THEN '".task_off."'
      WHEN task_id = 6 THEN '".task_unpaid."'
      ELSE
      CASE
        WHEN tasks.code IS NULL OR tasks.code = '' THEN tasks.name
        ELSE CONCAT('<span class=\"code-p\">', tasks.code,
        '</span> ', tasks.name)
      END
    END) AS task, duration, time_log.id AS record_id
    FROM time_log LEFT JOIN tasks
    ON tasks.id = time_log.task_id
    WHERE user_id = ? AND saved
    ORDER BY time_log.day DESC, time_log.duration DESC
    LIMIT $first,".RECORDS_PAGE
  );

  echo '      <h2>'.timesheet."</h2>\n";
  
  $stmt->execute(array($_SESSION['id']));
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  if (count($records) > 0) {
    echo '      <div id="sec-menu"><a href="?module=timesheet&action=export">'.export_timesheet_csv.
      '</a> - <a href="?module=bulk-log">'.bulk_log."</a></div>\n";

    //retrieve details for the right column
    //years with holidays, off sick, leave or off records
    $stmt = $db->prepare("
      SELECT DISTINCT(YEAR(day)) FROM time_log
      WHERE user_id = ? AND task_id < 11 AND task_id != 1 AND saved
    ");
    $stmt->execute(array($_SESSION['id']));
    $summary = array();
    //can't use a fetch() loop here because we are already iterating
    //a PDOStatement inside
    $years = $stmt->fetchAll(PDO::FETCH_NUM);
    foreach ($years as $year) {
      //retrieve amounts translated to days time
      
      //holidays
      $stmt = $db->prepare("
        SELECT SUM(duration)/".WORKDAY_DURATION." FROM time_log
        WHERE user_id = ? AND task_id = 2 AND YEAR(day) = ? AND saved
      ");
      $stmt->execute(array($_SESSION['id'], $year[0]));
      $amount = $stmt->fetchColumn();
      if ($amount){
        $summary[$year[0]][] =
          array('task' => task_holiday, 'amount' => $amount);
      }
      
      //off_sick
      $stmt = $db->prepare("
        SELECT SUM(duration)/".WORKDAY_DURATION." FROM time_log
        WHERE user_id = ? AND task_id = 3 AND YEAR(day) = ? AND saved
      ");
      $stmt->execute(array($_SESSION['id'], $year[0]));
      $amount = $stmt->fetchColumn();
      if ($amount) {
        $summary[$year[0]][] =
          array('task' => task_off_sick, 'amount' => $amount);
      }
      
      //leave
      $stmt = $db->prepare("
        SELECT COUNT(*) FROM time_log
        WHERE user_id = ? AND task_id = 4 AND YEAR(day) = ? AND saved
      ");
      $stmt->execute(array($_SESSION['id'], $year[0]));
      $amount = $stmt->fetchColumn();
      if ($amount) {
        $summary[$year[0]][] =
          array('task' => task_leave, 'amount' => $amount);
      }
      
      //off
      $stmt = $db->prepare("
        SELECT SUM(duration)/".WORKDAY_DURATION." FROM time_log
        WHERE user_id = ? AND task_id = 5 AND YEAR(day) = ? AND saved
      ");
      $stmt->execute(array($_SESSION['id'], $year[0]));
      $amount = $stmt->fetchColumn();
      if ($amount) {
        $summary[$year[0]][] =
          array('task' => task_off, 'amount' => $amount);
      }
    }

    //prepare table
    echo "      <div class=\"four-quarters alpha\">\n";
    echo "        <div class=\"three-quarters alpha\">\n";
    echo "          <table>\n";
    echo "            <tr>\n";
    echo "              <th>".day."</th>\n";
    echo "              <th>".duration."</th>\n";
    echo "              <th>".in_what."</th>\n";
    echo "              <th align=\"right\">".actions."</th>\n";
    echo "            </tr>\n";
    foreach ($records as $record) {
      if ($record['duration'] == 0) {
        //using duration=0 to print out empty strings instead of task keys
        //because there might be other tasks admitting zero time
        $duration = '';
      }
      else {
        $duration = getHValue($record['duration']);
      }
      if (getLastRecord($_SESSION['id']) == new DateTime($record['day'],
        new DateTimeZone(TIMEZONE))) {
        $remove = '<a href="?module=timesheet&action=remove&id='.
          $record['record_id']."&page=$page\">".remove.'</a>';
      }
      else {
        $remove = '';
      }
      echo "            <tr>\n";
      echo '              <td width="130">'.$record['day']."</td>\n";
      echo '              <td width="150">'.$duration."</td>\n";
      echo '              <td width="350">'.$record['task']."</td>\n";
      echo '              <td width="70" align="right">'.$remove."</td>\n";
      echo "            </tr>\n";
      }
    echo "          </table>\n";
    echo '          <div class="switcher">'.implode(' | ', $switcher).
      "</div>\n";

    $intervals = array(0 => 'week', 1 => 'month', 2 => 'year');
    
    foreach ($intervals as $interval) {
      $tasks = getTimesheetOverview($_SESSION['id'], $interval);
      if (count($tasks)) {
        echo '          <h3>'.constant('this_'.$interval)."</h3>\n";
        echo "          <ul id=\"unsaved-records\">\n";
        foreach ($tasks['tasks'] as $task) {
          echo "            <li>".$task['name'].": ".
            getHValue($task['spent']).
            //" (".$task['percent'].")</li>\n";
            "</li>\n";
        }
        echo "            <li><b>".total."</b>: ".
          getHValue($tasks['total_spent'])."</li>\n";
        echo "          </ul>\n";
      }
    }

    echo "        </div>\n";

    foreach ($summary as $year_num => $year) {
      echo "        <div class=\"one-quarter omega box-year\">\n";
      echo "          <h1>$year_num</h1>\n";
      echo "          <ul>\n";
      foreach ($year as $entry) {
        echo "            <li>".$entry['task'].": ".
          formatNumberP($entry['amount'], false, true, 1)." ".days."</li>\n";
      }
      echo "          </ul>\n";
      echo "        </div>\n";
    }
  echo "      </div>\n";
  echo "      <div class=\"clear\"></div>\n";
  }
  else {
    echo '      <h3>'.no_records_yet."</h3>\n";
    echo '      <div id="sec-menu"><a href="?module=bulk-log">'.
      bulk_log."</a></div>\n";
  }
}

?>
