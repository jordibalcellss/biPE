<?php

class DB extends PDO {
  public function __construct() {
    $dsn = "mysql:host=".DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME;
    parent::__construct($dsn, DB_USER, DB_PASS,
      array(PDO::MYSQL_ATTR_FOUND_ROWS => true));
  }
}

function LDAPconnect() {
  $con = ldap_connect(LDAP_HOST);
  ldap_set_option($con,LDAP_OPT_PROTOCOL_VERSION,3);
  if ($con) {
    $bind = ldap_bind($con,LDAP_USER,LDAP_PASS);
  }
  return array($con,$bind);
}

function printMessages($err) {
  //displays HTML form error messages
  if (count($err)) {
    echo '        <div id="messages">'."\n";
    echo '          '.implode('<br />',$err)."\n";
    echo "        </div>\n";
  }
}

function writeLog($filename, $message) {
  if (LOGGING) {
    $dir = 'log/';
    if (!is_dir($dir)) {
      mkdir($dir);
    }
    $file = fopen($dir.$filename,'a');
    $date = new DateTime(null, new DateTimeZone('UTC'));
    $data = $date->format('D M d H:i:s e Y').' '.$_SERVER['REMOTE_ADDR'].
      ' '.$_POST['username'].": $message\n";
    fwrite($file,$data);
    fclose($file);
  }
}

function getUserMembership($user_id) {
  //returns an array of group names
  $con = LDAPconnect();
  $result = ldap_search($con[0],LDAP_GROUP_BASE,"(cn=*)",
    array('cn','memberuid'));
  $entries = ldap_get_entries($con[0],$result);
  ldap_close($con[0]);
  $groups = array();
  for ($i = 0; $i < $entries['count']; $i++) {
    if (isset($entries[$i]['memberuid'])) {
      for ($j = 0; $j < $entries[$i]['memberuid']['count']; $j++) {
        if ($entries[$i]['memberuid'][$j] == $user_id) {
          $groups[] = $entries[$i]['cn'][0];
          break;
        }
      }
    }
  }
  return $groups;
}

function getRole($user_id) {
  /*
   * returns string or false if ambiguous, possible success values
   * admin
   * accountant
   * employee
   */
  $groups = getUserMembership($user_id);
  
  if (in_array(LDAP_AUTH_ADMIN_GROUP, $groups)) {
    return 'admin';
  }
  else if (in_array(LDAP_AUTH_ACCOUNTANT_GROUP, $groups)) {
    return 'accountant';
  }
  else if (in_array(LDAP_AUTH_EMPLOYEE_GROUP, $groups)) {
    return 'employee';
  }
  else {
    return false;
  }
}

function printMenu() {
  //menu items depend on the role
  $modules_admin = 'log timesheet periodic expenses tasks overview';
  $modules_accountant = 'expenses invoicing overview';
  $modules_employee = 'log timesheet expenses';

  $menu = '<span>'.greeting.', '.$_SESSION['id'].'! - ';
  if ($_SESSION['role'] == 'admin') {
    $modules = $modules_admin;
  }
  else if ($_SESSION['role'] == 'accountant') {
    $modules = $modules_accountant;
  }
  else if ($_SESSION['role'] == 'employee') {
    $modules = $modules_employee;
  }

  foreach (explode(' ', $modules) as $module) {
    $menu .= "<a href=\"?module=$module\">".constant($module).'</a> - ';
  }
  $menu .= '- <a href="login.php?action=logout">'.logout.'</a></span>'."\n";
  echo $menu;
}

