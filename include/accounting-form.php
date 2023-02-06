<?php

if ($_GET['action'] == 'edit') {
  $action = edit;
  $db = new DB();
  if ($_GET['type'] == 'quotation') {
    $stmt = $db->prepare('SELECT task_id FROM quotations where id=?');
  }
  else {
    $stmt = $db->prepare('SELECT task_id FROM invoices where id=?');
  }
  $stmt->execute(array($_GET['id']));
  $task_id = $stmt->fetchColumn();
}
else {
  $task_id = $_GET['id'];
}

if ($_GET['type'] == 'invoice') {
  $type = invoice;
}
else {
  $type = quotation;
}

if ($_POST) {
  if (strlen(trim($_POST['amount'])) == 0) {
    $err[] = amount_cannot_be_empty;
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
    $db = new DB();
    if ($_GET['type'] == 'quotation') {
      if ($_GET['action'] == 'edit') {
        $stmt = $db->prepare('UPDATE quotations SET description=?, amount=?, nature=?, day=? WHERE id=?');
        $stmt->execute(array(trim($_POST['description']), $amount, $_POST['nature'], $day, $_GET['id']));
      }
      else {
        $stmt = $db->prepare('INSERT INTO quotations (task_id, description, amount, nature, day) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute(array($task_id, trim($_POST['description']), $amount, $_POST['nature'], $day));     
      }
    }
    else {
      if (!isset($_POST['settled'])) {
        $settled = 0;
      }
      else {
        $settled = 1;
      }
      if ($_GET['action'] == 'edit') {
        $stmt = $db->prepare('UPDATE invoices SET description=?, amount=?, settled=?, nature=?, day=? WHERE id=?');
        $stmt->execute(array(trim($_POST['description']), $amount, $settled, $_POST['nature'], $day, $_GET['id']));
      }
      else {
        $stmt = $db->prepare('INSERT INTO invoices (task_id, description, amount, settled, nature, day) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute(array($_GET['id'], trim($_POST['description']), $amount, $settled, $_POST['nature'], $day));
      }
    }
    if ($stmt->rowCount() == 1) {
      $err[] = edit_success.' - <a href="index.php?module=accounting&action=list&id='.$task_id.'">'.back.'</a>';
    }
  }
}

if ($_GET['action'] == 'edit') {
  $db = new DB();
  if ($_GET['type'] == 'quotation') {
    $stmt = $db->prepare('SELECT * FROM quotations WHERE id=?');
  }
  else {
    $stmt = $db->prepare('SELECT * FROM invoices WHERE id=?');
  }
  $stmt->execute(array($_GET['id']));
  $document = $stmt->fetch(PDO::FETCH_NUM);
  $id = $document[0];
  $amount = formatNumberP($document[2], false);
  if ($_GET['type'] == 'invoice') {
    if ($document[6] == 1) {
      $checked = ' checked';
    }
    else {
      $checked = '';
    }
  }
  if ($document[4] == 'i') {
    $income_selected = ' selected';
    $expense_selected = '';
  }
  else {
    $income_selected = '';
    $expense_selected = ' selected';
  }
  $day = new DateTime($document[5], new DateTimeZone(TIMEZONE));
}
else {
  $action = add;
  for ($i = 0; $i <= 4; $i++) {
    $document[$i] = '';
  }
  $checked = '';
  $amount = '';
  $income_selected = ' selected';
  $expense_selected = '';
  $day = new DateTime(null, new DateTimeZone(TIMEZONE));
  $id = $_GET['id'];
}

?>
      <h2><?=accounting?></h2>
      <h3><?=getTaskName($task_id, 'h3')?></h3>
      <h4><?=$action?> <?=$type?></h4>
      <form id="accounting" enctype="application/x-www-form-urlencoded" method="post" action="index.php?module=accounting&action=<?=$_GET['action']?>&id=<?=$id?>&type=<?=$_GET['type']?>">
        <div><label for="nature"><?=nature?></label></div>
        <div><select name="nature">
          <option value="i"<?=$income_selected?>><?=income?></option>
          <option value="e"<?=$expense_selected?>><?=expense?></option>
        </select></div>

        <div><label for="description"><?=description?></label></div>
        <div><input name="description" type="text" class="long" value="<?=$document[3]?>" /></div>

        <div><label for="day"><?=date?>*</label></div>
        <div><input name="day" type="text" class="shorter" value="<?=$day->format('d-m-Y')?>" /></div>

        <div><label for="amount"><?=amount?>*</label></div>
        <div><input name="amount" type="text" class="shorter" value="<?=$amount?>" /></div>
<?php
if ($_GET['type'] == 'invoice') {
  echo "\n".'        <div><input name="settled" type="checkbox" value="'.$document[4].'"'.$checked.' /><label for="settled">'.settled."</label></div>\n";
}
?>

        <input name="submit" type="submit" value="<?=$action?>" />
<?php
printMessages($err);
echo "      </form>\n";

?>
