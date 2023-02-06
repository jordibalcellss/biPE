<?php

echo '      <h2>'.clients."</h2>\n";
echo '      <div id="sec-menu"><a href="?module=clients&action=edit">'.add."</a></div>\n";

$db = new DB();
$stmt = $db->prepare('SELECT id, name FROM clients');
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($records) > 0) {
  //prepare table
  echo "      <table>\n";
  echo "        <tr>\n";
  echo "          <th>".name."</th>\n";
  echo "          <th align=\"right\">".actions."</th>\n";
  echo "        </tr>\n";
  foreach ($records as $record) {
    $edit = '&nbsp;&nbsp;<a href="?module=clients&action=edit&id='.$record['id'].'">'.edit.'</a>';
    echo "        <tr>\n";
    echo '          <td width="300">'.$record['name']."</td>\n";
    echo '          <td align="right">'.$edit."</td>\n";
    echo "        </tr>\n";
  }
  echo "      </table>\n";
}
else {
  echo '      <p>'.no_records_yet."</p>\n";
}

?>
