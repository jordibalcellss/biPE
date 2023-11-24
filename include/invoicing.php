<?php

if ($_SESSION['role'] != 'employee') {

  echo '      <h2>'.invoicing."</h2>\n";
  
  $db = new DB();

  if (isset($_GET['action']) && isset($_GET['id'])) {
    if (strpos($_GET['action'], 'sent') !== false) {
      $stmt = $db->prepare('UPDATE invoices SET sent = ? WHERE id = ?');
      if ($_GET['action'] == 'sent') {
        $stmt->execute(array(1, $_GET['id']));
      }
      else if ($_GET['action'] == 'unsent') {
        $stmt->execute(array(0, $_GET['id']));
        
        //forcefully mark as unsettled as well
        $stmt = $db->prepare('UPDATE invoices SET settled = 0 WHERE id = ?');
        $stmt->execute(array($_GET['id']));
      }
    }
    else if (strpos($_GET['action'], 'settled') !== false) {
      $stmt = $db->prepare('UPDATE invoices SET settled = ? WHERE id = ?');
      if ($_GET['action'] == 'settled') {
        $stmt->execute(array(1, $_GET['id']));
        
        //forcefully mark as sent as well
        $stmt = $db->prepare('UPDATE invoices SET sent = 1 WHERE id = ?');
        $stmt->execute(array($_GET['id']));
      }
      else if ($_GET['action'] == 'unsettled') {
        $stmt->execute(array(0, $_GET['id']));
      }
    }
    else if ($_GET['action'] == 'export') {
      //TODO as per expenses
    }
    else if ($_GET['action'] == 'edit' || $_POST) {
      if ($_POST) {
        if (strlen(trim($_POST['day'])) == 0) {
          $err[] = date_cannot_be_empty;
        }
        else if (!checkInputDate($_POST['day'])) {
          $err[] = invalid_date;
        }
        if (!count($err)) {
          $day = checkInputDate($_POST['day']);
          $stmt = $db->prepare('
            UPDATE invoices SET day = ?, acc_id = ? WHERE id = ?
          ');
          $stmt->execute(array($day, trim($_POST['acc_id']), $_GET['id']));
          if ($stmt->rowCount() == 1) {
            $err[] = edit_success;
          }
        }
      }
      
      $stmt = $db->prepare('
        SELECT id, acc_id, day FROM invoices WHERE id = ?
      ');
      $stmt->execute(array($_GET['id']));
      $document = $stmt->fetch(PDO::FETCH_NUM);
      $day = new DateTime($document[2], new DateTimeZone(TIMEZONE));

      echo '      <h3>'.edit.' '.invoice."</h3>\n";

      echo '      <form id="invoicing" class="on-top"
        enctype="application/x-www-form-urlencoded" method="post"
        action="index.php?module=invoicing&action=edit&id='.
        $document[0].'">'."\n";
        echo '        <div><label for="day">'.date.'</label></div>'."\n";
        echo '        <div><input name="day" type="text"
          class="shorter" value="'.$day->format('d-m-Y').'" /></div>'."\n";
        echo '        <div><label for="acc_id">'.number.'</label></div>'."\n";
        echo '        <div><input name="acc_id" type="text"
          class="shorter" value="'.$document[1].'" /></div>'."\n";
        echo '        <input name="submit" type="submit" value="'.
          edit."\" />\n";
        echo '        <input name="done" type="button" value="'.
          done.
          "\" onclick=\"window.location.href='index.php?module=invoicing".
          "';\" />\n";
      printMessages($err);
      echo "      </form>\n";
    }
  }
  if (isset($_GET['submit'])) {
    //TODO as per expenses
  }

  //prepare pagination
  //TODO as per expenses

  $stmt = $db->prepare("
    SELECT DATE_FORMAT(day,'%d-%m-%Y') AS day,
    (CASE
      WHEN tasks.code IS NULL OR tasks.code = ''
      THEN SUBSTR(tasks.name, 1, 36)
      ELSE CONCAT('<span class=\"code-p\">', tasks.code, '</span> ',
        SUBSTR(tasks.name, 1, 12))
    END) AS task, invoices.id, amount,
    SUBSTR(description, 1, 30) AS description, sent, settled,
    SUBSTR(clients.name, 1, 18) AS client, acc_id, clients.id AS client_id
    FROM invoices
    LEFT JOIN tasks
    ON tasks.id = invoices.task_id
    LEFT JOIN clients
    ON clients.id = tasks.client_id
    WHERE nature = 'i' ORDER BY invoices.day DESC"
  );
  $stmt->execute();
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (count($records) > 0) {
    //prepare table
    echo "      <div>\n";
    echo "        <table>\n";
    echo "          <tr>\n";
    echo "            <th>".date."</th>\n";
    echo "            <th>".number."</th>\n";
    echo "            <th>".client."</th>\n";
    echo "            <th>".description."</th>\n";
    echo "            <th>".task."</th>\n";
    echo "            <th align=\"right\">".amount."</th>\n";
    echo "            <th align=\"right\">".file."</th>\n";
    echo "            <th align=\"right\">".sent."</th>\n";
    echo "            <th align=\"right\">".settled."</th>\n";
    echo "            <th align=\"right\">".actions."</th>\n";
    echo "          </tr>\n";
    foreach ($records as $record) {
      if (!$record['sent']) {
        $sent = '<a href="?module=invoicing&action=sent&id='.
          $record['id'].'"><div class="lamp off"></div></a>';
      }
      else {
        $sent = '<a href="?module=invoicing&action=unsent&id='.
          $record['id'].'"><div class="lamp lit"></div></a>';
      }
      if (!$record['settled']) {
        $settled = '<a href="?module=invoicing&action=settled&id='.
          $record['id'].'"><div class="lamp off"></div></a>';
      }
      else {
        $settled = '<a href="?module=invoicing&action=unsettled&id='.
          $record['id'].'"><div class="lamp lit"></div></a>';
      }
      $edit = '<a href="?module=invoicing&action=edit&id='.
        $record['id'].'">'.edit.'</a>';
      $client = '<a href="?module=clients&action=view&id='.
        $record['client_id'].'">'.$record['client'].'</a>';
      echo "          <tr>\n";
      echo '            <td width="100">'.$record['day']."</td>\n";
      echo '            <td width="80">'.$record['acc_id']."</td>\n";
      echo '            <td width="170">'.$client."</td>\n";
      echo '            <td width="225">'.$record['description']."</td>\n";
      echo '            <td width="150">'.$record['task']."</td>\n";
      echo '            <td align="right" width="60">'.
        formatNumberP($record['amount'])."</td>\n";
      echo '            <td align="right" width="60">'.''."</td>\n";
      echo '            <td align="right" width="70">'.$sent."</td>\n";
      echo '            <td align="right" width="80">'.$settled."</td>\n";
      echo '            <td align="right" width="80">'.$edit."</td>\n";
      echo "          </tr>\n";
    }
    echo "        </table>\n";
  }
  else {
    echo '        <h3>'.no_records_yet."</h3>\n";
  }
echo "      </div>\n";
}

?>
