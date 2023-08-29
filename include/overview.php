<?php

$db = new DB();

echo '      <h2>'.overview."</h2>\n";

if ($_SESSION['role'] != 'employee') {

  //income
  $stmt = $db->query("
    SELECT task_id, SUM(amount) FROM invoices
    LEFT JOIN tasks ON tasks.id = invoices.task_id
    WHERE nature = 'i' AND tasks.active
    GROUP BY task_id
  ");
  $income = $stmt->fetchAll(PDO::FETCH_NUM);
  
  //settled income
  $stmt = $db->query("
    SELECT task_id, SUM(amount) FROM invoices
    WHERE nature = 'i' AND settled
    GROUP BY task_id
  ");
  $settled = $stmt->fetchAll(PDO::FETCH_NUM);

  //estimated income
  $stmt = $db->query("
    SELECT task_id, SUM(amount) FROM quotations
    LEFT JOIN tasks ON tasks.id = quotations.task_id
    WHERE nature = 'i' AND tasks.active
    GROUP BY task_id
  ");
  $estimated = $stmt->fetchAll(PDO::FETCH_NUM);

  //ratio of estimate already invoiced
  $invoiced_ratios = getRatios($estimated, $income);

  //ratio of income already settled
  $settled_ratios = getRatios($income, $settled);

  //move into ultimate tabulation
  //iterate invoiced_ratios and push the settled ratio plus add task name
  for ($i = 0; $i < count($invoiced_ratios); $i++) {
    $task_id = $invoiced_ratios[$i][0];
    $task_id_key = array_search($task_id, array_column($settled_ratios, 0));
    $invoiced_ratios[$i][] = formatNumberP($settled_ratios[$task_id_key][1] *
      100, false, true, 0).' %';
    $invoiced_ratios[$i][0] = getTaskName($invoiced_ratios[$i][0]);
    $invoiced_ratios[$i][1] = formatNumberP($invoiced_ratios[$i][1] * 100,
      false, true, 0).' %';
  }
  
  /*for ($i = 0; $i < count($estimated_settled_ratios); $i++) {
    $estimated_settled_ratios[$i][0] = getTaskName($estimated_settled_ratios[$i][0]);
  }*/

  require('include/libraries/array-to-texttable.php');

  mb_internal_encoding("utf-8");

  $renderer = new ArrayToTextTable($invoiced_ratios);
  $renderer->showHeaders(false);
  echo "      <pre>\n";
  $renderer->render();
  echo "\n      </pre>\n";

  /*$renderer = new ArrayToTextTable($estimated_settled_ratios);
  $renderer->showHeaders(false);
  echo "      <pre>\n";
  $renderer->render();
  echo "\n      </pre>\n";*/
}

?>
