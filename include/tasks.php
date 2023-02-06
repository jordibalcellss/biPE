<?php

echo '      <h2>'.tasks."</h2>\n";
echo '      <div id="sec-menu"><a href="?module=tasks&action=edit">'.add.' '.task.'</a> - <a href="?module=clients">'.clients."</a></div>\n";

$records = getTasks('editable');
if (count($records) > 0) {
  //prepare table
  echo "      <table>\n";
  echo "        <tr>\n";
  echo "          <th>".code."</th>\n";
  echo "          <th>".name."</th>\n";
  echo "          <th align=\"right\">".actions."</th>\n";
  echo "        </tr>\n";
  foreach ($records as $record) {
    if (!$record['active']) {
      $tag_i = '<span class="greyed-out">';
      $tag_o = '</span>';
    }
    else {
      $tag_i = '';
      $tag_o = '';
    }
    if ($record['code'] == '') {
      $code = '';
    }
    else {
      $code = '<span class="code-p">'.$tag_i.$record['code'].$tag_o.'</span>';
    } 
    $edit = '<a href="?module=tasks&action=edit&id='.$record['id'].'">'.details.'</a>';
    $accounting = '&nbsp;&nbsp;<a href="?module=accounting&action=list&id='.$record['id'].'">'.accounting.'</a>';
    $status = '&nbsp;&nbsp;<a href="?module=tasks&action=status&id='.$record['id'].'">'.status.'</a>';
    $phases = '&nbsp;&nbsp;'.phases;
    echo "        <tr>\n";
    echo '          <td width="60">'.$code."</td>\n";
    echo '          <td width="270">'.$tag_i.$record['name'].$tag_o."</td>\n";
    echo '          <td align="right">'.$edit.$accounting.$status.$phases."</td>\n";
    echo "        </tr>\n";
  }
  echo "      </table>\n";
}
else {
  echo '      <p>'.no_records_yet."</p>\n";
}

?>
