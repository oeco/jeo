<?php get_header(); ?>

<?php mappress_featured(); ?>

<div class="section-title">
	<div class="container">
		<div class="twelve columns">
			<h1 class="archive-title"><?php
					if ( is_day() ) :
						printf( __( 'Daily Archives: %s', 'mappress' ), get_the_date() );
					elseif ( is_month() ) :
						printf( __( 'Monthly Archives: %s', 'mappress' ), get_the_date( _x( 'F Y', 'monthly archives date format', 'mappress' ) ) );
					elseif ( is_year() ) :
						printf( __( 'Yearly Archives: %s', 'mappress' ), get_the_date( _x( 'Y', 'yearly archives date format', 'mappress' ) ) );
					else :
						_e( 'Archives', 'mappress' );
					endif;
				?></h1>
		</div>
	</div>
</div>
<?php get_template_part('loop'); ?>

<?php get_footer(); ?>