<?php

if ($_SESSION['role'] != 'accountant') {
  
  echo '      <h2>'.periodic."</h2>\n";

  echo "      <div class=\"four-quarters\">\n";
  echo "        <div class=\"three-quarters alpha\">\n";
  
  $db = new DB();
  
  $stmt = $db->query("SELECT COUNT(*) FROM time_log WHERE saved");
  if ($stmt->fetchColumn()) {

    //weekly table
    $stmt = $db->query("
      SELECT DISTINCT(user_id) FROM time_log
      WHERE YEAR(day) = YEAR(CURDATE()) AND saved
      ORDER BY user_id ASC
    ");
    $user_ids = $stmt->fetchAll(PDO::FETCH_NUM);

    $i = 0;
    foreach ($user_ids as $user_id) {
      $period[$i][0] = $user_id[0];
      
      //iterate the current working week
      //and push times on a daily basis
      $start = new DateTime('last Sunday', new DateTimeZone(TIMEZONE));

      $j = 0;
      while ($j <= 4) {
        $stmt = $db->prepare("
          SELECT SUM(duration) FROM time_log
          WHERE user_id = ? AND day = ? AND saved
        ");
        $stmt->execute(array($user_id[0],
          $start->modify('+1 day')->format('Y-m-d')));
        $period[$i][] = $stmt->fetchColumn();
        $j++;
      }

      //weekly total
      $period[$i][] = array_sum(array_slice($period[$i], 1));

      //monthly total
      $stmt = $db->prepare("
        SELECT SUM(duration) FROM time_log
        WHERE user_id = ?
        AND MONTH(day) = MONTH(CURDATE()) 
        AND YEAR(day) = YEAR(CURDATE())
        AND saved
      ");
      $stmt->execute(array($user_id[0]));
      $period[$i][] = $stmt->fetchColumn();

      //yearly total
      $stmt = $db->prepare("
        SELECT SUM(duration) FROM time_log
        WHERE user_id = ?
        AND YEAR(day) = YEAR(CURDATE()) AND saved
      ");
      $stmt->execute(array($user_id[0]));
      $total = $stmt->fetchColumn();
      $period[$i][] = $total;

      //difference
      $stmt = $db->prepare("
        SELECT SUM(duration) FROM time_log
        WHERE user_id = ? AND task_id = 6
        AND YEAR(day) = YEAR(CURDATE()) AND saved
      ");
      $stmt->execute(array($user_id[0]));
      $unpaid = $stmt->fetchColumn();

      $today = new DateTime(null, new DateTimeZone(TIMEZONE));
      $working_h = countWorkingDays($today->format('Y')) * WORKDAY_DURATION;

      $period[$i][] = $total - $working_h - $unpaid;
      
      //unpaid
      $period[$i][] = $unpaid;

      $i++;
    }

    echo '          <h3>'.weekly_view."</h3>\n";
    //prepare table
    echo "          <table class=\"period\">\n";
    echo "            <tr>\n";
    echo "              <th></th>\n";
    echo "              <th>".mon."</th>\n";
    echo "              <th>".tue."</th>\n";
    echo "              <th>".wed."</th>\n";
    echo "              <th>".thu."</th>\n";
    echo "              <th>".fri."</th>\n";
    echo "              <th>".week."</th>\n";
    echo "              <th>".month."</th>\n";
    echo "              <th>".year."</th>\n";
    echo "              <th>".overtime."</th>\n";
    echo "              <th>".task_unpaid."</th>\n";
    echo "            </tr>\n";
    foreach ($period as $p) {
      echo "            <tr>\n";
      echo '              <td width="90">'.$p[0]."</td>\n";
      echo '              <td width="65">'.
        getHValue($p[1], true)."</td>\n";
      echo '              <td width="65">'.
        getHValue($p[2], true)."</td>\n";
      echo '              <td width="65">'.
        getHValue($p[3], true)."</td>\n";
      echo '              <td width="65">'.
        getHValue($p[4], true)."</td>\n";
      echo '              <td width="65">'.
        getHValue($p[5], true)."</td>\n";
      echo '              <td width="80">'.
        getHValue($p[6], true)."</td>\n";
      echo '              <td width="70">'.
        getHValue($p[7], true)."</td>\n";
      echo '              <td width="90">'.
        getHValue($p[8])."</td>\n";
      echo '              <td width="100">'.
        getHValue($p[9])."</td>\n";
      echo '              <td>'.
        getHValue($p[10], true)."</td>\n";
      echo "            </tr>\n";
    }
    echo "          </table>\n";
    
    unset($period);

    //yearly tables
    $stmt = $db->query("
      SELECT DISTINCT(YEAR(day)) FROM time_log WHERE saved
      ORDER BY YEAR(day) DESC
    ");
    $years = $stmt->fetchAll(PDO::FETCH_NUM);
    
    foreach ($years as $year) {
      $stmt = $db->prepare("
        SELECT DISTINCT(user_id) FROM time_log WHERE YEAR(day) = ? AND saved
        ORDER BY user_id ASC
      ");
      $stmt->execute(array($year[0]));
      $user_ids = $stmt->fetchAll(PDO::FETCH_NUM);
      
      $i = 0;
      foreach ($user_ids as $user_id) {
        $period[$i]['user_id'] = $user_id[0];
        
        //tasks
        $stmt = $db->prepare("
          SELECT SUM(duration)/".WORKDAY_DURATION." FROM time_log
          WHERE user_id = ? AND task_id > 10 AND YEAR(day) = ? AND saved
        ");
        $stmt->execute(array($user_id[0], $year[0]));
        $period[$i]['tasks'] = $stmt->fetchColumn();
        
        //holidays
        $stmt = $db->prepare("
          SELECT SUM(duration)/".WORKDAY_DURATION." FROM time_log
          WHERE user_id = ? AND task_id = 2 AND YEAR(day) = ? AND saved
        ");
        $stmt->execute(array($user_id[0], $year[0]));
        $period[$i]['holidays'] = $stmt->fetchColumn();

        //off_sick
        $stmt = $db->prepare("
          SELECT SUM(duration)/".WORKDAY_DURATION." FROM time_log
          WHERE user_id = ? AND task_id = 3 AND YEAR(day) = ? AND saved
        ");
        $stmt->execute(array($user_id[0], $year[0]));
        $period[$i]['off_sick'] = $stmt->fetchColumn();
        
        //leave
        $stmt = $db->prepare("
          SELECT COUNT(*) FROM time_log
          WHERE user_id = ? AND task_id = 4 AND YEAR(day) = ? AND saved
        ");
        $stmt->execute(array($user_id[0], $year[0]));
        $period[$i]['leave'] = $stmt->fetchColumn();

        //off
        $stmt = $db->prepare("
          SELECT SUM(duration)/".WORKDAY_DURATION." FROM time_log
          WHERE user_id = ? AND task_id = 5 AND YEAR(day) = ? AND saved
        ");
        $stmt->execute(array($user_id[0], $year[0]));
        $period[$i]['off'] = $stmt->fetchColumn();

        //unpaid
        $stmt = $db->prepare("
          SELECT SUM(duration)/".WORKDAY_DURATION." FROM time_log
          WHERE user_id = ? AND task_id = 6 AND YEAR(day) = ? AND saved
        ");
        $stmt->execute(array($user_id[0], $year[0]));
        $period[$i]['unpaid'] = $stmt->fetchColumn();
        
        $i++;
      }

      echo '          <h3>'.$year[0]."</h3>\n";
      //prepare table
      echo "          <table class=\"period\">\n";
      echo "            <tr>\n";
      echo "              <th></th>\n";
      echo "              <th>".tasks."</th>\n";
      echo "              <th>".task_holiday."</th>\n";
      echo "              <th>".task_off_sick."</th>\n";
      echo "              <th>".task_leave."</th>\n";
      echo "              <th>".task_off."</th>\n";
      echo "              <th>".task_unpaid."</th>\n";
      echo "              <th>".total."</th>\n";
      echo "              <th>".overtime."</th>\n";
      echo "            </tr>\n";
      $working_d = countWorkingDays($year[0]);
      foreach ($period as $p) {
        $total = array_sum(array_slice($p, 1));
        $overtime = $total - $working_d; 
        echo "            <tr>\n";
        echo '              <td width="90">'.$p['user_id']."</td>\n";
        echo '              <td width="120">'.
          getDValue($p['tasks'])."</td>\n";
        echo '              <td width="90">'.
          getDValue($p['holidays'])."</td>\n";
        echo '              <td width="120">'.
          getDValue($p['off_sick'])."</td>\n";
        echo '              <td width="60">'.
          getDValue($p['leave'])."</td>\n";
        echo '              <td width="85">'.
          getDValue($p['off'])."</td>\n";
        echo '              <td width="110">'.
          getDValue($p['unpaid'])."</td>\n";
        echo '              <td width="90">'.
          getDValue($total)."</td>\n";
        echo '              <td>'.
          getDvalue($overtime)."</td>\n";
        echo "            </tr>\n";
      }
      echo "          </table>\n";
    }
  }
  else {
    echo '      <h3>'.no_records_yet."</h3>\n";
  }
  
  echo "        </div>\n";

  echo "        <div class=\"one-quarter omega\">\n";
  echo '          <p class="advice">'.periodic_advice."</p>\n";
  echo "        </div>\n";
  echo "      </div>\n";

  echo "      <div class=\"clear\"></div>\n";
}

?>
