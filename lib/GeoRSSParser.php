<?php
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

    $loc = explode(": ", $parts[2]);
    if (!empty($loc)) {
      $fire->location = $loc[1];
    }
    $status = explode(": ", $parts[3]);

    if (!empty($status)) {
      $fire->status = $status[1];
    }
    $desc = explode(": ", $parts[4]);
    if (!empty($desc)) {
      $fire->description = $desc[1];   
    }
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
  public function parseCoordinates($item, $fire) {
    $coords = strip_tags((string)$item->description);

    $matches = array();
    preg_match("/latitude: (.*)/", $coords, $matches);
    $fire->lat = $matches[1];

    preg_match("/longitude: (.*)/", $coords, $matches);
    $fire->long = $matches[1];
  }

  public function parseDescription($item, $fire) {
    $description = (string)$item->description;

    $fire->description = strip_tags($description);
  }
}
