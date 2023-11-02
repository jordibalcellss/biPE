<?php

$db = new DB();

if ($_SESSION['role'] != 'accountant') {
  
  echo '      <h2>'.periodic."</h2>\n";

  echo "      <div class=\"four-quarters\">\n";
  echo "        <div class=\"three-quarters alpha\">\n";
  echo "          three-quarters\n";
  echo "        </div>\n";

  echo "        <div class=\"one-quarter omega\">\n";
  echo "          one-quarter\n";
  echo "        </div>\n";
  echo "      </div>\n";

  echo "      <div class=\"clear\"></div>\n";
}

?>
