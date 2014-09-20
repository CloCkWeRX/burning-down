<?php
require_once 'lib/Fire.php';
require_once 'lib/GeoRSSParser.php';


require_once 'HTTP/Request2.php';
require_once 'Cache/Lite.php';
require_once 'Log.php';

require_once 'config.php';

$log = new Log(null);

try {
  $db->query('CREATE TABLE data(
    guid VARCHAR(100),
    lat VARCHAR(20),
    lon VARCHAR(20),
    description TEXT,
    title VARCHAR(100),
    status VARCHAR(100),
    location VARCHAR(100),
    article_timestamp VARCHAR(10),
    PRIMARY KEY (guid))');

  $db->query('CREATE TABLE areas(
    guid VARCHAR(100),
    osmid VARCHAR(30),
    PRIMARY KEY (guid, osmid))'); 

} catch (Exception $e) {
  $log->debug($e);
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
// file_put_contents('output.json', json_encode($fires, JSON_PRETTY_PRINT));

// Set a id for this cache


// Set a few options
$options = array(
    'cacheDir' => 'cache/',
    'lifeTime' => 60*60*24*7
);

// Create a Cache_Lite object
$cache = new Cache_Lite($options);


$request = new HTTP_Request2('http://overpass-api.de/api/interpreter');
$request->setMethod('POST');

// 500m
// 1 deg latitude = approx 111km
// 500/111000 = 0.0045045045
$offset = 0.0045045045;


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

    $ways = $results->xpath('//way');
    foreach ($ways as $way) {
      $area = array();
      foreach ($way->nd as $node) {
        $nodes = $results->xpath('//node[@id=' . (string)$node['ref'] . "]");

        if ($nodes[0]["lat"] && $nodes[0]["lon"]) {
          $area["nodes"][] = array((float)$nodes[0]["lat"], (float)$nodes[0]["lon"]);
        }
      }

      foreach ($way->tag as $tag) {
        $area[(string)$tag['k']] = (string)$tag['v'];
      }
      $area['fire'] = $fire->id;
      $area['id'] = (string)$way['id'];
      $areas[] = $area;
    }
    
  } catch (HTTP_Request2_MessageException $e) {
    $log->warning($e->getMessage());
  }

}
// file_put_contents('areas.json', json_encode($areas, JSON_PRETTY_PRINT));



foreach ($fires as $fire) {
  $exists = $db->query("SELECT * FROM fires WHERE guid = " . $db->quote($fire->id))->fetchObject();

  if (!$exists) {
    $sql = "INSERT INTO fires(guid, lat, lon, description, article_timestamp) VALUES(:guid, :lat, :lon, :description, :article_timestamp)";
  } else {
    $sql = "UPDATE fires SET lat = :lat, lon = :lon, description = :description, article_timestamp = :article_timestamp WHERE guid = :guid";
  }
  $statement = $db->prepare($sql);
  $statement->execute(array(
    ':guid' => $fire->id, 
    ':lat' => $fire->lat, 
    ':lon' => $fire->long,
    ':description' => $fire->description,
    ':article_timestamp' => $fire->date->format("U")
  ));
}


foreach ($areas as $area) {
  $exists = $db->query("SELECT * FROM areas WHERE osmid = " . $db->quote($area['id']) . " and guid = " . $db->quote($area['fire']))->fetchObject();

  if (!$exists) {
    $sql = "INSERT INTO areas(guid, osmid) VALUES(:guid, :osmid)";
    $statement = $db->prepare($sql);

    $statement->execute(array(
      ':guid' => $area['fire'], 
      ':osmid' => $area['id']
    ));
  }
}
