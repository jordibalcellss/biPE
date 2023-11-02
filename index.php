<?php

/**
 * biPE
 *
 * Copyright 2023 by Jordi Balcells <jordi@balcells.io>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 * 
 * You should have received a copy of the GNU General Public License along with
 * this program.
 * 
 * If not, see <https://www.gnu.org/licenses/>.
 */

require 'config.php';
require 'locale/'.LOCALE.'.php';
require 'include/functions.php';
ini_set('error_reporting',ERROR_REPORTING);
ini_set('display_errors',DISPLAY_ERRORS);

session_start();
if (!isset($_SESSION['id'])) {
  if (!isset($_COOKIE['sessionPersists'])) {
    header("Location: login.php");
  }
  else {
    $_SESSION['id'] = $_COOKIE['sessionPersists'];
    $_SESSION['role'] = getRole($_SESSION['id']);
  }
}
ob_start(); //buffers output until end of page or ob_ functions

require 'include/template/head.php';

$err = [];

if (isset($_GET['module'])) {
  if ($_GET['module'] == 'log' && $_SESSION['role'] != 'accountant') {
    if ($_POST) {
      require('include/log.php');
      require('include/log-form.php');
    }
    else {
      require('include/log-form.php');
    }
  }
  else if ($_GET['module'] == 'timesheet')
  {
    require('include/timesheet.php');
  }
  else if ($_GET['module'] == 'tasks' && $_SESSION['role'] == 'admin') {
    if (isset($_GET['action'])) {
      if ($_GET['action'] == 'edit') {
        require('include/tasks-form.php');
      }
      else if ($_GET['action'] == 'status') {
        require('include/status.php');
      }
    }
    else {
      require('include/tasks.php');
    }
  }
  if ($_GET['module'] == 'bulk-log' && $_SESSION['role'] != 'accountant') {
    require('include/bulk-log.php');
  }
  else if ($_GET['module'] == 'accounting'
    && $_SESSION['role'] != 'employee') {
    if (isset($_GET['action'])) {
      if ($_GET['action'] == 'list') {
        require('include/accounting.php');
      }
      else if ($_GET['action'] == 'remove') {
        if (removeAccountingEntry($_GET['id'], $_GET['type'])) {
          echo '      <div>'.remove_success.
            ' - <a href="index.php?module=tasks">'.back.'</a></div>'."\n";
        }
      }
      else {
        require('include/accounting-form.php');
      }
    }
  }
  else if ($_GET['module'] == 'clients' && $_SESSION['role'] != 'employee') {
    if (isset($_GET['action'])) {
      if ($_GET['action'] == 'edit') {
        require('include/clients-form.php');
      }
      else if ($_GET['action'] == 'remove') {
        require('include/clients.php');
      }
    }
    else {
      require('include/clients.php');
    }
  }
  else if ($_GET['module'] == 'expenses') {
    require('include/expenses.php');
  }
  else if ($_GET['module'] == 'invoicing') {
    require('include/invoicing.php');
  }
  else if ($_GET['module'] == 'overview') {
    require('include/overview.php');
  }
  else if ($_GET['module'] == 'periodic') {
    require('include/periodic.php');
  }
  else if ($_GET['module'] == 'test') {
    require('include/test.php');
  }
}
else {
  if ($_SESSION['role'] != 'accountant') {
    require('include/log-form.php');
  }
  else {
    require('include/invoicing.php');
  }
}

require 'include/template/base.php';

?>
