<?php

if ($_POST) {
  if (strlen(trim($_POST['name'])) == 0) {
    $err[] = name_cannot_be_empty;
  }
  if (!count($err)) {
    if (!isset($_POST['active'])) {
      $active = 0;
    }
    else {
      $active = 1;
    }
    if (trim($_POST['rate']) == '') {
      $rate = 0;
    }
    else {
      $rate = formatNumberR(trim($_POST['rate']));
    }
    $db = new DB();
    if ($_GET['id'] != '') {
      $stmt = $db->prepare('UPDATE tasks SET code=?, name=?, category_id=?, client_id=?, rate=?, active=? WHERE id=?');
      $stmt->execute(array( trim($_POST['code']),
                            trim($_POST['name']),
                            $_POST['category'],
                            $_POST['client'],
                            $rate,
                            $active,
                            $_GET['id'])
      );
      if ($stmt->rowCount() == 1) {
        $err[] = edit_success.' - <a href="index.php?module=tasks">'.back.'</a>';
      }
    }
    else {
      $stmt = $db->prepare('INSERT INTO tasks (code, name, category_id, client_id, rate, active) VALUES (?, ?, ?, ?, ?, ?)');
      $stmt->execute(array( trim($_POST['code']),
                            trim($_POST['name']),
                            $_POST['category'],
                            $_POST['client'],
                            $rate,
                            $active)
      );
      if ($stmt->rowCount() == 1) {
        $err[] = add_success.' - <a href="index.php?module=tasks">'.back.'</a>';
      }
    }
  }
}

if (isset($_GET['id'])) {
  if ($_GET['id'] != '') {
    $action = edit;
    $db = new DB();
    $stmt = $db->prepare('SELECT * FROM tasks WHERE id=?');
    $stmt->execute(array($_GET['id']));
    $task = $stmt->fetch(PDO::FETCH_NUM);
    if ($task[6] == 1) {
      $checked = ' checked';
    }
    else {
      $checked = '';
    }
    if ($task[5] != '') {
      $rate = formatNumberP($task[5], true);
    }
    else {
      $rate = '';
    }
  }
  else {
    $action = add;
    for ($i = 0; $i <= 8; $i++) {
      $task[$i] = '';
    }
    $checked = ' checked';
    $rate = '';
  }
}
else {
  $action = add;
  for ($i = 0; $i <= 8; $i++) {
    $task[$i] = '';
  }
  $checked = ' checked';
  $rate = '';
}

?>
      <h2><?=$action?> <?=task?></h2>
      <form id="tasks" enctype="application/x-www-form-urlencoded" method="post" action="index.php?module=tasks&action=edit&id=<?=$task[0]?>">
        <div><label for="code"><?=code?></label></div>
        <div><input name="code" type="text" class="shorter" value="<?=$task[3]?>" /></div>

        <div><label for="name"><?=name?>*</label></div>
        <div><input name="name" type="text" class="long" value="<?=$task[4]?>" /></div>

        <div><label for="category"><?=category?></label></div>
        <div><select name="category">
          <option value="0"></option>
<?php
$db = new DB();
$stmt = $db->prepare('SELECT * FROM categories ORDER BY name ASC');
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
  if ($row[0] == $task[1]) {
    $selected = ' selected';
  }
  else {
    $selected = '';
  }
  echo '          <option value="'.$row[0]."\"$selected>".$row[1]."</option>\n";
}
?>
        </select></div>

        <div><label for="client"><?=client?></label></div>
        <div><select name="client">
          <option value="0"></option>
<?php
$db = new DB();
$stmt = $db->prepare('SELECT id, name FROM clients ORDER BY name ASC');
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
  if ($row[0] == $task[2]) {
    $selected = ' selected';
  }
  else {
    $selected = '';
  }
  echo '          <option value="'.$row[0]."\"$selected>".$row[1]."</option>\n";
}
?>
        </select></div>

        <div><label for="rate"><?=rate?> <?=rate_math?></label></div>
        <div><input name="rate" type="text" class="shorter" value="<?=$rate?>" /></div>

        <div><input name="active" type="checkbox" value=""<?=$checked?> /><label for="active"><?=active?></label></div>

        <input name="submit" type="submit" value="<?=$action?>" />
<?php
printMessages($err);
echo "      </form>\n";

?>