function getTasks($filter) {
  /*
   * returns an array with the following attributes
   * id
   * code
   * name
   * active (editable filter only)
   */
  $db = new DB();
  $cond = '';
  if ($filter == 'active') {
    $cond = 'active';
  }
  else if ($filter == 'editable') {
    $cond = 'NOT readonly';
  }
  $cond = $cond.' ORDER BY code DESC, name ASC, id DESC';

  $stmt = $db->prepare("
    SELECT id, code,
    (CASE
      WHEN id=1 THEN '".task_weekend_nothing."'
      WHEN id=2 THEN '".task_holiday."'
      WHEN id=3 THEN '".task_off_sick."'
      WHEN id=4 THEN '".task_leave."'
      ELSE SUBSTR(name, 1, 32)
    END) AS name, active FROM tasks WHERE $cond
  ");
  $stmt->execute();
  $tasks = [];
  $i = 0;
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $tasks[$i]['id'] = $row['id'];
    $tasks[$i]['code'] = $row['code'];
    $tasks[$i]['name'] = $row['name'];
    if ($filter == 'editable') {
      $tasks[$i]['active'] = $row['active'];
    }
    $i++;
  }
  return $tasks;
}

function getTaskName($task_id, $class = false) {
  //returns string prepended with code (if any)
  if (!$class) {
    $opening = '';
    $closure = '';
  }
  else {
    $opening = "<span class=\"code-$class\">";
    $closure = "</span>";
  }

  $db = new DB();
  $stmt = $db->prepare("
    SELECT
    (CASE
      WHEN id=1 THEN '".task_weekend_nothing."'
      WHEN id=2 THEN '".task_holiday."'
      WHEN id=3 THEN '".task_off_sick."'
      WHEN id=4 THEN '".task_leave."'
      ELSE
      CASE
        WHEN code IS NULL OR code='' THEN name
        ELSE CONCAT('$opening', code, '$closure ', name)
      END
    END) FROM tasks WHERE id=?
  ");
  $stmt->execute(array($task_id));
  return $stmt->fetchColumn();
}

function getDurations() {
  //returns an array
  $durations = [];
  for ($i = 1; $i <= (WORKDAY_DURATION + 2)/INTERVAL_H; $i++) {
    $durations[$i-1] = INTERVAL_H*$i;
  } 
  return $durations;
}

function getLastRecord($user_id) {
  //returns DateTime object, latest entry date or false if empty
  $db = new DB();
  $stmt = $db->prepare('
    SELECT MAX(day) AS day FROM time_log WHERE user_id=? AND saved');
  $stmt->execute(array($user_id));
  $day = $stmt->fetchColumn();
  if ($day == NULL) {
    return false;
  }
  else {
    return new DateTime($day, new DateTimeZone(TIMEZONE));
  }
}

function removeRecord($record_id,$user_id) {
  $db = new DB();
  $last = getLastRecord($user_id);
  $stmt = $db->prepare('
    DELETE FROM time_log WHERE id=? AND user_id=? AND day=?');
  $stmt->execute(array($record_id,$user_id,$last->format('Y-m-d')));
  return $stmt->rowCount();
}

function getTargetDate($user_id) {
  //returns a DateTime object
  if (!getLastRecord($user_id)) {
    //first record
    $today = new DateTime(null, new DateTimeZone(TIMEZONE));
    if (SKIP_WEEKENDS_HOLIDAYS) {
      //return last full working day
      while (true) {
        $cand = $today->modify('-1 day');
        if ($cand->format('D') != 'Sat' && $cand->format('D') != 'Sun'
          && !isHoliday($cand)) {
          return $cand;
        }
      }
    }
    return $today->modify('-1 day');
  }
  else {
    $today = getLastRecord($user_id);
    if (SKIP_WEEKENDS_HOLIDAYS) {
      //return next working day
      while (true) {
        $cand = $today->modify('+1 day');
        if ($cand->format('D') != 'Sat' && $cand->format('D') != 'Sun'
          && !isHoliday($cand)) {
          return $cand;
        }
      }
    }
    return $today->modify('+1 day');
  }
}

function isHoliday($day) {
  //expects a DateTime object
  $db = new DB();
  $stmt = $db->query("SELECT CONCAT(month, '-', day) AS day FROM holidays");
  while ($row = $stmt->fetch()) {
    if ($day->format('n-j') == $row['day']) {
      return true;
    }
  }
  return false;
}

function countWorkingDays($year) {
  //returns an integer
  $day = new DateTime($year.'-01-01', new DateTimeZone("UTC"));
  $last = new DateTime($year.'-12-31', new DateTimeZone("UTC"));
  $count = 0;
  while ($day <= $last) {
    $day = $day->modify('+1 day');
    if ($day->format('D') != 'Sat' && $day->format('D') != 'Sun'
      && !isHoliday($day)) {
      $count++;
    }
  }
  return $count;
}

function getUnsavedRecords($user_id) {
  //returns an array of strings
  $db = new DB();
  $stmt = $db->prepare("
    SELECT tasks.id, tasks.code,
    (CASE
      WHEN tasks.id=1 THEN '".task_weekend_nothing."'
      WHEN tasks.id=2 THEN '".task_holiday."'
      WHEN tasks.id=3 THEN '".task_off_sick."'
      WHEN tasks.id=4 THEN '".task_leave."'
      ELSE tasks.name
    END) AS name, time_log.duration
    FROM time_log LEFT JOIN tasks
    ON tasks.id = time_log.task_id
    WHERE user_id=? AND NOT saved
  ");
  $stmt->execute(array($user_id));
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $out = [];
  $accumulated = 0;
  foreach ($records as $record) {
    if ($record['id'] <= 4) {
      $time_preview = '';
    }
    else {
      if (countRemainingHours($record['id'])) {
        $time_preview = ' ('.countRemainingHours($record['id']).
          ' '.hours.' '.remaining.')';
      }
      else {
        $time_preview = '';
      }
    }
    if ($record['code'] == '') {
      $code = '';
    }
    else {
      $code = '<span class="code-p">'.$record['code'].'</span> ';
    }
    $out[] = $code.$record['name'].": ".getHValue($record['duration']).
      $time_preview;
    $accumulated += $record['duration'];
  }
  if ($accumulated) {
    $out[] = '<b>'.total.'</b>: '.getHValue($accumulated);
  }
  return $out;
}

function countRemainingHours($task_id){
  /*
   * returns an integer or false if
   * rate is not set or
   * there are no fees or 
   * user role is employee
   */
  $db = new DB();
  //rate p/hour
  $stmt = $db->prepare('SELECT rate FROM tasks WHERE id=?');
  $stmt->execute(array($task_id));
  $rate = $stmt->fetchColumn();
  if ($rate && $_SESSION['role'] != 'employee') {
    //over estimated revenue
    $stmt = $db->prepare("
      SELECT SUM(amount) FROM quotations WHERE task_id=? AND nature='i'");
    $stmt->execute(array($task_id));
    $estimated_income = $stmt->fetchColumn();

    $stmt = $db->prepare("
      SELECT SUM(amount) FROM quotations WHERE task_id=? AND nature='e'");
    $stmt->execute(array($task_id));
    $estimated_expense = $stmt->fetchColumn();
    
    $estimated_revenue = $estimated_income - $estimated_expense;

    if ($estimated_revenue) {
      //accumulated worked
      $stmt = $db->prepare('
        SELECT SUM(duration) AS spent FROM time_log WHERE task_id=?');
      $stmt->execute(array($task_id));
      $spent = $stmt->fetchColumn();

      return round($estimated_revenue / $rate - $spent);
    }
  }
  else {
    return false;
  }
}

function removeAccountingEntry($entry_id, $entry_type) {
  $db = new DB();
  if ($entry_type == 'quotation') {
    $table = 'quotations';
  }
  else if ($entry_type = 'invoice') {
    $table = 'invoices';
  }
  $stmt = $db->prepare("DELETE FROM $table WHERE id=?");
  $stmt->execute(array($entry_id));
  return $stmt->rowCount();
}

function getClientDetails($client_id) {
  /*
   * returns an array with the following attributes
   * name
   * address
   * city
   * postcode
   * email
   * phone
   * vat_code
   * 
   * the empty attributes are not returned
   */
  $db = new DB();
  $stmt = $db->prepare('SELECT * FROM clients WHERE id=?');
  $stmt->execute(array($client_id));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $client = [];
  $client['name'] = $row['name'];
  $client['address'] = $row['address'];
  $client['city'] = $row['city'];
  $client['postcode'] = $row['postcode'];
  if (strlen($row['email']) != 0) {
    $client['email'] = $row['email'];
  }
  if (strlen($row['phone']) != 0) {
    $client['phone'] = $row['phone'];
  }
  if (strlen($row['vat_code']) != 0) {
    $client['vat_code'] = $row['vat_code'];
  }
  return $client;
}

function getTasksFromClient($client_id) {
  //returns an array of formatted task codes
  $db = new DB();
  $stmt = $db->prepare("SELECT
    (CASE
      WHEN code=''
      THEN '<span class=\"code-p\">----</span>'
      ELSE CONCAT('<span class=\"code-p\">', code, '</span>')
    END) AS code
    FROM tasks
    LEFT JOIN clients
    ON clients.id = tasks.client_id
    WHERE client_id=?
    ORDER BY code DESC
  ");
  $stmt->execute(array($client_id));
  $codes = [];
  while ($row = $stmt->fetch()) {
    $codes[] = $row['code'];
  }
  return $codes;
}

function getTimesheetOverview($user_id, $interval = 'week') {
  /*
   * prepares formatted non-readonly tasks overview
   * returns an array of arrays
   * [tasks => [[name, spent, percent], [...]], total_spent ]
   */
  $interval = strtoupper($interval);
  $db = new DB();
  $stmt = $db->prepare("
    SELECT
    (CASE
      WHEN tasks.code IS NULL OR tasks.code = '' THEN tasks.name
      ELSE CONCAT('<span class=\"code-p\">', tasks.code,
      '</span> ', tasks.name)
    END) as task, SUM(duration) as spent
    FROM time_log
    LEFT JOIN tasks
    ON tasks.id = time_log.task_id
    WHERE time_log.day >=
      DATE_SUB(CURDATE(), INTERVAL DAYOF$interval(CURDATE()) DAY)
    AND NOT tasks.readonly AND time_log.saved
    AND time_log.user_id = ?
    GROUP BY time_log.task_id
    ORDER BY spent DESC
  ");
  $stmt->execute(array($user_id));
  $total_spent = 0;
  $overview = array();
  //iterate result set for total spent
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $overview['tasks'][] = array(
      'name' => $row['task'], 'spent' => $row['spent']);
    $total_spent = $total_spent + $row['spent'];
  }
  //push percents
  if (isset($overview['tasks'])) {
    for ($i = 0; $i < count($overview['tasks']); $i++) {
      $percent = formatNumberP(
        $overview['tasks'][$i]['spent'] / $total_spent * 100, false, true, 1);
      $overview['tasks'][$i]['percent'] = $percent.' %';
    }
    $overview['total_spent'] = $total_spent;
  }
  return $overview;
}

function getRatios($full, $partial) {
  /*
   * expects two 2-column array with
   * column 0 = numeric index
   * column 1 = value
   * returns an array with the same number of rows than full and containing
   * partial's values divided by full's matching indexes sorted by column 1
   */
  $ratios = array();
  $i = 0;
  while ($i < count($full)) {
    $j = 0;
    while ($j < count($partial)) {
      if ($partial[$j][0] == $full[$i][0]) {
        //there is a matching index
        $ratios[$i][0] = $partial[$j][0];
        $ratios[$i][1] = $partial[$j][1] / $full[$i][1];
        break;
      }
      if ($j == count($partial) - 1) {
        //reached the end of partial, then ratio = 0
        $ratios[$i][0] = $full[$i][0];
        $ratios[$i][1] = 0;
      }
      $j++;
    }
    $i++;
  }
  usort($ratios, 'sortByValue');
  return $ratios;
}

function sortByValue($a, $b) {
  if ($a[1] > $b[1]) {
    return 1;
  }
  else if ($a[1] < $b[1]) {
    return -1;
  }
  else {
    return 0;
  }
}

function gcd($n, $m) {
  if ($m > 0) {
    return gcd($m, $n%$m);
  }
  else {
    return abs($n);
  }
}

function decimalPartToFrac($n) {
  //returns formatted string with fraction to unity
  $w = floor($n);
  $d = $n - $w;
  if ($d > 0) {
    $d_len = strlen((string)$d) - 2;
    $num = ceil($d * pow(10, $d_len));
    $den = pow(10, $d_len);
    return $num / gcd($num, $den).'/'.$den / gcd($num,$den);
  }
  else {
    return '';
  }
}

function getHValue($n) {
  /*
   * translates into fractional hour reading
   *
   * expects a number
   * returns an string with the integer part and a fraction
   * if there is a decimal part, otherwise a single integer
   * (or fraction if less than unity)
   * appends constant "hours" to the output
   */
  if ($n - floor($n)) {
    if ($n > 1) {
      return floor($n).' '.hours.' '.decimalPartToFrac($n);
    }
    else {
      return decimalPartToFrac($n).' '.hours;
    }
  }
  else {
    return floor($n).' '.hours;
  }
}
  
function checkInputDate($date) {
  /*
   * gets direct user input string (dd*mm*yyyy, with or w/o leading zeros)
   * returns yyyy-mm-dd on success
   */

  //admits dashes, slashes, dots and spaces as separators
  $dmy = preg_split('/[-\/.\s]/',trim($date));
  if (count($dmy) != 3) {
    return false;
  }
  else {
    if (!ctype_digit($dmy[0]) || !ctype_digit($dmy[1]) ||
      !ctype_digit($dmy[2])) {
      return false;
    }
    else {
      if (checkdate($dmy[1], $dmy[0], $dmy[2])) {
        return $dmy[2].'-'.$dmy[1].'-'.$dmy[0];
      }
      else {
        return false;
      }
    }
  }
}

function formatDateP($date) {
  //returns d-m-Y string for printing
  if ($date != '') {
    $date = new DateTime($date, new DateTimeZone(TIMEZONE));
    return $date->format('d-m-Y');
  }
  else {
    return false;
  }
}

function formatNumberP($number, $hide_zero = false, $hide_zero_decimal = false,
  $decimals = 2) {
  //returns formatted string for printing
  if ($hide_zero && $number == 0) {
    return '';
  }
  else {
    if ($hide_zero_decimal) {
      return str_replace('.', ',', floatval(round($number, $decimals)));
    }
    else {
      if (LOCALE == 'ca') {
        return number_format($number, $decimals, ',', '.');
      }
    }
  }
}

function formatNumberR($number) {
  //returns formatted string to feed back
  if (LOCALE == 'ca') {
    return str_replace(',', '.',str_replace('.', '', $number));
  }
}

function mb_str_pad($str, $pad_len, $pad_str = ' ', $dir = STR_PAD_RIGHT) {
  //https://stackoverflow.com/questions/14773072/php-str-pad-unicode-issue
  $str_len = mb_strlen($str);
  $pad_str_len = mb_strlen($pad_str);
  if (!$str_len && ($dir == STR_PAD_RIGHT || $dir == STR_PAD_LEFT)) {
    $str_len = 1; // @debug
  }
  if (!$pad_len || !$pad_str_len || $pad_len <= $str_len) {
    return $str;
  }

  $result = null;
  if ($dir == STR_PAD_BOTH) {
    $length = ($pad_len - $str_len) / 2;
    $repeat = ceil($length / $pad_str_len);
    $result = mb_substr(str_repeat($pad_str, $repeat), 0, floor($length))
      . $str
      . mb_substr(str_repeat($pad_str, $repeat), 0, ceil($length));
  }
  else {
    $repeat = ceil($str_len - $pad_str_len + $pad_len);
    if ($dir == STR_PAD_RIGHT) {
      $result = $str . str_repeat($pad_str, $repeat);
      $result = mb_substr($result, 0, $pad_len);
    }
    else if ($dir == STR_PAD_LEFT) {
      $result = str_repeat($pad_str, $repeat);
      $result = mb_substr($result, 0,
        $pad_len - (($str_len - $pad_str_len) + $pad_str_len)) . $str;
    }
  }
  return $result;
}

?>
