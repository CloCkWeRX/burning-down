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
  }

}
class DESQLDParser extends GeoRSSParser {

  public function parse() {
    $items = array();
    foreach ($this->feed->xpath("//item") as $item) {
      $fire = new Fire();

      $fire->title = (string)$item->title;
      
      $fire->title = (string)$item->title;
      $fire->description = (string)$item->description;
      list($fire->lat, $fire->long) = explode(" ", (string)$item->xpath('georss:point')[0]);

      $items[] = $fire;
    }

    return $items;
  }
}

class ACTParser extends GeoRSSParser {
  public function parse() {
    $items = array();
    foreach ($this->feed->xpath("//item") as $item) {
      $fire = new Fire();

      $fire->title = (string)$item->title;
      
      $fire->title = (string)$item->title;
      $fire->description = (string)$item->description;
      list($fire->lat, $fire->long) = explode(" ", (string)$item->xpath('georss:point')[0]);

      $items[] = $fire;
    }

    return $items;
  }
}

class RFSNSWParser extends GeoRSSParser {
  public function parse() {
    $items = array();
    foreach ($this->feed->xpath("//item") as $item) {
      $fire = new Fire();

      $fire->title = (string)$item->title;
      
      $fire->title = (string)$item->title;
      $fire->description = (string)$item->description;
      list($fire->lat, $fire->long) = explode(" ", (string)$item->xpath('georss:point')[0]);

      $items[] = $fire;
    }

    return $items;
  }
}

class CFSSAParser extends GeoRSSParser {
  public function parse() {
    $items = array();
    foreach ($this->feed->xpath("//item") as $item) {
      $fire = new Fire();

      $fire->title = (string)$item->title;
      
      $fire->title = (string)$item->title;
      $fire->description = (string)$item->description;
      //list($fire->lat, $fire->long) = explode(" ", (string)$item->xpath('georss:point')[0]);

      $items[] = $fire;
    }

    return $items;
  }
}
class CFAVicParser extends GeoRSSParser {
  public function parse() {
    $items = array();
    foreach ($this->feed->xpath("//item") as $item) {
      $fire = new Fire();

      $fire->title = (string)$item->title;
      
      $fire->title = (string)$item->title;
      $fire->description = (string)$item->description;
      list($fire->lat, $fire->long) = explode(" ", (string)$item->xpath('georss:point')[0]);

      $items[] = $fire;
    }

    return $items;
  }
}

$files = array(
  'bushfireAlert.xml' => 'DESQLDParser',
  'currentincidents.xml' => 'ACTParser', // http://www.esa.act.gov.au/feeds/currentincidents.xml

  'majorIncidents.xml' => 'RFSNSWParser', // http://www.rfs.nsw.gov.au/feeds/majorIncidents.xml
  'CFS_Current_Incidents.xml' => 'CFSSAParser', // http://www.cfs.sa.gov.au/custom/criimson/CFS_Current_Incidents.xml
  'IN_COMING.rss' => 'CFAVICParser' //http://osom.cfa.vic.gov.au/public/osom/IN_COMING.rss
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
