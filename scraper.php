<?php
require_once 'lib/Fire.php';
require_once 'lib/GeoRSSParser.php';

$db = new PDO('sqlite:data.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
  $db->query('CREATE TABLE data(
    guid VARCHAR(100),
    lat VARCHAR(20),
    lon VARCHAR(20),
    description TEXT,
    title VARCHAR(100),
    status VARCHAR(100),
    location VARCHAR(100),
    source VARCHAR(30),
    article_timestamp VARCHAR(10),
    PRIMARY KEY (guid))');
} catch (Exception $e) {
}


$files = array(
  'http://www.ruralfire.qld.gov.au/bushfirealert/bushfireAlert.xml' => 'DESQLDParser',
  'http://www.esa.act.gov.au/feeds/currentincidents.xml' => 'ACTParser',
  'http://www.rfs.nsw.gov.au/feeds/majorIncidents.xml' => 'RFSNSWParser', 
  'http://www.cfs.sa.gov.au/custom/criimson/CFS_Current_Incidents.xml' => 'CFSSAParser',
  'http://osom.cfa.vic.gov.au/public/osom/IN_COMING.rss' => 'CFAVICParser',
  // // MFS SA?
  // // WA ?
  // // NT ?
  'http://www.fire.tas.gov.au/Show?pageId=colBushfireSummariesRss' => 'TASParser',
  'http://sentinel.ga.gov.au/geoserver/wms/reflect?layers=hotspot_current&format=application/rss+xml' => 'SentinelParser'
);

$fires = array();
foreach ($files as $feed_file => $parser) {
  $document = simplexml_load_file($feed_file);
  $document->registerXPathNamespace('georss', 'http://www.georss.org/georss');
  $engine = new $parser($document);

  $fires = array_merge($fires, $engine->parse());
}

foreach ($fires as $fire) {
  $exists = $db->query("SELECT * FROM data WHERE guid = " . $db->quote($fire->id))->fetchObject();

  if (!$exists) {
    $sql = "INSERT INTO data(guid, lat, lon, description, article_timestamp) VALUES(:guid, :lat, :lon, :description, :article_timestamp)";
  } else {
    $sql = "UPDATE data SET lat = :lat, lon = :lon, description = :description, article_timestamp = :article_timestamp WHERE guid = :guid";
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
$db->query("DELETE FROM data WHERE guid LIKE 'http://sentinel.ga.gov.au/geoserver/wms/reflect?featureid=hotspot_current.fid%'");
$db->query("ALTER TABLE data ADD COLUMN `source` VARCHAR(30)");