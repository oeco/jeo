(function($) {

	mappress.markers = function(map) {

		if(!mappress_markers.query)
			return;

		var markers = mappress.markers;
		var markersLayer = mapbox.markers.layer();
		var features;
		var fragment = false;
		var listPost;

		// setup sidebar
		map.$.parents('.map-container').wrapAll('<div class="content-map" />');
		map.$.parents('.content-map').prepend('<div class="map-sidebar"><div class="sidebar-inner"></div></div>');
		map.$.sidebar = map.$.parents('.content-map').find('.sidebar-inner');
		map.dimensions = new MM.Point(map.parent.offsetWidth, map.parent.offsetHeight);
		map.draw();

		if(typeof mappress.fragment === 'function')
			fragment = mappress.fragment();

		$.getJSON(mappress_markers.ajaxurl,
		{
			action: 'markers_geojson',
			query: mappress_markers.query
		},
		function(geojson) {
			if(geojson === 0)
				return;
			markers.build(geojson);
		});

		mappress.markers.build = function(geojson) {

			map.addLayer(markersLayer);

			// do clustering
			features = markers.doClustering(geojson.features);

			map.features = features;
			map.markersLayer = markersLayer;

			markersLayer
				.features(features)
				.key(function(f) {
					return f.properties.id;
				})
				.factory(function(x) {

					if(!markers.hasLocation(x))
						return;

					var e = document.createElement('div');

					$(e).addClass('story-points')
						.addClass(x.properties.id)
						.attr('data-publisher', x.properties.source);

					$(e).data('feature', x);

					/* soon
					if(!markers.fromMap(x))
						$(e).addClass('hide');
					else
						$(e).removeClass('hide');
					*/

					// POPUP

					var o = document.createElement('div');
					o.className = 'popup clearfix';
					e.appendChild(o);
					var content = document.createElement('div');
					content.className = 'story';
					content.innerHTML = '<span class="arrow">&nbsp;</span><small>' + x.properties.date + ' - ' + x.properties.source + '</small><h4>' + x.properties.title + '</h4>';
					o.appendChild(content);

					// cluster stuff
					if(x.properties.cluster) {

						var cluster = x.properties.cluster;
						var coords = x.geometry.coordinates;

						$(e).addClass(cluster);
						$(e).addClass('cluster');

						if(!x.properties.activeCluster) {

							x.properties.origLat = parseFloat(coords[1]);
							x.properties.origLon = parseFloat(coords[0]);

							// Story count popup
							var content = document.createElement('div');
							content.className = 'count';
							content.innerHTML = '<span class="arrow">&nbsp;</span><h4></h4>';
							o.appendChild(content);

							$(e).hover(function() {
								var len = $('.' + cluster + ':not(.hide)').length;
								if (len > 1) {
									$('.count h4', this).text(len + ' ' + mappress_markers.stories_label);
								} else {
									$(e).addClass('open');
								}
							});

						}

					}

					$(e).click(function() {

						var open = markers.collapseClusters();

						if (cluster && _.indexOf(open, cluster) == -1) {

							var cl = $('.' + cluster + ':not(.hide)');
							var step = 2 * Math.PI / cl.length;
							cl.each(function(i, el) {

								var feature = $(el).data('feature');
								var coords = feature.geometry.coordinates;

								$(el).addClass('open');

								feature.properties.activeCluster = true;

								coords[1] = parseFloat(coords[1]) + (Math.sin(step * i) * cl.length * 0.1);
								coords[0] = parseFloat(coords[0]) + (Math.cos(step * i) * cl.length * 0.1);

							});

							markersLayer.features(features);
							return;

						}

						if(!$(this).hasClass('active'))
							markers.open(x, false);

					});

					return e;

				});


			// FIRST STORY
			var story = geojson.features[0];
			var silent = true;

			// if not home, navigate to post
			if(!mappress_markers.home) 
				silent = false;

			if(fragment) {
				var fStoryID = fragment.get('story');
				if(fStoryID) {
					var found = _.any(geojson.features, function(c) {
						if(c.properties.id == fStoryID) {
							story = c;
							if(fragment.get('loc'))
								silent = true;
							return true;
						}
					});
					if(!found) {
						fragment.rm('story');
					}
				}
			}

			// bind list post events
			listPosts = $('.list-posts');
			if(listPosts.length) {
				listPosts.find('li').click(function() {
					var markerID = $(this).attr('id');
					document.body.scrollTop = 0;
					var markerMap = markers.fromMap(markerID);
					if(!markerMap) {
						// to do, update map group map nav through map (only one) from post	
					}
					markers.open(markerID, false);
					return false;
				});

				story = listPosts.find('li:nth-child(1)').attr('id');
			}

			markers.open(story, silent);

		};

		mappress.markers.getMarker = function(id) {
			return _.find(features, function(m) { return m.properties.id === id; });
		}

		mappress.markers.open = function(marker, silent) {

			if(!markers.fromMap(marker))
				return;

			// if marker is string, get object
			if(typeof marker === 'string') {
				marker = _.find(features, function(m) { return m.properties.id === marker; });
			}

			if(fragment) {
				if(!silent)
					fragment.set({story: marker.properties.id});
			}

			if(typeof _gaq !== 'undefined') {
				_gaq.push(['_trackPageView', location.pathname + location.search + '#!/story=' + marker.properties.id]);
			}

			if(!silent) {
				var zoom;
				var center;
				if(markers.hasLocation(marker)) { 
					center = {
						lat: marker.geometry.coordinates[1],
						lon: marker.geometry.coordinates[0]
					}
					zoom = 7;
					if(map.conf.maxZoom < 7)
						zoom = map.conf.maxZoom;
				} else {
					center = map.conf.center;
					zoom = map.conf.zoom;
				}
				map.ease.location(center).zoom(zoom).optimal(0.9, 1.42, function() {
					if(fragment) {
						fragment.rm('loc');
					}
				});				
			}

			// populate sidebar
			if(map.$.sidebar.length) {

				if(!map.$.sidebar.story) {
					map.$.sidebar.append('<div class="story" />');
					map.$.sidebar.story = map.$.sidebar.find('.story');
				}

				map.$.find('.story-points').removeClass('active');
				var $point = map.$.find('.story-points.' + marker.properties.id);
				$point.addClass('active');

				var storyData = marker.properties;

				var story = '';
				story += '<small>' + storyData.date + ' - ' + storyData.source + '</small>';
				story += '<h2>' + storyData.title + '</h2>';
				if(storyData.thumbnail)
					story += '<div class="media-limit"><img class="thumbnail" src="' + storyData.thumbnail + '" /></div>';
				story += storyData.story;
				story += ' <a href="' + storyData.url + '" target="_blank" rel="external">' + mappress_markers.read_more_label + '</a>';

				map.$.sidebar.story.empty().append($(story));

				// add share button
				if(!map.$.sidebar.share) {

					map.$.sidebar.append('<div class="sharing" />');
					map.$.sidebar.share = map.$.sidebar.find('.sharing');

					var shareContent = '';
					shareContent += '<a class="button share-button" href="#">' + mappress_markers.share_label + '</a>';
					shareContent += '<div class="share-options">';
					shareContent += '<label for="story_embed_iframe_input" class="iframe_input">' + mappress_markers.copy_embed_label + '</label>';
					shareContent += '<input type="text" id="story_embed_iframe_input" class="iframe_input" readonly="readonly">';
					shareContent += '<div class="social">';
					shareContent += '<div class="fb-like" data-href="" data-send="false" data-layout="button_count" data-width="450" data-show-faces="false" data-font="verdana" data-action="recommend"></div>';
					shareContent += '</div>';


					map.$.sidebar.share.append(shareContent);

					map.$.sidebar.share.find('.share-button').click(function() {
						var sharing = map.$.sidebar.share.find('.share-options');
						if(sharing.hasClass('hidden')) {
							sharing.show().removeClass('hidden');
						} else {
							sharing.hide().addClass('hidden');
						}
						return false;
					});

					map.$.sidebar.share.find('.iframe_input').click(function() {
						map.$.sidebar.share.find('input.iframe_input').select();
						return false;
					});
				}

				map.$.sidebar.share.find('.share-options').hide().addClass('hidden');

				// update share button
				var share_url = mappress_markers.site_url + '#!/' + 'story=' + marker.properties.id;
				var iframe_url = share_url + '&iframe=true&full=true';
				var iframe_content = '<iframe src="' + iframe_url + '" frameborder="0" width="1100" height="480"></iframe>';
				map.$.sidebar.share.find('.iframe_input').attr('value', iframe_content);
				// fb
				map.$.sidebar.share.find('.fb-like').data('href', share_url);

			}

			// activate post in post list
			var postList = $('.list-posts');
			if(postList.length) {
				postList.find('li').removeClass('active');
				var item = postList.find('#' + marker.properties.id);
				if(item.length) {
					item.addClass('active');
				}
			}
		};

		mappress.markers.hasLocation = function(marker) {
			if(marker.geometry.coordinates[0] ===  0 || !marker.geometry.coordinates[0])
				return false;
			else
				return true;
		}

		mappress.markers.fromMap = function(x) {
			// if marker is string, get object
			if(typeof x === 'string') {
				x = _.find(features, function(m) { return m.properties.id === x; });
			}

			if(!x)
				return false;

			if(!x.properties.maps)
				return true;

			return _.find(x.properties.maps, function(markerMap) { return 'map_' + markerMap == map.currentMapID; });
		}

		mappress.markers.doClustering = function(features) {

			// determine if p1 is close to p2
			var close = function(p1, p2) {

				if(!p1 || !p2)
					return false;

				var x1 = p1[1];
				var y1 = p1[0];
				var x2 = p2[1];
				var y2 = p2[0];
				d = Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
				return d < 0.1;
			}

			var tested = [];
			var clusters = {};

			_.each(features, function(current, i) {

				tested.push(current);

				clusters[current.properties.id] = 0;

				// test each marker
				_.each(tested, function(f) {

					var id = f.properties.id;

					if(current.properties.id === id)
						return;

					if(close(current.geometry.coordinates, f.geometry.coordinates)) {

						var clusterID = tested.length;
						if(clusters[id] !== 0)
							clusterID = clusters[id];

						clusters[current.properties.id] = clusterID;
						clusters[id] = clusterID;

					}

				});

			});

			// save cluster to feature
			_.each(features, function(f, i) {
				if(clusters[f.properties.id] !== 0)
					features[i].properties.cluster = 'cluster-' + clusters[f.properties.id];
			});

			return features;
		}

		// Close all open clusters.
		mappress.markers.collapseClusters = function() {
			var open = [];
			map.$.find('.story-points.open').each(function(i, el) {
				// Remove open class and kill popup, which has different content
				// depending on open class.
				$(el).removeClass('open');
				//$(el).find('.popup').stop().animate({opacity: 'hide'}, 0);
				var cluster = $(el).attr('class').match(/(cluster-\d+)/);

				open.push(cluster[1]);

				var feature = $(el).data('feature');
				var coords = feature.geometry.coordinates;

				feature.properties.activeCluster = false;
				coords[1] = feature.properties.origLat;
				coords[0] = feature.properties.origLon;

			});
			markersLayer.features(features);
			return open;
		};

		mappress.markers.openClusters = function(m) {
			var radius = 0.1;
			map.$.find('.cluster:not(.open)').each(function(i, el) {

				var cluster = $(el).attr('class').match(/(cluster-\d+)/);
				var cl = $('.' + cluster[1] + ':not(.open)');

				if(cl.length) {
					var step = 2 * Math.PI / cl.length;
					cl.each(function(i, el) {
						delete el.coord; // Clear mmg internal coordinate cache.
						$(el).addClass('open');
						$(el).data('original_lat', el.location.lat);
						$(el).data('original_lon', el.location.lon);
						el.location.lat += Math.sin(step * i) * cl.length * radius;
						el.location.lon += Math.cos(step * i) * cl.length * radius;
					});
				}
			});
		};
	}

})(jQuery);