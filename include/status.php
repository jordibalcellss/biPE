<?php

echo "      <h2>".getTaskName($_GET['id'], 'h3')."</h2>\n";

$db = new DB();

$stmt = $db->prepare('SELECT rate FROM tasks WHERE id=?');
$stmt->execute(array($_GET['id']));
$rate = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT SUM(amount) FROM quotations WHERE task_id=? AND nature='i'");
$stmt->execute(array($_GET['id']));
$estimated_income = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT SUM(amount) FROM quotations WHERE task_id=? AND nature='e'");
$stmt->execute(array($_GET['id']));
$estimated_expense = $stmt->fetchColumn();

$estimated_revenue = $estimated_income - $estimated_expense;

if ($rate) {
  echo '      <div class="one-third box-number alpha">'."\n";
  echo '        <h1>'.rate.' '.rate_math."</h1>\n";
  echo '        <span>'.formatNumberP($rate, false, true)."</span>\n";
  echo '      </div>'."\n";

  $stmt = $db->prepare('SELECT SUM(duration) AS spent FROM time_log WHERE task_id=? AND saved');
  $stmt->execute(array($_GET['id']));
  $spent = $stmt->fetchColumn();

  echo '      <div class="one-third box-number">'."\n";
  echo '        <h1>'.hours.' '.spent."</h1>\n";
  echo '        <span>'.number_format(round($spent), 0, '', '.')."</span>\n";
  echo '      </div>'."\n";

  $remaining = $estimated_revenue / $rate - $spent;

  echo '      <div class="one-third box-number">'."\n";
  echo '        <h1>'.hours.' '.remaining."</h1>\n";
  echo '        <span>'.number_format(round($remaining), 0, '', '.')."</span>\n";
  echo '      </div>'."\n";  
}

echo '      <div class="one-third box-number alpha">'."\n";
echo '        <h1>'.estimated_income."</h1>\n";
echo '        <span>'.formatNumberP($estimated_income)."</span>\n";
echo '      </div>'."\n";

echo '      <div class="one-third box-number">'."\n";
echo '        <h1>'.estimated_expense."</h1>\n";
echo '        <span>'.formatNumberP($estimated_expense)."</span>\n";
echo '      </div>'."\n";

echo '      <div class="one-third box-number">'."\n";
echo '        <h1>'.estimated_revenue."</h1>\n";
echo '        <span>'.formatNumberP($estimated_revenue)."</span>\n";
echo '      </div>'."\n";

$stmt = $db->prepare("SELECT SUM(amount) FROM invoices WHERE task_id=? AND nature='i'");
$stmt->execute(array($_GET['id']));
$income = $stmt->fetchColumn();

echo '      <div class="one-third box-number alpha">'."\n";
echo '        <h1>'.invoiced_income."</h1>\n";
echo '        <span>'.formatNumberP($income)."</span>\n";
echo '      </div>'."\n";

$stmt = $db->prepare("SELECT SUM(amount) FROM invoices WHERE task_id=? AND nature='e'");
$stmt->execute(array($_GET['id']));
$expense = $stmt->fetchColumn();

echo '      <div class="one-third box-number">'."\n";
echo '        <h1>'.invoiced_expense."</h1>\n";
echo '        <span>'.formatNumberP($expense)."</span>\n";
echo '      </div>'."\n";

$revenue = $income - $expense;

echo '      <div class="one-third box-number">'."\n";
echo '        <h1>'.revenue."</h1>\n";
echo '        <span>'.formatNumberP($revenue)."</span>\n";
echo '      </div>'."\n";

$stmt = $db->prepare("SELECT SUM(amount) FROM invoices WHERE task_id=? AND nature='i' AND settled");
$stmt->execute(array($_GET['id']));
$settled_income = $stmt->fetchColumn();

echo '      <div class="one-third box-number alpha">'."\n";
echo '        <h1>'.settled_income."</h1>\n";
echo '        <span>'.formatNumberP($settled_income)."</span>\n";
echo '      </div>'."\n";

$stmt = $db->prepare("SELECT SUM(amount) FROM invoices WHERE task_id=? AND nature='e' AND settled");
$stmt->execute(array($_GET['id']));
$settled_expense = $stmt->fetchColumn();

echo '      <div class="one-third box-number">'."\n";
echo '        <h1>'.settled_expense."</h1>\n";
echo '        <span>'.formatNumberP($settled_expense)."</span>\n";
echo '      </div>'."\n";

echo "      <div class=\"clear\"></div>\n";

?>
