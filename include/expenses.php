<?php

$db = new DB();

if ($_SESSION['role'] == 'accountant') {
  if (isset($_GET['action']) && isset($_GET['id'])) {
    $stmt = $db->prepare('UPDATE expenses_reclaim SET paid_back=? WHERE id=?');
    if ($_GET['action'] == 'paid') {
      $stmt->execute(array(1, $_GET['id']));
    }
    else if ($_GET['action'] == 'unpaid') {
      $stmt->execute(array(0, $_GET['id']));
    }
  }
  $stmt = $db->prepare("SELECT DATE_FORMAT(day,'%d-%m-%Y') AS day,
                        (CASE
                          WHEN tasks.code IS NULL OR tasks.code=''
                          THEN SUBSTR(tasks.name, 1, 36)
                          ELSE CONCAT('<span class=\"code-p\">', tasks.code,
                          '</span> ', SUBSTR(tasks.name, 1, 29))
                        END) AS task, expenses_reclaim.id, amount,
                        SUBSTR(description, 1, 29) as description,
                        nature, paid_back, user_id
                        FROM expenses_reclaim LEFT JOIN tasks
                        ON tasks.id = expenses_reclaim.task_id
                        ORDER BY day DESC");
  $stmt->execute(array($_SESSION['id']));
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (count($records) > 0) {
    //prepare table
    echo '      <h2>'.personal_expenses."</h2>\n";
    echo "      <div class=\"three-quarters alpha\">\n";
    echo "        <table>\n";
    echo "          <tr>\n";
    echo "            <th>".who."</th>\n";
    echo "            <th>".date."</th>\n";
    echo "            <th>".task."</th>\n";
    echo "            <th>".description."</th>\n";
    echo "            <th align=\"right\">".value."</th>\n";
    echo "            <th align=\"right\">".nature."</th>\n";
    echo "            <th align=\"right\">".paid_back."</th>\n";
    echo "          </tr>\n";
    foreach ($records as $record) {
      if (!$record['paid_back']) {
        $paid_back = '<a href="?module=expenses&action=paid&id='.
          $record['id'].'">'.no.'</a>';
      }
      else {
        $paid_back = '<a href="?module=expenses&action=unpaid&id='.
          $record['id'].'">'.yes.'</a>';
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
        formatNumberP($record['amount'])."</td>\n";
      echo '            <td align="right" width="60">'.$nature."</td>\n";
      echo '            <td align="right" width="70">'.$paid_back."</td>\n";
      echo "          </tr>\n";
    }
    echo "        </table>\n";
  }
  else {
    echo '        <h3>'.no_records_yet."</h3>\n";
  }
echo "      </div>\n";
}
else {
  if (isset($_GET['action'])) {
    if ($_GET['action'] == 'remove' && isset($_GET['id'])) {
      $stmt = $db->prepare('DELETE FROM expenses_reclaim WHERE id=?');
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
      $amount = formatNumberR(trim($_POST['amount']));
      $day = checkInputDate($_POST['day']);
    
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

  $day = new DateTime(null, new DateTimeZone(TIMEZONE));

?>
      <h2><?=declare_personal_expenses?></h2>
      <div class="one-quarter alpha">
        <form id="expenses" enctype="application/x-www-form-urlencoded"
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

  $stmt = $db->prepare("SELECT DATE_FORMAT(day,'%d-%m-%Y') AS day,
                        (CASE
                          WHEN tasks.code IS NULL OR tasks.code=''
                          THEN SUBSTR(tasks.name, 1, 36)
                          ELSE CONCAT('<span class=\"code-p\">', tasks.code,
                          '</span> ', SUBSTR(tasks.name, 1, 29))
                        END) AS task, expenses_reclaim.id, amount,
                        SUBSTR(description, 1, 29) as description,
                        nature, paid_back
                        FROM expenses_reclaim LEFT JOIN tasks
                        ON tasks.id = expenses_reclaim.task_id
                        WHERE user_id=?
                        ORDER BY day DESC");
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
    echo "            <th align=\"right\">".value."</th>\n";
    echo "            <th align=\"right\">".nature."</th>\n";
    echo "            <th align=\"right\">".paid_back."</th>\n";
    echo "            <th align=\"right\">".actions."</th>\n";
    echo "          </tr>\n";
    foreach ($records as $record) {
      if (!$record['paid_back']) {
        $paid_back = no;
      }
      else {
        $paid_back = yes;
      }
      if ($record['nature'] == 'm') {
        $nature = mileage;
      }
      else {
        $nature = receipt;
      }
      $remove = '<a href="?module=expenses&action=remove&id='.
        $record['id'].'">'.remove.'</a>';
      echo "          <tr>\n";
      echo '            <td width="100">'.$record['day']."</td>\n";
      echo '            <td width="250">'.$record['task']."</td>\n";
      echo '            <td width="200">'.$record['description']."</td>\n";
      echo '            <td align="right" width="60">'.
        formatNumberP($record['amount'])."</td>\n";
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
  echo "      </div>\n";
}

?>
