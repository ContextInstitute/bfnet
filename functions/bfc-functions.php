<?php

// All of the bfcom-specific functions

// Remove the Toolbar for all users
add_filter('show_admin_bar','__return_false');

// This is based on https://codex.buddypress.org/developer/navigation-api/
function bfc_nav_configure() {
	global $bp;
	if( bp_is_group() ) {
		$bp->groups->nav->edit_nav( array( 'name' => __( 'G-Dash', 'buddypress' )), 'home', bp_current_item() );
	}
}
add_action( 'bp_actions', 'bfc_nav_configure' );


/**
 * This function, bfc_nouveau_has_nav(), is based on bp_nouveau_has_nav() in /wp-content/plugins/buddypress/bp-templates/bp-nouveau/includes/template-tags.php
 *
 * Init the Navigation Loop and check it has items.
 *
 * @since 3.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *
 *     @type string $type                    The type of Nav to get (primary or secondary)
 *                                           Default 'primary'. Required.
 *     @type string $object                  The object to get the nav for (eg: 'directory', 'group_manage',
 *                                           or any custom object). Default ''. Optional
 *     @type bool   $user_has_access         Used by the secondary member's & group's nav. Default true. Optional.
 *     @type bool   $show_for_displayed_user Used by the primary member's nav. Default true. Optional.
 * }
 *
 * @return bool True if the Nav contains items. False otherwise.
 */


function bfc_nouveau_has_nav( $args = array() ) {
	$bp_nouveau = bp_nouveau();
	$n = bp_parse_args(
		$args,
		array(
			'type'                    => 'primary',
			'object'                  => '',
			'user_has_access'         => true,
			'show_for_displayed_user' => true,
		),
		'nouveau_has_nav'
	);
	if ( empty( $n['type'] ) ) {
		return false;
	}
	$nav                       = array();
	$bp_nouveau->displayed_nav = '';
	$bp_nouveau->object_nav    = $n['object'];
	if ( bp_is_directory() || 'directory' === $bp_nouveau->object_nav ) {
		$bp_nouveau->displayed_nav = 'directory';
		$nav                       = $bp_nouveau->directory_nav->get_primary();
	// So far it's only possible to build a Group nav when displaying it.
	} elseif ( bp_is_group() ) {
		$bp_nouveau->displayed_nav = 'groups';
		$parent_slug               = bp_get_current_group_slug();
		$group_nav                 = buddypress()->groups->nav;
		if ( 'group_manage' === $bp_nouveau->object_nav && bp_is_group_admin_page() ) {
			$parent_slug .= '_manage';
		/**
		 * If it's not the Admin tabs, reorder the Group's nav according to the
		 * following list.
		 */
		} else {
			bp_nouveau_set_nav_item_order( $group_nav, array('home','forum','members','docs','activity','admin'), $parent_slug );
		}
		$nav = $group_nav->get_secondary(
			array(
				'parent_slug'     => $parent_slug,
				'user_has_access' => (bool) $n['user_has_access'],
			)
		);
	// Build the nav for the displayed user
	} elseif ( bp_is_user() ) {
		$bp_nouveau->displayed_nav = 'personal';
		$user_nav                  = buddypress()->members->nav;
		if ( 'secondary' === $n['type'] ) {
			$nav = $user_nav->get_secondary(
				array(
					'parent_slug'     => bp_current_component(),
					'user_has_access' => (bool) $n['user_has_access'],
				)
			);
		} else {
			$args = array();
			if ( true === (bool) $n['show_for_displayed_user'] && ! bp_is_my_profile() ) {
				$args = array( 'show_for_displayed_user' => true );
			}
			// Reorder the user's primary nav according to the customizer setting.
			bp_nouveau_set_nav_item_order( $user_nav, array('profile','notifications','messages','groups','docs','activity','settings'));
			$nav = $user_nav->get_primary( $args );
		}
	} elseif ( ! empty( $bp_nouveau->object_nav ) ) {
		$bp_nouveau->displayed_nav = $bp_nouveau->object_nav;
		/**
		 * Use the filter to use your specific Navigation.
		 * Use the $n param to check for your custom object.
		 *
		 * @since 3.0.0
		 *
		 * @param array $nav The list of item navigations generated by the BP_Core_Nav API.
		 * @param array $n   The arguments of the Navigation loop.
		 */
		$nav = apply_filters( 'bp_nouveau_get_nav', $nav, $n );
	}
	// The navigation can be empty.
	if ( $nav === false ) {
		$nav = array();
	}
	$bp_nouveau->sorted_nav = array_values( $nav );
	if ( 0 === count( $bp_nouveau->sorted_nav ) || ! $bp_nouveau->displayed_nav ) {
		unset( $bp_nouveau->sorted_nav, $bp_nouveau->displayed_nav, $bp_nouveau->object_nav );
		return false;
	}
	$bp_nouveau->current_nav_index = 0;
	return true;
}

