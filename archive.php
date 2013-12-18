<?php get_header(); ?>

<?php jeo_featured(); ?>

<div class="section-title">
	<div class="container">
		<div class="twelve columns">
			<h1 class="archive-title"><?php
					if( is_tag() || is_category() || is_tax() ) :
						printf( __( '%s', 'jeo' ), single_term_title() );
					elseif ( is_day() ) :
						printf( __( 'Daily Archives: %s', 'jeo' ), get_the_date() );
					elseif ( is_month() ) :
						printf( __( 'Monthly Archives: %s', 'jeo' ), get_the_date( _x( 'F Y', 'monthly archives date format', 'jeo' ) ) );
					elseif ( is_year() ) :
						printf( __( 'Yearly Archives: %s', 'jeo' ), get_the_date( _x( 'Y', 'yearly archives date format', 'jeo' ) ) );
					else :
						_e( 'Archives', 'jeo' );
					endif;
				?></h1>
		</div>
	</div>
</div>
<?php get_template_part('loop'); ?>

<?php get_footer(); ?>