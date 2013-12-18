<?php

require_once(get_template_directory() . '/inc/admin/settings.php');
require_once(get_template_directory() . '/inc/core.php');
require_once(get_template_directory() . '/inc/share-widget.php');

if(!isset($content_width))
	$content_width = 760;

/*
 * Theme setup
 */
function jeo_setup() {

	add_theme_support('automatic-feed-links');
	add_theme_support('post-thumbnails');

	// text domain
	load_theme_textdomain('jeo', get_template_directory() . '/languages');

	register_nav_menus(array(
		'header_menu' => __('Header menu', 'jeo'),
		'footer_menu' => __('Footer menu', 'jeo')
	));

	//sidebars
	register_sidebar(array(
		'name' => __('Post sidebar', 'jeo'),
		'id' => 'post',
		'before_title' => '<h2 class="widget-title">',
		'after_title' => '</h2>'
	));
	register_sidebar(array(
		'name' => __('General sidebar', 'jeo'),
		'id' => 'general',
		'before_title' => '<h2 class="widget-title">',
		'after_title' => '</h2>'
	));
	register_sidebar(array(
		'name' => __('Front page', 'jeo'),
		'id' => 'front_page',
		'before_title' => '<h2 class="widget-title">',
		'after_title' => '</h2>'
	));

}
add_action('after_setup_theme', 'jeo_setup');

function jeo_theme_scripts() {
	// styles
	wp_register_style('jeo-lsf', get_template_directory_uri() . '/css/lsf.css');
	wp_register_style('jeo-base', get_template_directory_uri() . '/css/base.css', array(), '1.2');
	wp_register_style('jeo-skeleton', get_template_directory_uri() . '/css/skeleton.css', array('jeo-base'), '1.2');
	wp_register_style('font-opensans', 'http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800');
	wp_register_style('jeo-main', get_template_directory_uri() . '/css/main.css', array('jeo-skeleton', 'jeo-lsf', 'font-opensans'), '0.0.3');

	wp_register_script('jquery-isotope', get_template_directory_uri() . '/lib/jquery.isotope.min.js', array('jquery'), '1.5.25');

	wp_register_script('jeo-site', get_template_directory_uri() . '/js/site.js', array('jquery', 'jquery-isotope'));
}
add_action('wp_enqueue_scripts', 'jeo_theme_scripts', 5);

function jeo_enqueue_theme_scripts() {
	if(wp_style_is('jeo-main', 'registered'))
		wp_enqueue_style('jeo-main');

	if(wp_script_is('jeo-site', 'registered'))
		wp_enqueue_script('jeo-site');

	 if (is_singular())
	 	wp_enqueue_script( "comment-reply" );

}
add_action('wp_enqueue_scripts', 'jeo_enqueue_theme_scripts', 12);

function jeo_flush_rewrite() {
	global $pagenow;
	if(is_admin() && $_REQUEST['activated'] && $pagenow == 'themes.php') {
		global $wp_rewrite;
		$wp_rewrite->init();
		$wp_rewrite->flush_rules();
	}
}
add_action('init', 'jeo_flush_rewrite');

function jeo_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	switch ( $comment->comment_type ) :
		case 'pingback' :
		case 'trackback' :
			// Display trackbacks differently than normal comments.
			?>
			<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
				<p><?php _e( 'Pingback:', 'humus' ); ?> <?php comment_author_link(); ?> <?php edit_comment_link( __( '(Edit)', 'humus' ), '<span class="edit-link">', '</span>' ); ?></p>
			</li>
			<?php
		break;
		default :
		// Proceed with normal comments.
		global $post;
	?>
	<li <?php comment_class('row'); ?> id="li-comment-<?php comment_ID(); ?>">
		<article id="comment-<?php comment_ID(); ?>">
			<header class="comment-header clearfix">
				<?php echo get_avatar($comment, 60); ?>
			</header>
			<div class="comment-meta">
				<span class="comment-author">
					<?php
					printf( '<cite class="fn">%1$s</cite>',
						get_comment_author_link()
					);
					?>
				</span> | 
				<span class="comment-date">
					<?php
					printf( '<a href="%1$s"><time datetime="%2$s">%3$s</time></a>',
						esc_url( get_comment_link( $comment->comment_ID ) ),
						get_comment_time( 'c' ),
						/* translators: 1: date, 2: time */
						sprintf( __( '%1$s at %2$s', 'humus' ), get_comment_date(), get_comment_time() )
					);
					?>
				</span>
				<?php edit_comment_link( __( 'Edit', 'humus' ), ' | <span class="comment-edit-link">', '</span>'); ?>
			</div>
			<div class="comment-content-area">
				<?php if ( '0' == $comment->comment_approved ) : ?>
					<p class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.', 'humus' ); ?></p>
				<?php endif; ?>

				<section class="comment-content">
					<?php comment_text(); ?>
				</section><!-- .comment-content -->

				<div class="reply">
					<?php comment_reply_link( array_merge( $args, array('reply_text' => __( 'Reply', 'humus' ), 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
				</div><!-- .reply -->
			</div>
		</article><!-- #comment-## -->
	<?php
		break;
	endswitch; // end comment_type check
}
?>