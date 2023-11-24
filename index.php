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
  require('include/'.$_GET['module'].'.php');
}
else {
  if ($_SESSION['role'] != 'accountant') {
    require('include/log.php');
  }
  else {
    require('include/invoicing.php');
  }
}

require 'include/template/base.php';

?>
