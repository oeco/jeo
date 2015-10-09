<footer id="colophon">
	<div class="container">
		<div class="seven columns">
			<nav id="footer-nav">
				<?php wp_nav_menu(array('theme_location' => 'footer_menu')); ?>
			</nav>
		</div>
		<div class="five columns">
			<div class="credits">
				<p><?php printf(__('This website is built on <a href="%s" target="_blank" rel="external">WordPress</a> using the <a href="%s" target="_blank" rel="external">JEO Beta</a> theme', 'jeo'), 'http://wordpress.org', 'http://jeo.cardume.art.br/'); ?></p>
			</div>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
