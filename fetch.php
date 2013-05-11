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
    list($fire->lat, $fire->long) = explode(" ", (string)$item->xpath('georss:point')[0]);
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
  'bushfireAlert.xml' => 'DESQLDParser', // http://www.ruralfire.qld.gov.au/bushfirealert/bushfireAlert.xml
  // 'currentincidents.xml' => 'ACTParser', // http://www.esa.act.gov.au/feeds/currentincidents.xml

  // 'majorIncidents.xml' => 'RFSNSWParser', // http://www.rfs.nsw.gov.au/feeds/majorIncidents.xml
  // 'CFS_Current_Incidents.xml' => 'CFSSAParser', // http://www.cfs.sa.gov.au/custom/criimson/CFS_Current_Incidents.xml
  // 'IN_COMING.rss' => 'CFAVICParser', //http://osom.cfa.vic.gov.au/public/osom/IN_COMING.rss
  // // MFS SA?
  // // WA ?
  // // NT ?
  // 'Show?pageId=colBushfireSummariesRss' => 'TASParser', // TAS http://www.fire.tas.gov.au/Show?pageId=colBushfireSummariesRss
  // 'sentinelrss.xml' => 'SentinelParser' // http://sentinel.ga.gov.au/RSS/sentinelrss.xml
);

foreach ($files as $feed_file => $parser) {
  $document = simplexml_load_file($feed_file);
  $document->registerXPathNamespace('georss', 'http://www.georss.org/georss');
  $parser = new $parser($document);


  print_r($parser->parse());

}
// $document = simplexml_load_file('./bushfireAlert.xml');
// $document->registerXPathNamespace('georss', 'http://www.georss.org/georss');
// $parser = new DESQLDParser($document);
