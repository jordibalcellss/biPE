<?php

if ($_SESSION['role'] != 'employee') {

  $db = new DB();

  if (isset($_GET['action'])) {
    if ($_GET['action'] == 'edit' || $_GET['action'] == 'add') {
      require('include/accounting-form.php');
    }
    else if ($_GET['action'] == 'remove') {
      if (removeAccountingEntry($_GET['id'], $_GET['type'])) {
        echo '      <div>'.remove_success.
        ' - <a href="index.php?module=tasks">'.back.'</a></div>'."\n";
      }
    }
    else if ($_GET['action'] == 'list') {
      echo "      <h2>".getTaskName($_GET['id'], 'h2')."</h2>\n";
      echo '      <div id="sec-menu">'.
        '<a href="?module=accounting&action=add&id='.
        $_GET['id'].'&type=quotation">'.add.' '.quotation.
        '</a> - <a href="?module=accounting&action=add&id='.$_GET['id'].
        '&type=invoice">'.add.' '.invoice."</a></div>\n";

      $quotations = true;
      $invoices = true;

      $stmt = $db->prepare('SELECT id, amount, description, nature
        FROM quotations WHERE task_id = ? ORDER BY nature');
      $stmt->execute(array($_GET['id']));
      $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if (count($records) > 0) {
        //prepare table
        echo "      <table>\n";
        echo "        <tr>\n";
        echo "          <th>".quotation."</th>\n";
        echo "          <th align=\"right\">".amount."</th>\n";
        echo "          <th align=\"right\">".nature."</th>\n";
        echo "          <th align=\"right\">".actions."</th>\n";
        echo "        </tr>\n";
        foreach ($records as $record) {
          if ($record['nature'] == 'i') {
            $nature = income;
          } 
          else {
            $nature = expense;
          }
          $edit = '<a href="?module=accounting&action=edit&id='.
            $record['id'].'&type=quotation">'.edit.'</a>';
          $remove = '&nbsp;&nbsp;<a href="?module=accounting&action=remove'.
            '&id='.$record['id'].'&type=quotation">'.remove.'</a>';
          echo "        <tr>\n";
          echo '          <td width="320">'.$record['description']."</td>\n";
          echo '          <td align="right" width="60">'.
            formatNumberP($record['amount'])."</td>\n";
          echo '          <td align="right" width="80">'.$nature."</td>\n";
          echo '          <td align="right" width="120">'.$edit.$remove.
            "</td>\n";
          echo "        </tr>\n";
        }
        echo "      </table>\n";
      }
      else {
        $quotations = false;
      }

      $stmt = $db->prepare('SELECT id, amount, description, nature, sent,
        settled FROM invoices WHERE task_id = ? ORDER BY nature');
      $stmt->execute(array($_GET['id']));
      $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if (count($records) > 0) {
        //prepare table
        echo "      <table class=\"separated\">\n";
        echo "        <tr>\n";
        echo "          <th>".invoice."</th>\n";
        echo "          <th align=\"right\">".amount."</th>\n";
        echo "          <th align=\"right\">".nature."</th>\n";
        echo "          <th align=\"right\">".sent."</th>\n";
        echo "          <th align=\"right\">".settled."</th>\n";
        echo "          <th align=\"right\">".actions."</th>\n";
        echo "        </tr>\n";
        foreach ($records as $record) {
          if (!$record['sent']) {
            $sent = '<div class="lamp off"></div>';
          }
          else {
            $sent = '<div class="lamp lit"></div>';
          }
          if (!$record['settled']) {
            $settled = '<div class="lamp off"></div>';
          }
          else {
            $settled = '<div class="lamp lit"></div>';
          }
          if ($record['nature'] == 'i') {
            $nature = income;
          }
          else {
            $nature = expense;
          }
          $edit = '<a href="?module=accounting&action=edit&id='.
            $record['id'].'&type=invoice">'.edit.'</a>';
          $remove = '&nbsp;&nbsp;<a href="?module=accounting&action=remove'.
            '&id='.$record['id'].'&type=invoice">'.remove.'</a>';
          echo "        <tr>\n";
          echo '          <td width="320">'.$record['description']."</td>\n";
          echo '          <td align="right" width="60">'.
            formatNumberP($record['amount'])."</td>\n";
          echo '          <td align="right" width="80">'.$nature."</td>\n";
          echo '          <td align="right" width="80">'.$sent."</td>\n";
          echo '          <td align="right" width="90">'.$settled."</td>\n";
          echo '          <td align="right" width="120">'.$edit.$remove.
            "</td>\n";
          echo "        </tr>\n";
        }
        echo "      </table>\n";
      }
      else {
        $invoices = false;
      }

      if (!$quotations && !$invoices) {
        echo '      <p>'.no_records_yet."</p>\n";
      }
    }
  }
}

?>
