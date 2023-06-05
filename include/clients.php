<?php

$db = new DB();

echo '      <h2>'.clients."</h2>\n";

if ($_SESSION['role'] == 'accountant') {
  $stmt = $db->prepare('SELECT id FROM clients ORDER BY name ASC');
  $stmt->execute();
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (count($records) > 0) {
    $i = 0;
    foreach ($records as $record) {
      if ($i % 4 == 0 || $i == 0) {
        $offset_class = ' alpha';
        $div = "      <div class=\"clear\"></div>\n";
      }
      else {
        $offset_class = '';
        $div = '';
      }
      $client = getClientDetails($record['id']);
      echo $div;
      echo "      <div class=\"one-quarter box-client$offset_class\">\n";
      echo "        <p>\n";
      foreach ($client as $attr) {
        echo '          '.$attr."<br />\n";
      }
      echo "        </p>\n";
      echo "      </div>\n";
      $i++;
    }
  echo "      <div class=\"clear\"></div>\n";
  }
  else {
    echo '      <p>'.no_records_yet."</p>\n";
  }
}
else {
  echo '      <div id="sec-menu"><a href="?module=clients&action=edit">'.
    add."</a></div>\n";

  //removal action
  if (isset($_GET['action']) && isset($_GET['id'])) {
    if ($_GET['action'] = 'remove') {
      $orphaned = getTasksFromClient($_GET['id']);
      $stmt = $db->prepare('DELETE FROM clients WHERE id=?');
      $stmt->execute(array($_GET['id']));
      if ($stmt->rowCount() && count($orphaned)) {
        echo '      <div id="under-sec-menu-messages">'."\n";
        echo '        '.implode(' ', $orphaned).' '.are_now_orphans."\n";
        echo "      </div>\n";
      }
    }
  }

  //prepare table
  $stmt = $db->prepare('SELECT id, name FROM clients ORDER BY name ASC');
  $stmt->execute();
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (count($records) > 0) {
    echo "      <table>\n";
    echo "        <tr>\n";
    echo "          <th>".name."</th>\n";
    echo "          <th align=\"right\">".actions."</th>\n";
    echo "        </tr>\n";
    foreach ($records as $record) {
      $edit = '<a href="?module=clients&action=edit&id='.
        $record['id'].'">'.edit.'</a>';
      $remove = '&nbsp;&nbsp;<a href="?module=clients&action=remove&id='.
        $record['id'].'">'.remove.'</a>';
      echo "        <tr>\n";
      echo '          <td width="300">'.$record['name']."</td>\n";
      echo '          <td align="right">'.$edit.$remove."</td>\n";
      echo "        </tr>\n";
    }
    echo "      </table>\n";
  }
  else {
    echo '      <p>'.no_records_yet."</p>\n";
  }
}

?>
