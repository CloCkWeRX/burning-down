<?php
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

    if (count($point) > 0) { 

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


  $fires = array_merge($fires, $engine->parse());
}
file_put_contents('output.json', json_encode($fires, JSON_PRETTY_PRINT));

require_once 'HTTP/Request2.php';
require_once 'Cache/Lite.php';
require_once 'Log.php';
// Set a id for this cache


// Set a few options
$options = array(
    'cacheDir' => 'cache/',
    'lifeTime' => 3600
);

// Create a Cache_Lite object
$cache = new Cache_Lite($options);

$request = new HTTP_Request2('http://overpass-api.de/api/interpreter');
$request->setMethod('POST');

// 500m
// 1 deg latitude = approx 111km
// 500/111000 = 0.0045045045
$offset = 0.0045045045;

$log = new Log(null);

$areas = array();
$nodes = array();
foreach ($fires as $fire) {

  $query = simplexml_load_file('osm.xml');
  $bbox = $query->xpath('//bbox-query');
  //s="41.88917978666802" w="12.48945951461792" n="41.89152790667126" e="12.497506141662598"
  //s="-35.24786215739992" w="137.67791748046875" n="-34.58573628651287" e="139.73785400390625

  foreach ($bbox as $box) {
    $box['n'] = $fire->lat + $offset;
    $box['s'] = $fire->lat - $offset;
    $box['w'] = $fire->long - $offset;
    $box['e'] = $fire->long + $offset;

  }
  
  try {
    $id = md5($query->asXML());
    $body = $cache->get($id);
    if (!$body) {
      $log->debug("Fetching");
      $request->addPostParameter('data', $query->asXML());
      $response = $request->send();

      $body = $response->getBody();
    
      $cache->save($response->getBody(), $id);
    } else {
      $log->debug("Hit cache");
    }
    
    $results = simplexml_load_string($body);
    
    // TODO: Calculate the area of the polygons in question to make claims like "500ha of farmland."

    $areas = array_merge($areas, $results->xpath('//way'));
  } catch (HTTP_Request2_MessageException $e) {
    print $e->getMessage();
  }

}
file_put_contents('areas.json', json_encode($areas, JSON_PRETTY_PRINT));
