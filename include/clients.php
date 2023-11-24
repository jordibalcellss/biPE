<?php

if ($_SESSION['role'] != 'employee') { 

  $db = new DB();
  
  if (isset($_GET['action'])) {
    if ($_GET['action'] == 'edit') {
      echo '      <h2>'.clients."</h2>\n";
      require('include/clients-form.php');
    }
    if ($_GET['action'] == 'remove') {
      echo '      <h2>'.clients."</h2>\n";
      echo '      <div id="sec-menu"><a href="?module=clients&action=edit">'.
        add."</a></div>\n";
      $orphaned = getTasksFromClient($_GET['id']);
      $stmt = $db->prepare('DELETE FROM clients WHERE id = ?');
      $stmt->execute(array($_GET['id']));
      if ($stmt->rowCount() && count($orphaned)) {
        echo "      <ul id=\"arrow-listings\">\n";
        foreach ($orphaned as $task) {
          echo "        <li>".$task."</li>\n";
        }
        echo "      </ul>\n";
        echo '      <p class="margin-bot">'.are_now_orphans."</p>\n";
      }
    }
    if ($_GET['action'] == 'view') {
      $client = getClientDetails($_GET['id']);
      echo '      <h2>'.$client['name']."</h2>\n";
      echo '      <h3>'.client_data."</h3>\n";
      echo "      <p>\n";
      echo "        <b>".$client['name']."</b><br />\n";
      if (isset($client['address'])) {
        echo "        ".$client['address']."<br />\n";
      }
      if (isset($client['postcode'])) {
        $line = $client['postcode'];
      }
      if (isset($client['city'])) {
        $line = $line.' '.$client['city'];
      }
      if (isset($line)) {
        echo "        ".$line."<br />\n";
      }
      if (isset($client['vat_code'])) {
        echo "        ".$client['vat_code']."<br />\n";
      }
      if (isset($client['email']) || isset($client['phone'])) {
        echo "        <br />\n";
      }
      if (isset($client['email'])) {
        echo "        ".$client['email']."<br />\n";
      }
      if (isset($client['phone'])) {
        echo "        ".$client['phone']."<br />\n";
      }
      echo "      </p>\n";

      $tasks = getTasksFromClient($_GET['id']);
      if (count($tasks)) {
        echo "      <br />\n";
        echo '      <h3>'.tasks."</h3>\n";
        echo "      <ul id=\"arrow-listings\">\n";
        foreach ($tasks as $task) {
          echo "        <li>".$task."</li>\n";
        }
        echo "      </ul>\n";
      }

      require('include/template/base.php');
      exit;
    }
  }
  else {
    echo '      <h2>'.clients."</h2>\n";
    echo '      <div id="sec-menu"><a href="?module=clients&action=edit">'.
      add."</a></div>\n";
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
      $name = '<a href="?module=clients&action=view&id='.
        $record['id'].'">'.$record['name'].'</a>';
      $edit = '<a href="?module=clients&action=edit&id='.
        $record['id'].'">'.edit.'</a>';
      $remove = '&nbsp;&nbsp;<a href="?module=clients&action=remove&id='.
        $record['id'].'">'.remove.'</a>';
      echo "        <tr>\n";
      echo '          <td width="300">'.$name."</td>\n";
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