/* Update post actions menu by removing Spam item */
function bfc_change_topic_admin_links ($r) {
	$r['links'] = apply_filters( 'bfc_topic_admin_links', array(
		'edit' => bbp_get_topic_edit_link ( $r ),
		'close' => bbp_get_topic_close_link( $r ),
		'stick' => bbp_get_topic_stick_link( $r ),
		'merge' => bbp_get_topic_merge_link( $r ),
		'trash' => bbp_get_topic_trash_link( $r ),
		'reply' => bbp_get_topic_reply_link( $r )
		), $r['id'] );
	return $r['links'] ;
}
add_filter ('bbp_topic_admin_links', 'bfc_change_topic_admin_links' ) ;

/* Replace post actions menu text with icons, add title to show up as tooltips */
function bfc_filter_bbp_actions_topic_links( $array, $r_id ) {

	$array = str_replace('>Edit<', 'title="Edit this item"><img src="/wp-content/themes/bfcom/assets/images/edit.svg"><', $array);
	$array = str_replace('>Close<', 'title="Close this item"><img src="/wp-content/themes/bfcom/assets/images/close.svg"><', $array);
	$array = str_replace('>Stick<', 'title="Stick this item"><img src="/wp-content/themes/bfcom/assets/images/stick.svg"><', $array);
	$array = str_replace('>Merge<', 'title="Merge this item"><img src="/wp-content/themes/bfcom/assets/images/merge.svg"><', $array);
	$array = str_replace('>Trash<', 'title="Trash this item"><img src="/wp-content/themes/bfcom/assets/images/trash.svg"><', $array);
	$array = str_replace('>Reply<', 'title="Reply to this item"><img src="/wp-content/themes/bfcom/assets/images/reply.svg"><', $array);

    return $array;
};

// add the filter
add_filter( 'bbp_get_topic_admin_links', 'bfc_filter_bbp_actions_topic_links', 10, 2 );

/* Update post reply menu by removing Spam item */
function bfc_change_admin_links ($r) {
	$r['links'] = apply_filters( 'bfc_reply_admin_links', array(
		'edit'  => bbp_get_reply_edit_link ( $r ),
		'move'  => bbp_get_reply_move_link ( $r ),
		'split' => bbp_get_topic_split_link( $r ),
		'trash' => bbp_get_reply_trash_link( $r ),
		'reply' => bbp_get_reply_to_link   ( $r )
		), $r['id'] );
	return $r['links'] ;
}
add_filter ('bbp_reply_admin_links', 'bfc_change_admin_links' ) ;

/* Replace post replies menu text with icons, add title to show up as tooltips */
function bfc_filter_bbp_reply_admin_links( $array, $r_id ) {

	// add title to anchor to show up as tooltips for reply menu icons
	$array = str_replace('>Edit<', 'title="Edit this item"><img src="/wp-content/themes/bfcom/assets/images/edit.svg"><', $array);
	$array = str_replace('>Move<', 'title="Move this item"><img src="/wp-content/themes/bfcom/assets/images/move.svg"><', $array);
	$array = str_replace('>Split<', 'title="Split this item"><img src="/wp-content/themes/bfcom/assets/images/split.svg"><', $array);
	$array = str_replace('>Trash<', 'title="Trash this item"><img src="/wp-content/themes/bfcom/assets/images/trash.svg"><', $array);
	$array = str_replace('>Reply<', 'title="Reply to this item"><img src="/wp-content/themes/bfcom/assets/images/reply.svg"><', $array);

    return $array;
};

// add the filter
add_filter( 'bbp_get_reply_admin_links', 'bfc_filter_bbp_reply_admin_links', 10, 2 );

//removes 'private' and protected prefix for forums
function remove_private_title($title) {
	return '%s';
}

function remove_protected_title($title) {
	return '%s';
}

add_filter('protected_title_format', 'remove_protected_title');
add_filter('private_title_format', 'remove_private_title');

// Adds the Forum after the group name as the title of the forum index page
function bfc_add_forum_to_title ($title){
	$forum_id = bbp_get_forum_id( $forum_id = 0 );
	$title = get_the_title( $forum_id );
	$title .= " Forum";
	return $title;
}

add_filter('bbp_get_forum_title', bfc_add_forum_to_title, 10, 2);
