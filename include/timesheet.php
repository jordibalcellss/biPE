<?php

if ($_SESSION['role'] != 'accountant') {
  if (isset($_GET['action'])) {
    if ($_GET['action'] == 'remove' && isset($_GET['id'])) { 
      removeRecord($_GET['id'],$_SESSION['id']);
    }
  }

  //prepare pagination
  if (!isset($_GET['page'])) {
    $page = 1;
  }
  else {
    $page = $_GET['page'];
  }
  $db = new DB();
  $stmt = $db->prepare("SELECT COUNT(*)
    FROM time_log WHERE user_id=? AND saved=1");
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
      WHEN task_id=1 THEN '".task_weekend_nothing."'
      WHEN task_id=2 THEN '".task_holiday."'
      WHEN task_id=3 THEN '".task_off_sick."'
      WHEN task_id=4 THEN '".task_leave."'
      ELSE
      CASE
        WHEN tasks.code IS NULL OR tasks.code=''
        THEN SUBSTR(tasks.name, 1, 38)
        ELSE CONCAT('<span class=\"code-p\">', tasks.code,
        '</span> ', SUBSTR(tasks.name, 1, 31))
      END
    END) AS task, duration, time_log.id AS record_id
    FROM time_log LEFT JOIN tasks
    ON tasks.id = time_log.task_id
    WHERE user_id=? AND saved=1
    ORDER BY time_log.day DESC, time_log.duration DESC
    LIMIT $first,".RECORDS_PAGE);

  $stmt->execute(array($_SESSION['id']));
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (count($records) > 0) {
    echo '      <h2>'.timesheet."</h2>\n";
    echo '      <div id="sec-menu"><a href="export.php">'.export_timesheet_csv.
      '</a> - <a href="?module=bulk-log">'.bulk_log."</a></div>\n";

    $stmt = $db->prepare("
      SELECT YEAR(day) AS year,
      (CASE
        WHEN task_id=2 THEN '".task_holiday."'
        WHEN task_id=3 THEN '".task_off_sick."'
        WHEN task_id=4 THEN '".task_leave."'
      END) AS task,
      (CASE
        WHEN task_id=4 THEN COUNT(*)
        ELSE SUM(duration)/".WORKDAY_DURATION."
      END) AS amount
      FROM time_log WHERE YEAR(day) IN
      (SELECT DISTINCT(YEAR(day)) FROM time_log
      WHERE user_id=? AND task_id=2 OR task_id=3
      OR task_id=4 AND saved=1) 
      AND user_id=? AND task_id=2 OR task_id=3
      OR task_id=4 AND saved=1 GROUP BY YEAR(day), task_id");
  
    $stmt->execute(array($_SESSION['id'], $_SESSION['id']));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
    //prepare table
    echo "      <div class=\"three-quarters alpha\">\n";
    echo "        <div class=\"two-quarters alpha\">\n";
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
        $duration = floor($record['duration']).' '.hours.
          decimalPartToFrac($record['duration']);
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
      echo '              <td width="100">'.$record['day']."</td>\n";
      echo '              <td width="120">'.$duration."</td>\n";
      echo '              <td width="250">'.$record['task']."</td>\n";
      echo '              <td width="70" align="right">'.$remove."</td>\n";
      echo "            </tr>\n";
      }
    echo "          </table>\n";
    echo '          '.implode(' | ', $switcher)."\n";
    
    echo '<br />';
    echo '<br />';
    require('include/libraries/array-to-texttable.php');
    mb_internal_encoding("utf-8");
    $week = getTimesheetOverview($_SESSION['id'], 'week');
    $month = getTimesheetOverview($_SESSION['id'], 'month');
    $year = getTimesheetOverview($_SESSION['id'], 'year');
    $renderer_week = new ArrayToTextTable($week['tasks']);
    $renderer_month = new ArrayToTextTable($month['tasks']);
    $renderer_year = new ArrayToTextTable($year['tasks']);
    $renderer_week->showHeaders('true');
    $renderer_month->showHeaders('true');
    $renderer_year->showHeaders('true');
    echo "<pre>\n";
    echo "\n\n";
    echo 'durant una setmana';
    echo "\n";
    $renderer_week->render();
    echo "\n"."total spent: ".floor($week['total_spent']).' '.
      hours.decimalPartToFrac($week['total_spent']);;
    echo "\n\n\n";
    echo 'durant un mes';
    echo "\n";
    $renderer_month->render();
    echo "\n"."total spent: ".floor($month['total_spent']).' '.
      hours.decimalPartToFrac($month['total_spent']);;
    echo "\n\n\n";
    echo 'durant un any';
    echo "\n";
    $renderer_year->render();
    echo "\n"."total spent: ".floor($year['total_spent']).' '.
      hours.decimalPartToFrac($year['total_spent']);;
    echo "\n\n";
    echo "\n\n";
    echo "\n\n";
    echo "\n</pre>\n";

    echo "        </div>\n";

    $year_prev = '';
    $to_close = false;
    for ($i = 0; $i < count($rows); $i++) {
      $to_close = !$to_close;
      if ($rows[$i]['year'] != $year_prev) {
        echo "        <div class=\"one-quarter omega box-year\">\n";
        echo "          <h1>".$rows[$i]['year']."</h1>\n";
        echo "          <ul>\n";
        if (LOCALE == 'ca') {
          echo "            <li>".$rows[$i]['task'].": ".str_replace('.', ',',
            rtrim($rows[$i]['amount'], '0.'))." ".days."</li>\n";
        }   
        else {
          echo "            <li>".$rows[$i]['task'].": ".
            rtrim($rows[$i]['amount'], '0.')." ".days."</li>\n";
        }
      }
      else {
        if (LOCALE == 'ca') {
          echo "            <li>".$rows[$i]['task'].": ".str_replace('.', ',',
            rtrim($rows[$i]['amount'], '0.'))." ".days."</li>\n";
        }
        else {
          echo "            <li>".$rows[$i]['task'].": ".
            rtrim($rows[$i]['amount'], '0.')." ".days."</li>\n";
        }
      }
      if ($to_close || $i == count($rows)-1) {
        echo "          </ul>\n";
        echo "        </div>\n";
      }
      $year_prev = $rows[$i]['year'];
    }
  echo "      </div>\n";
  }
  else {
    echo '      <h3>'.no_records_yet."</h3>\n";
    echo '      <div id="sec-menu"><a href="?module=bulk-log">'.
      bulk_log."</a></div>\n";
  }
}

?>
