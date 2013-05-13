#mappress
v0.7.7beta

## Mix and visualize your MapBox maps with WordPress
Download the file and upload to your `wp-content/themes/` directory, on your [self-hosted WordPress installation](http://codex.wordpress.org/WordPress_Quick_Start_Guide).

Or you can use git:

```
$ cd wp-content/themes
$ git clone git://github.com/cardume/mappress.git
```

After setting the files, go to **Appearance > Themes** and activate **mappress**.

## Features
 - MapBox maps integration with filtering layers tools.
 - Geocoding WordPress posts using OpenStreetMaps or Google Maps supporting custom post types.
 - Google Street View support for Google Maps geocoding.
 - Customizable marker icons that can be associated to categories, custom taxonomies or posts directly.
 - Map markers query integrated to posts query.
 - GeoJSON API (any content `/?geojson` gives the geojson output). *E.g.: yourwebsite.com/category/one/?geojson*
 - Extensive hooks and filters with documentation yet to come

### Features to come
 - Leaflet map library for more map features
 - Custom map tiles, such as [stamen maps](http://maps.stamen.com/), [MapQuest](http://developer.mapquest.com/web/products/open/map) and [OpenStreetMap](http://wiki.openstreetmap.org/wiki/Tiles)
 - CartoDB integration

## Using mappress

### First map
After activating your theme, you'll see a big message on your homepage to create your first map. Do it! We have a MapBox example if you still don't have your own maps. But we [recommend you start doing it](http://mapbox.com/)

### Settings, configurations and contents
After setting your first map, go to the bottom of your dashboard's menu and click on MapPress Settings to change website styles, map behaviours and basic settings to set it up the way you need it.

Now you can go manage your posts, geolocate them and add custom marker icons!

### Authors and Contributors
MapPress is a collaboration between @oeco, @cardume, @memelab and @icfjknight.

### Support or Contact
Having trouble with mappress? Go to our [issues page](https://github.com/cardume/mappress/issues) and we'll help you there!
