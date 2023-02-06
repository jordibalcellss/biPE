<?php

if ($_POST) {
  if (strlen(trim($_POST['name'])) == 0) {
    $err[] = name_cannot_be_empty;
  }
  else {
    $db = new DB();
    if ($_GET['id'] != '') {
      $stmt = $db->prepare('UPDATE clients SET name=?, address=?, city=?, postcode=?, vat_code=?, email=?, phone=? WHERE id=?');
      $stmt->execute(array( trim($_POST['name']),
                            trim($_POST['address']),
                            trim($_POST['city']),
                            trim($_POST['postcode']),
                            trim($_POST['vat_code']),
                            trim($_POST['email']),
                            trim($_POST['phone']),
                            $_GET['id'])
      );
      if ($stmt->rowCount() == 1) {
        $err[] = edit_success.' - <a href="index.php?module=clients">'.back.'</a>';
      }
    }
    else {
      $stmt = $db->prepare('INSERT INTO clients (name, address, city, postcode, vat_code, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?)');
      $stmt->execute(array( trim($_POST['name']),
                            trim($_POST['address']),
                            trim($_POST['city']),
                            trim($_POST['postcode']),
                            trim($_POST['vat_code']),
                            trim($_POST['email']),
                            trim($_POST['phone'])
      ));
      if ($stmt->rowCount() == 1) {
        $err[] = add_success.' - <a href="index.php?module=clients">'.back.'</a>';
      }
    }
  }
}

if (isset($_GET['id'])) {
  if ($_GET['id'] != '') {
    $action = edit;
    $db = new DB();
    $stmt = $db->prepare('SELECT * FROM clients WHERE id=?');
    $stmt->execute(array($_GET['id']));
    $client = $stmt->fetch(PDO::FETCH_NUM);
  }
  else {
    $action = add;
    for ($i = 0; $i <= 7; $i++) {
      $client[$i] = '';
    }
  }
}
else {
  $action = add;
  for ($i = 0; $i <= 7; $i++) {
    $client[$i] = '';
  }
}

?>
      <h2><?=$action?> <?=client?></h2>
      <form id="clients" enctype="application/x-www-form-urlencoded" method="post" action="index.php?module=clients&action=edit&id=<?=$client[0]?>">
        <div><label for="name"><?=name?>*</label></div>
        <div><input name="name" type="text" value="<?=$client[1]?>" /></div>

        <div><label for="address"><?=address?></label></div>
        <div><input name="address" type="text" class="long "value="<?=$client[2]?>" /></div>

        <div><label for="city"><?=city?></label></div>
        <div><input name="city" type="text" value="<?=$client[3]?>" /></div>

        <div><label for="postcode"><?=postcode?></label></div>
        <div><input name="postcode" type="text" class="shorter" value="<?=$client[4]?>" /></div>

        <div><label for="vat_code"><?=vat_code?></label></div>
        <div><input name="vat_code" type="text" class="shorter" value="<?=$client[7]?>" /></div>

        <div><label for="email"><?=email?></label></div>
        <div><input name="email" type="text" value="<?=$client[5]?>" /></div>
        
        <div><label for="phone"><?=phone?></label></div>
        <div><input name="phone" type="text" class="shorter" value="<?=$client[6]?>" /></div>

        <input name="submit" type="submit" value="<?=$action?>" />
<?php
printMessages($err);
echo "      </form>\n";

?>
