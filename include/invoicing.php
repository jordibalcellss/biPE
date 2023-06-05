<?php

$db = new DB();

if ($_SESSION['role'] == 'accountant') {
  if (isset($_GET['action']) && isset($_GET['id'])) {
    if (strpos($_GET['action'], 'sent') !== false) {
      $stmt = $db->prepare('UPDATE invoices SET sent=? WHERE id=?');
      if ($_GET['action'] == 'sent') {
        $stmt->execute(array(1, $_GET['id']));
      }
      else if ($_GET['action'] == 'unsent') {
        $stmt->execute(array(0, $_GET['id']));
      }
    }
    else if (strpos($_GET['action'], 'settled') !== false) {
      $stmt = $db->prepare('UPDATE invoices SET settled=? WHERE id=?');
      if ($_GET['action'] == 'settled') {
        $stmt->execute(array(1, $_GET['id']));
      }
      else if ($_GET['action'] == 'unsettled') {
        $stmt->execute(array(0, $_GET['id']));
      }
    }
  }
  $stmt = $db->prepare("SELECT DATE_FORMAT(day,'%d-%m-%Y') AS day,
                        (CASE
                          WHEN tasks.code IS NULL OR tasks.code=''
                          THEN SUBSTR(tasks.name, 1, 36)
                          ELSE CONCAT('<span class=\"code-p\">', tasks.code,
                          '</span> ', SUBSTR(tasks.name, 1, 29))
                        END) AS task, invoices.id, amount,
                        SUBSTR(description, 1, 50) as description,
                        sent, settled, clients.name AS client
                        FROM invoices
                        LEFT JOIN tasks
                        ON tasks.id = invoices.task_id
                        LEFT JOIN clients
                        ON clients.id = tasks.client_id
                        WHERE nature='i' ORDER BY invoices.day DESC");
  $stmt->execute();
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (count($records) > 0) {
    //prepare table
    echo '      <h2>'.invoicing."</h2>\n";
    echo '      <div id="sec-menu"><a href="?module=clients">'.
      clients."</a></div>\n";
    echo "      <div>\n";
    echo "        <table>\n";
    echo "          <tr>\n";
    echo "            <th>".date."</th>\n";
    echo "            <th>".task."</th>\n";
    echo "            <th>".description."</th>\n";
    echo "            <th>".client."</th>\n";
    echo "            <th align=\"right\">".amount."</th>\n";
    echo "            <th align=\"right\">".sent."</th>\n";
    echo "            <th align=\"right\">".settled."</th>\n";
    echo "          </tr>\n";
    foreach ($records as $record) {
      if (!$record['sent']) {
        $sent = '<a href="?module=invoicing&action=sent&id='.
          $record['id'].'">'.no.'</a>';
      }
      else {
        $sent = '<a href="?module=invoicing&action=unsent&id='.
          $record['id'].'">'.yes.'</a>';
      }
      if (!$record['settled']) {
        $settled = '<a href="?module=invoicing&action=settled&id='.
          $record['id'].'">'.no.'</a>';
      }
      else {
        $settled = '<a href="?module=invoicing&action=unsettled&id='.
          $record['id'].'">'.yes.'</a>';
      }
      echo "          <tr>\n";
      echo '            <td width="100">'.$record['day']."</td>\n";
      echo '            <td width="250">'.$record['task']."</td>\n";
      echo '            <td width="325">'.$record['description']."</td>\n";
      echo '            <td width="270">'.$record['client']."</td>\n";
      echo '            <td align="right" width="60">'.
        formatNumberP($record['amount'])."</td>\n";
      echo '            <td align="right" width="70">'.$sent."</td>\n";
      echo '            <td align="right" width="80">'.$settled."</td>\n";
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
