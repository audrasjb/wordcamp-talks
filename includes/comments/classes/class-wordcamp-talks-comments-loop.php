<?php
/**
 * WordCamp Talks Comments Loop class.
 *
 * @package WordCamp Talks
 * @subpackage comments/classes
 *
 * @since 1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Comment Loop **************************************************************/

if ( ! class_exists( 'WordCamp_Talks_Comments_Loop' ) ) :

/**
 * Comments loop Class.
 *
 * @since 1.0.0
 * @since 1.1.0 Renamed from WordCamp_Talks_Loop_Comments to WordCamp_Talks_Comments_Loop.
 */
class WordCamp_Talks_Comments_Loop extends WordCamp_Talks_Core_Loop {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $args the loop args
	 */
	public function __construct( $args = array() ) {

		$default = array(
			'post_status' => 'publish',
			'status'      => 'approve',
			'user_id'     => 0,
			'number'      => wct_talks_per_page(),
		);

		// All post status if user is viewing his profile
		if ( wct_is_current_user_profile() || current_user_can( 'read_private_talks' ) ) {
			$default['post_status'] = '';
		}

		//Merge default with requested
		$r = wp_parse_args( $args, $default );

		// Set which pagination page
		if ( get_query_var( wct_cpage_rewrite_id() ) ) {
			$paged = get_query_var( wct_cpage_rewrite_id() );

		} else if ( ! empty( $_GET[ wct_cpage_rewrite_id() ] ) ) {
			$paged = absint( $_GET[ wct_cpage_rewrite_id() ] );

		} else if ( ! empty( $r['page'] ) ) {
			$paged = absint( $r['page'] );

		// Set default page (first page)
		} else {
			$paged = 1;
		}

		$comments_args = array(
			'post_type'   => wct_get_post_type(),
			'post_status' => $r['post_status'],
			'status'      => $r['status'],
			'user_id'     => (int) $r['user_id'],
			'number'      => (int) $r['number'],
			'offset'      => intval( ( $paged - 1 ) * $r['number'] ),
			'page'        => (int) $paged,
		);

		if ( ! empty( $comments_args ) ) {
			foreach ( $comments_args as $key => $value ) {
				$this->{$key} = $value;
			}
		} else {
			return false;
		}

		if ( empty( $this->user_id ) ) {
			$comment_count = 0;
		} else {
			$comment_count = wct_comments_count_comments( $this->user_id );
		}

		// Get the comments
		$comments = get_comments( $comments_args );

		if ( ! empty( $comments ) ) {
			$post_ids = wp_list_pluck( $comments, 'comment_post_ID' );

			// Get all posts in the object cache.
			$posts = get_posts( array( 'include' => $post_ids, 'post_type' => wct_get_post_type() ) );

			// Reset will need to be done at the end of the loop
			wct_set_global( 'needs_reset', true );

			// Build a new post array indexed by post ID
			$p = array();
			foreach ( $posts as $post ) {
				$p[ $post->ID ] = $post;
			}

			// Attach the corresponding post to each comment
			foreach ( $comments as $key => $comment ) {
				if ( ! empty( $p[ $comment->comment_post_ID ] ) ) {
					$comments[ $key ]->talk = $p[ $comment->comment_post_ID ];
				}
			}
		}

		$params = array(
			'plugin_prefix'    => 'wct',
			'item_name'        => 'comment',
			'item_name_plural' => 'comments',
			'items'            => $comments,
			'total_item_count' => $comment_count,
			'page'             => $this->page,
			'per_page'         => $this->number,
		);

		$paginate_args = array();

		if ( ! wct_is_pretty_links() ) {
			$paginate_args['base'] = add_query_arg( wct_cpage_rewrite_id(), '%#%' );
		} else {
			$paginate_args['base']   = trailingslashit( wct_users_get_displayed_profile_url( 'comments') ) . '%_%';
			$paginate_args['format'] = wct_cpage_slug() . '/%#%/';
		}

		parent::start( $params, apply_filters( 'wct_comments_pagination_args', $paginate_args ) );
	}
}

endif;
