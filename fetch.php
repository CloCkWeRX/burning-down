<?php
require_once 'HTTP/Request2.php';

class Fire {

  public $lat;
  public $long;
  public $description;
  public $author;
  public $category;
  public $date;
  public $id;
  public $title;
  public $status;
}

class GeoRSSParser {
  public function __construct(SimpleXMLElement $feed) {
    $this->feed = $feed;
    $this->fire = new Fire();
  }

  public function parse() {
    $items = array();
    foreach ($this->feed->xpath("//item") as $item) {
      $fire = clone $this->fire;

      $fire->id = (string)$item->guid;
      
      $fire->title = (string)$item->title;
      $fire->date = new DateTime((string)$item->pubDate);
      $this->parseDescription($item, $fire);
      $this->parseCoordinates($item, $fire);
      $items[] = $fire;
    }

    return $items;
  }

  public function parseDescription($item, $fire) {
    $fire->description = (string)$item->description;
  }


  public function parseCoordinates($item, $fire) {
    $point = $item->xpath('georss:point');
    if ($point && $point[0]) { 

      list($fire->lat, $fire->long) = explode(" ", (string)$point[0]);
    }
  }
}
class DESQLDParser extends GeoRSSParser {

  public function parseDescription($item, $fire) {
    $description = (string)$item->description;

    $parts = explode(". ", $description);

    $fire->location = explode(": ", $parts[2])[1];

    $fire->status = explode(": ", $parts[3])[1];
    $fire->description = explode(": ", $parts[4])[1];   
  }

}

class ACTParser extends GeoRSSParser {
}

class RFSNSWParser extends GeoRSSParser {
}

class CFSSAParser extends GeoRSSParser {
  public function parseCoordinates($item, $fire) {}
}

class CFAVicParser extends GeoRSSParser {
}

class TASParser extends GeoRSSParser {
}
class SentinelParser extends GeoRSSParser {
  public function parseDescription($item, $fire) {
    $description = (string)$item->description;

    $fire->description = str_replace("<br/>", "\n", $description);
  }
}

$files = array(
  'data/www.ruralfire.qld.gov.au/bushfirealert/bushfireAlert.xml' => 'DESQLDParser',
  'data/www.esa.act.gov.au/feeds/currentincidents.xml' => 'ACTParser',
  'data/www.rfs.nsw.gov.au/feeds/majorIncidents.xml' => 'RFSNSWParser', 
  'data/www.cfs.sa.gov.au/custom/criimson/CFS_Current_Incidents.xml' => 'CFSSAParser',
  'data/osom.cfa.vic.gov.au/public/osom/IN_COMING.rss' => 'CFAVICParser',
  // // MFS SA?
  // // WA ?
  // // NT ?
  'data/www.fire.tas.gov.au/Show?pageId=colBushfireSummariesRss' => 'TASParser',
  'data/sentinel.ga.gov.au/RSS/sentinelrss.xml' => 'SentinelParser'
);

$fires = array();
foreach ($files as $feed_file => $parser) {
  $document = simplexml_load_file($feed_file);
  $document->registerXPathNamespace('georss', 'http://www.georss.org/georss');
  $engine = new $parser($document);


  $fires += $engine->parse();
}
file_put_contents('output.json', json_encode($fires));
