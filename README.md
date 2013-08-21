#mappress
v0.8.3beta

MapPress WordPress Theme acts as a geojournalism platform which allows news organizations, bloggers and NGOs to publish news stories as layers of information on digital maps. With MapPress, creating the interaction between data layers and contextual information is much more intuitive and interactive. The theme is ready for multilingual content and facilitates the publishing tasks.

You can post geotagged stories and create richly designed pages for each one of the featured stories. At same time, by simply imputing the ids of layers hosted on MapBox, you can manage sophisticated maps without loosing perfomance, add legends directly with HTML and set the map paramethers. All direct at the WordPress dashboard.

MapPress wants to help journalists and NGOs to improve storytelling with maps. Creating a child theme with all its functionality is easy since it contains all the necessary hooks to customize layouts and data visualization.

## Mix and visualize your MapBox maps with WordPress
Download the file and upload to your `wp-content/themes/` directory, on your [self-hosted WordPress installation](http://codex.wordpress.org/WordPress_Quick_Start_Guide).

Or you can use git:

```
$ cd wp-content/themes
$ git clone git://github.com/cardume/mappress.git
```

After setting the files, go to **Appearance > Themes** and activate **mappress**.

## Features
 - Leaflet map library
 - MapBox maps integration with filtering layers tools.
 - Geocoding WordPress posts using OpenStreetMaps or Google Maps supporting custom post types.
 - Google Street View support for Google Maps geocoding.
 - Customizable marker icons that can be associated to categories, custom taxonomies or posts directly.
 - Map markers query integrated to posts query.
 - GeoJSON API (any content `/?geojson` gives the geojson output). *E.g.: yourwebsite.com/category/one/?geojson*
 - Extensive hooks with documentation yet to come
 - Support [qTranslate](http://wordpress.org/extend/plugins/qtranslate/) multilanguage plugin

### Features to come
 - Custom map tiles, such as [stamen maps](http://maps.stamen.com/), [MapQuest](http://developer.mapquest.com/web/products/open/map) and [OpenStreetMap](http://wiki.openstreetmap.org/wiki/Tiles)
 - CartoDB integration

## Using mappress

### First map
After activating your theme, you'll see a big message on your homepage to create your first map. Do it! We have a MapBox example if you still don't have your own maps. But we [recommend you start doing it](http://mapbox.com/)

### Settings, configurations and contents
After setting your first map, go to the bottom of your dashboard's menu and click on MapPress Settings to change website styles, map behaviours and basic settings to set it up the way you need it.

Now you can go manage your posts, geolocate them and add custom marker icons!

### Authors and Contributors

MapPress is a collaboration between [@oeco](https://github.com/oeco/), [@cardume](https://github.com/cardume/), [@memelab](https://github.com/memelab/) and [@icfjknight](https://github.com/icfjknight/). Developed after the theme was built for [InfoAmazonia](http://infoamazonia.org/), a project led by ICFJ Knight International Journalism Fellow [Gustavo Faleiros](http://www.icfj.org/our-work/brazil-expand-use-satellite-mapping-and-other-technologies-improve-environmental-reporting) and supported by [Internews](http://www.internews.org/).

### Support or Contact
Having trouble with mappress? Go to our [issues page](https://github.com/cardume/mappress/issues) and we'll help you there!
