<?php

if ($_SESSION['role'] != 'accountant') {
  
  echo '      <h2>'.periodic."</h2>\n";

  echo "      <div class=\"four-quarters\">\n";
  echo "        <div class=\"three-quarters alpha\">\n";
  
  $db = new DB();
  
  $stmt = $db->query("SELECT COUNT(*) FROM time_log WHERE saved");
  if ($stmt->fetchColumn()) {
    
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
          SELECT SUM(duration) FROM time_log
          WHERE user_id = ? AND task_id > 10 AND YEAR(day) = ? AND saved
        ");
        $stmt->execute(array($user_id[0], $year[0]));
        $period[$i]['tasks'] = $stmt->fetchColumn();
        
        //holidays
        $stmt = $db->prepare("
          SELECT SUM(duration) FROM time_log
          WHERE user_id = ? AND task_id = 2 AND YEAR(day) = ? AND saved
        ");
        $stmt->execute(array($user_id[0], $year[0]));
        $period[$i]['holidays'] = $stmt->fetchColumn();

        //off_sick
        $stmt = $db->prepare("
          SELECT SUM(duration) FROM time_log
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
        $period[$i]['leave'] = $stmt->fetchColumn() * WORKDAY_DURATION;
        
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
      echo "              <th>".total."</th>\n";
      echo "              <th>".difference."</th>\n";
      echo "            </tr>\n";
      $working_days = countWorkingDays($year[0]);
      foreach ($period as $p) {
        $total = array_sum(array_slice($p, 1));
        $difference = $working_days * WORKDAY_DURATION - $total; 
        echo "            <tr>\n";
        echo '              <td width="90">'.$p['user_id']."</td>\n";
        echo '              <td width="120">'.
          getHValue($p['tasks'])."</td>\n";
        echo '              <td width="100">'.
          getHValue($p['holidays'])."</td>\n";
        echo '              <td width="120">'.
          getHValue($p['off_sick'])."</td>\n";
        echo '              <td width="100">'.
          getHValue($p['leave'])."</td>\n";
        echo '              <td width="110">'.
          getHValue($total)."</td>\n";
        echo '              <td>'.
          getHvalue($difference)."</td>\n";
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
