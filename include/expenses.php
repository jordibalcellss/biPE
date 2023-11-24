<?php

$db = new DB();

if ($_SESSION['role'] == 'accountant') {

  if (isset($_GET['action'])) {
    if ($_GET['action'] == 'paid' || $_GET['action'] == 'unpaid') {
      if (isset($_GET['id'])) {
        $stmt = $db->prepare('
          UPDATE expenses_reclaim SET paid_back = ? WHERE id = ?');
        if ($_GET['action'] == 'paid') {
          $stmt->execute(array(1, $_GET['id']));
        }
        else if ($_GET['action'] == 'unpaid') {
          $stmt->execute(array(0, $_GET['id']));
        }
      }
    }
    else if ($_GET['action'] == 'export') {
      ob_clean();
      $stmt = $db->prepare("
        SELECT DATE_FORMAT(day,'%d-%m-%Y') AS day,
        (CASE
          WHEN tasks.code IS NULL OR tasks.code = '' THEN tasks.name
          ELSE CONCAT(tasks.code, \" \", tasks.name)
        END) AS task, expenses_reclaim.id, amount, description,
        (CASE
          WHEN nature = 'm' THEN '".mileage."'
          WHEN nature = 'r' THEN '".receipt."'
        END) AS nature, paid_back, user_id
        FROM expenses_reclaim LEFT JOIN tasks
        ON tasks.id = expenses_reclaim.task_id
        WHERE day BETWEEN ? AND ? AND paid_back LIKE ? AND user_id LIKE ?
        ORDER BY expenses_reclaim.id DESC
      ");
      $stmt->execute(array($_GET['day_from'], $_GET['day_to'],
        '%'.$_GET['paid_back'], '%'.$_GET['user_id']));
      $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

      header('Content-type: text/csv');
      header('Content-Disposition: attachment; filename='.str_replace(
        ' ', '_', personal_expenses).'_'.
        bin2hex(openssl_random_pseudo_bytes(2)).'.csv');
      
      foreach ($records as $record) {
        echo $record['user_id'].';'.$record['day'].';'.$record['task'].';'.
          $record['description'].';'.formatNumberP($record['amount']).';'.
          $record['nature'].';'.$record['paid_back']."\n";
      }
      exit;
    }
  }
  if (isset($_GET['submit'])) {
    //filter params sent
    if (strlen(trim($_GET['day_from'])) != 0) {
      if (!checkInputDate($_GET['day_from'])) {
        $day_from = '1970-01-01';
      }
      else {
        $day_from = checkInputDate($_GET['day_from']);
      }
    }
    else {
      $day_from = '1970-01-01';
    }
    if (strlen(trim($_GET['day_to'])) != 0) {
      if (!checkInputDate($_GET['day_to'])) {
        $day = new DateTime(null, new DateTimeZone(TIMEZONE));
        $day_to = $day->format('Y-m-d');
      }
      else {
        $day_to = checkInputDate($_GET['day_to']);
      }
    }
    else {
      $day = new DateTime(null, new DateTimeZone(TIMEZONE));
      $day_to = $day->format('Y-m-d');
    }
    $paid_back = $_GET['paid_back'];
    $user_id = $_GET['user_id'];
  }
  else {
    //default params
    $day_from = '1970-01-01';
    $day = new DateTime(null, new DateTimeZone(TIMEZONE));
    $day_to = $day->format('Y-m-d');
    $paid_back = '';
    $user_id = '';
  }
  $filter_params = "&user_id=$user_id&day_from=$day_from&day_to=$day_to".
    "&paid_back=$paid_back&submit=".filter;
  
  //prepare pagination
  if (!isset($_GET['page'])) {
    $page = 1;
  }
  else {
    $page = $_GET['page'];
  }

  $stmt = $db->prepare("SELECT COUNT(*) FROM expenses_reclaim WHERE day BETWEEN
    ? AND ? AND paid_back LIKE ? AND user_id LIKE ?");
  $stmt->execute(array($day_from, $day_to, '%'.$paid_back, '%'.$user_id));
  $num_records = $stmt->fetchColumn();

  $num_pages = ceil($num_records / RECORDS_PAGE);

  if ($num_pages <= 1) {
    $switcher = [];
  }
  else {
    if ($page > 1) {
      $switcher[] = "<a
      href=\"?module=expenses$filter_params&page=".($page - 1).'">'.
        next_page.'</a>';
    }
    if ($page < $num_pages) {
      $switcher[] = "<a
      href=\"?module=expenses$filter_params&page=".($page + 1).'">'.
        previous_page.'</a>';
    }
  }
  $first = RECORDS_PAGE * ($page - 1);

  $stmt = $db->prepare("
    SELECT DATE_FORMAT(day,'%d-%m-%Y') AS day,
    (CASE
      WHEN tasks.code IS NULL OR tasks.code = ''
      THEN SUBSTR(tasks.name, 1, 36)
      ELSE CONCAT('<span class=\"code-p\">', tasks.code,
      '</span> ', SUBSTR(tasks.name, 1, 29))
    END) AS task, expenses_reclaim.id, amount,
    SUBSTR(description, 1, 29) as description, nature, paid_back, user_id
    FROM expenses_reclaim LEFT JOIN tasks
    ON tasks.id = expenses_reclaim.task_id
    WHERE day BETWEEN ? AND ? AND paid_back LIKE ? AND user_id LIKE ?
    ORDER BY expenses_reclaim.id DESC LIMIT $first,".RECORDS_PAGE
  );
  $stmt->execute(array($day_from, $day_to, '%'.$paid_back, '%'.$user_id));
  
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
  //prepare filters and table
  echo '      <h2>'.personal_expenses."</h2>\n";
  echo "      <div class=\"four-quarters\">\n";
  echo '        <form id="expenses-filter"
          enctype="application/x-www-form-urlencoded" method="get"
          action="index.php?module=expenses">'."\n";
  echo '          <input name="module" type="hidden" value="expenses" />'."\n";
  echo '          <div><label for="user_id">'.who."</label></div>\n";
  echo "          <div><select name=\"user_id\">\n";
  echo "            <option value=\"\"></option>\n";
  $stmt = $db->prepare('SELECT DISTINCT user_id FROM expenses_reclaim ORDER
    BY user_id ASC');
  $stmt->execute();
  while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    echo '            <option value="'.$row[0].'">'.$row[0]."</option>\n";
  }
  echo "          </select></div>\n";
  echo '          <div><label for="day_from">'.from.' '.date.
      '</label></div>'."\n";
  echo '          <div><input name="day_from" type="text"
            class="shorter" value="" /></div>'."\n";
  echo '          <div><label for="day_to">'.to.' '.date.
      '</label></div>'."\n";
  echo '          <div><input name="day_to" type="text"
            class="shorter" value="" /></div>'."\n";
  echo '          <div><label for="paid_back">'.paid_back."</label></div>\n";
  echo "          <div><select name=\"paid_back\">\n";
  echo '            <option value=""></option>'."\n";
  echo '            <option value="1">'.yes.'</option>'."\n";
  echo '            <option value="0">'.no.'</option>'."\n";
  echo "          </select></div>\n";
  echo '          <input name="submit" type="submit" value="'.
    filter."\" />\n";
  echo '          <input name="reset" type="button" value="'.
    clear.
    "\" onclick=\"window.location.href='index.php?module=expenses';\" />\n";
  echo '          <input name="export" type="button" value="'.
    export_csv.
    "\" onclick=\"window.location.href='index.php?module=expenses".
    "&action=export".$filter_params."';\"/>\n";
  echo "        </form>\n";
  if (count($records) > 0) {
    echo "        <table>\n";
    echo "          <tr>\n";
    echo "            <th>".who."</th>\n";
    echo "            <th>".date."</th>\n";
    echo "            <th>".task."</th>\n";
    echo "            <th>".description."</th>\n";
    echo "            <th align=\"right\">".amount."</th>\n";
    echo "            <th align=\"right\">".nature."</th>\n";
    echo "            <th align=\"right\">".paid_back."</th>\n";
    echo "          </tr>\n";
    foreach ($records as $record) {
      if (!$record['paid_back']) {
        $paid_back = "<a href=\"?module=expenses$filter_params".
          "&action=paid&id=".$record['id'].
          "&page=$page\"><div class=\"lamp off\"></div></a>";
      }
      else {
        $paid_back = "<a href=\"?module=expenses$filter_params".
          "&action=unpaid&id=".$record['id'].
          "&page=$page\"><div class=\"lamp lit\"></div></a>";
      }
      if ($record['nature'] == 'm') {
        $nature = mileage;
      }
      else {
        $nature = receipt;
      }
      echo "          <tr>\n";
      echo '            <td width="80">'.$record['user_id']."</td>\n";
      echo '            <td width="100">'.$record['day']."</td>\n";
      echo '            <td width="250">'.$record['task']."</td>\n";
      echo '            <td width="200">'.$record['description']."</td>\n";
      echo '            <td align="right" width="60">'.
        formatNumberP($record['amount']).' '.currency."</td>\n";
      echo '            <td align="right" width="60">'.$nature."</td>\n";
      echo '            <td align="right" width="70">'.$paid_back."</td>\n";
      echo "          </tr>\n";
    }
    echo "        </table>\n";
  }
  else {
    echo '        <p>'.empty_search."</p>\n";
  }
  echo '        '.implode(' | ', $switcher)."\n";
  echo "      </div>\n";
}
else {
  if (isset($_GET['action'])) {
    if ($_GET['action'] == 'remove' && isset($_GET['id'])) {
      $stmt = $db->prepare('DELETE FROM expenses_reclaim WHERE id = ?');
      $stmt->execute(array($_GET['id']));
    }
  }
  if ($_POST) {
    if (strlen(trim($_POST['description'])) == 0) {
      $err[] = description_cannot_be_empty;
    }
    if (strlen(trim($_POST['amount'])) == 0) {
      $err[] = value_cannot_be_empty;
    }
    if (strlen(trim($_POST['day'])) == 0) {
      $err[] = date_cannot_be_empty;
    }
    else if (!checkInputDate($_POST['day'])) {
      $err[] = invalid_date;
    }
    if (!count($err)) { 
      $day = checkInputDate($_POST['day']);
      if ($_POST['nature'] == 'm') {
        $amount = trim($_POST['amount']) * MILEAGE_RATE;
      }
      else {
        $amount = formatNumberR(trim($_POST['amount']));
      }
    
      $stmt = $db->prepare('INSERT INTO expenses_reclaim
        (user_id, task_id, amount, description, nature, day)
        VALUES (?, ?, ?, ?, ?, ?)');
      $stmt->execute(array( $_SESSION['id'],
                            $_POST['task'],
                            $amount,
                            trim($_POST['description']),
                            $_POST['nature'],
                            $day
      ));
      if ($stmt->rowCount() == 1) {
        $err[] = add_success;
      }
    }
  }

?>
      <h2><?=declare_personal_expenses?></h2>
      <div class="one-quarter alpha">
        <form id="expenses" class="left"
          enctype="application/x-www-form-urlencoded"
          method="post"
          action="index.php?module=expenses">
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
  $day = new DateTime(null, new DateTimeZone(TIMEZONE));
?>
          </select></div>

          <div><label for="day"><?=date?>*</label></div>
          <div><input name="day" type="text" class="shorter"
            value="<?=$day->format('d-m-Y')?>" /></div>

          <div><label for="description"><?=description?>*</label></div>
          <div><input name="description" type="text" class="long"
            value="" /></div>

          <div><label for="nature"><?=nature?></label></div>
          <div><select name="nature">
            <option value="m"><?=mileage_value?></option>
            <option value="r"><?=receipt_value?></option>
          </select></div>

          <div><label for="amount"><?=value?>*</label></div>
          <div><input name="amount" type="text" class="shorter"
            value="" /></div>

          <input name="submit" type="submit" value="<?=log_task?>" />
<?php
  printMessages($err);
  echo "        </form>\n";
  echo "      </div>\n";

  //prepare pagination
  if (!isset($_GET['page'])) {
    $page = 1;
  }
  else {
    $page = $_GET['page'];
  }
  $stmt = $db->prepare("
    SELECT COUNT(*) FROM expenses_reclaim WHERE user_id=?");
  $stmt->execute(array($_SESSION['id']));
  $num_records = $stmt->fetchColumn();

  $num_pages = ceil($num_records / RECORDS_PAGE);

  if ($num_pages <= 1) {
    $switcher = [];
  }
  else {
    if ($page > 1) {
      $switcher[] = '<a href="?module=expenses&page='.($page - 1).'">'.
        next_page.'</a>';
    }
    if ($page < $num_pages) {
      $switcher[] = '<a href="?module=expenses&page='.($page + 1).'">'.
        previous_page.'</a>';
    }
  }
  $first = RECORDS_PAGE * ($page - 1);

  $stmt = $db->prepare("
    SELECT DATE_FORMAT(day,'%d-%m-%Y') AS day,
    (CASE
      WHEN tasks.code IS NULL OR tasks.code = ''
      THEN SUBSTR(tasks.name, 1, 36)
      ELSE CONCAT('<span class=\"code-p\">', tasks.code,
      '</span> ', SUBSTR(tasks.name, 1, 29))
    END) AS task, expenses_reclaim.id, amount,
    SUBSTR(description, 1, 29) as description, nature, paid_back
    FROM expenses_reclaim LEFT JOIN tasks
    ON tasks.id = expenses_reclaim.task_id
    WHERE user_id = ?
    ORDER BY expenses_reclaim.id DESC LIMIT $first,".RECORDS_PAGE
  );
  $stmt->execute(array($_SESSION['id']));
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (count($records) > 0) {
    //prepare table
    echo "      <div class=\"three-quarters\">\n";
    echo "        <table>\n";
    echo "          <tr>\n";
    echo "            <th>".date."</th>\n";
    echo "            <th>".task."</th>\n";
    echo "            <th>".description."</th>\n";
    echo "            <th align=\"right\">".amount."</th>\n";
    echo "            <th align=\"right\">".nature."</th>\n";
    echo "            <th align=\"right\">".paid_back."</th>\n";
    echo "            <th align=\"right\">".actions."</th>\n";
    echo "          </tr>\n";
    foreach ($records as $record) {
      if (!$record['paid_back']) {
        $paid_back = '<div class="lamp off"></div>';
        $remove = '<a href="?module=expenses&action=remove&id='.
          $record['id']."&page=$page\">".remove.'</a>';
      }
      else {
        $paid_back = '<div class="lamp lit"></div>';
        $remove = '';
      }
      if ($record['nature'] == 'm') {
        $nature = mileage;
      }
      else {
        $nature = receipt;
      }
      echo "          <tr>\n";
      echo '            <td width="100">'.$record['day']."</td>\n";
      echo '            <td width="250">'.$record['task']."</td>\n";
      echo '            <td width="200">'.$record['description']."</td>\n";
      echo '            <td align="right" width="60">'.
        formatNumberP($record['amount']).' '.currency."</td>\n";
      echo '            <td align="right" width="60">'.$nature."</td>\n";
      echo '            <td align="right" width="70">'.$paid_back."</td>\n";
      echo '            <td align="right" width="80">'.$remove."</td>\n";
      echo "          </tr>\n";
    }
    echo "        </table>\n";
  }
  else {
    echo '        <p>&nbsp;&nbsp;'.no_records_yet."</p>\n";
  }
  echo '        '.implode(' | ', $switcher)."\n";
  echo "      </div>\n";
}

?>
