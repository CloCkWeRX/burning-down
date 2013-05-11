### What
An aggregation of all fires in Australia, from assorted GeoRSS feeds.

### To use:

    $ git clone ...
    $ chmod +x cron.sh
    # Run manually or set up your own crontab
    $ ./cron.sh
    
    $ pear install HTTP_Request2 Cache_Lite Log

    # Transform the data from GeoRSS to JSON
    $ php fetch.php
    
    # And now, go look!
    $ php -S localhost:8000
    $ google-chrome http://localhost:8000/map.html

### What's covered?

 * http://www.ruralfire.qld.gov.au/bushfirealert/bushfireAlert.xml
 * http://www.esa.act.gov.au/feeds/currentincidents.xml
 * http://www.rfs.nsw.gov.au/feeds/majorIncidents.xml
 * http://www.cfs.sa.gov.au/custom/criimson/CFS_Current_Incidents.xml
 * http://osom.cfa.vic.gov.au/public/osom/IN_COMING.rss
 * http://www.fire.tas.gov.au/Show?pageId=colBushfireSummariesRss
 * http://sentinel.ga.gov.au/RSS/sentinelrss.xml

### What's missing?
NT does not appear to publish geoRSS

SA's CFS does not have geocodes fire locations in their feeds

### TODO
Query the OSM Overpass API to detect what is in the particular area - forests, buildings, etc
