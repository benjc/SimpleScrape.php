<?php
//Example of scraping current box office from fwfr.com
require("simpleScrape.php");

$scraper = new simpleScrape();

$scraper->sourceURL = "http://www.fwfr.com";
$scraper->scriptPath = "exampleScript.txt";

$values = $scraper->scrape();

foreach($values as $key=>$value) {
  for($index = 0; $index < Count($value); $index++) {
    echo "[\"".$key."\"][".$index."] = \"".$value[$index]."\"<br>";
  }
}
?> 
