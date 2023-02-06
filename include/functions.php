<?php

class DB extends PDO {
  public function __construct() {
    $dsn = "mysql:host=".DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME;
    parent::__construct($dsn, DB_USER, DB_PASS, array(PDO::MYSQL_ATTR_FOUND_ROWS => true));
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
    $data = $date->format('D M d H:i:s e Y').' '.$_SERVER['REMOTE_ADDR'].' '.$_POST['username'].": $message\n";
    fwrite($file,$data);
    fclose($file);
  }
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
  if ($filter == 'active') {
    $cond = 'active';
  }
  else if ($filter == 'editable') {
    $cond = 'NOT readonly';
  }
  $stmt = $db->prepare("SELECT id, code,
                        (CASE
                          WHEN id=1 THEN '".task_weekend_nothing."'
                          WHEN id=2 THEN '".task_holiday."'
                          WHEN id=3 THEN '".task_off_sick."'
                          WHEN id=4 THEN '".task_leave."'
                          ELSE name
                        END) AS name, active FROM tasks WHERE $cond");
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

function getTaskName($task_id,$class) {
  //returns string prepended with code (if any)
  $db = new DB();
  $stmt = $db->prepare("SELECT
                        (CASE
                          WHEN id=1 THEN '".task_weekend_nothing."'
                          WHEN id=2 THEN '".task_holiday."'
                          WHEN id=3 THEN '".task_off_sick."'
                          WHEN id=4 THEN '".task_leave."'
                          ELSE
                          CASE
                            WHEN code IS NULL OR code='' THEN name
                            ELSE CONCAT('<span class=\"code-$class\">', code, \"</span> \", name)
                          END
                        END) FROM tasks WHERE id=?");
  $stmt->execute(array($task_id));
  return $stmt->fetchColumn();
}

function getDurations() {
  //returns an array
  $durations = [];
  for ($i = 1; $i <= WORKDAY_DURATION/INTERVAL_H; $i++) {
    $durations[$i-1] = INTERVAL_H*$i;
  } 
  return $durations;
}

function getLastRecord($user_id) {
  //returns DateTime object, latest entry date or false if empty
  $db = new DB();
  $stmt = $db->prepare('SELECT MAX(day) AS day FROM time_log WHERE user_id=? AND saved=1');
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
  $stmt = $db->prepare('DELETE FROM time_log WHERE id=? AND user_id=? AND day=?');
  $stmt->execute(array($record_id,$user_id,$last->format('Y-m-d')));
  return $stmt->rowCount();
}

function getTargetDate($user_id) {
  //returns a DateTime object
  if (!getLastRecord($user_id)) {
    //first record, log yesterday time
    return new DateTime('yesterday', new DateTimeZone(TIMEZONE));
  }
  else {
    //log the next day time
    $today = getLastRecord($user_id);
    return $today->modify('+1 day');
  }
}

function getUnsavedRecords($user_id) {
  //returns an array of strings
  $db = new DB();
  $stmt = $db->prepare("SELECT tasks.id, tasks.code,
                        (CASE
                          WHEN tasks.id=1 THEN '".task_weekend_nothing."'
                          WHEN tasks.id=2 THEN '".task_holiday."'
                          WHEN tasks.id=3 THEN '".task_off_sick."'
                          WHEN tasks.id=4 THEN '".task_leave."'
                          ELSE tasks.name
                        END) AS name, time_log.duration
                        FROM time_log LEFT JOIN tasks ON tasks.id = time_log.task_id
                        WHERE user_id=? AND saved=0");
  $stmt->execute(array($user_id));
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $out = [];
  foreach ($records as $record) {
    if ($record['id'] <= 4) {
      $time_preview = '';
    }
    else {
      if (countRemainingHours($record['id'])) {
        $time_preview = ' ('.countRemainingHours($record['id']).' '.hours.' '.remaining.')';
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
    $out[] = $code.$record['name'].": ".floor($record['duration']).' '.hours.decimalPartToFrac($record['duration']).$time_preview;
  }
  return $out;
}

function countRemainingHours($task_id){
  //returns an integer or false if rate is not set or there are no fees
  $db = new DB();
  //rate p/hour
  $stmt = $db->prepare('SELECT rate FROM tasks WHERE id=?');
  $stmt->execute(array($task_id));
  $rate = $stmt->fetchColumn();
  if ($rate) {
    //over estimated revenue
    $stmt = $db->prepare("SELECT SUM(amount) FROM quotations WHERE task_id=? AND nature='i'");
    $stmt->execute(array($task_id));
    $estimated_income = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT SUM(amount) FROM quotations WHERE task_id=? AND nature='e'");
    $stmt->execute(array($task_id));
    $estimated_expense = $stmt->fetchColumn();
    
    $estimated_revenue = $estimated_income - $estimated_expense;

    if ($estimated_revenue) {
      //accumulated worked
      $stmt = $db->prepare('SELECT SUM(duration) AS spent FROM time_log WHERE task_id=?');
      $stmt->execute(array($task_id));
      $spent = $stmt->fetchColumn();

      return round($estimated_revenue / $rate - $spent);
    }
  }
  else {
    return false;
  }
}

function gcd($n,$m) {
  if ($m > 0) {
    return gcd($m,$n%$m);
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
    $d_len = strlen((string)$d)-2;
    $num = ceil($d * pow(10,$d_len));
    $den = pow(10,$d_len);
    return ' '.$num / gcd($num,$den).'/'.$den / gcd($num,$den);
  }
  else {
    return '';
  }
}

function checkInputDate($date) {
  //gets direct user input string (dd*mm*yyyy, with or w/o leading zeros), returns yyyy-mm-dd on success
  $dmy = preg_split('/[-\/.\s]/',trim($date)); //admits dashes, slashes, dots and spaces as separators
  if (count($dmy) != 3) {
    return false;
  }
  else {
    if (!ctype_digit($dmy[0]) || !ctype_digit($dmy[1]) || !ctype_digit($dmy[2])) {
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

function formatNumberP($number, $hide_zero=false, $hide_zero_decimal=false) {
  //returns formatted string for printing
  if ($hide_zero && $number == 0) {
    return '';
  }
  else {
    if ($hide_zero_decimal) {
      return str_replace('.', ',', floatval(round($number, 2)));
    }
    else {
      if (LOCALE == 'ca') {
        return number_format($number, 2, ',', '.');
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

?>
