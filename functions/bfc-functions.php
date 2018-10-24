<?php

// All of the bfcom-specific functions

// Remove the Toolbar for all users
add_filter('show_admin_bar','__return_false');

// This is based on https://codex.buddypress.org/developer/navigation-api/
function bfc_nav_configure() {
	global $bp;
    if( bp_is_group() ) {
		$bp->groups->nav->edit_nav( array( 'name' => __( 'G-Dash', 'buddypress' )), 'home', bp_current_item() );

		// comment those you want to show
	   $hide_tabs = array(             
        	'announcements'    => 1,
        	'invite-anyone'    => 1,
        	'notifications'    => 1,
        );
                   
        $parent_nav_slug = bp_get_current_group_slug();
  
		//Remove the nav items
		foreach ( array_keys( $hide_tabs ) as $tab ) {
			bp_core_remove_subnav_item( $parent_nav_slug, $tab, 'groups' );
		}   
    } elseif ( bp_is_user() ) {
		$hide_tabs = array(             
        	'invite-anyone'    => 1,
        	'following'    => 1,
        	'followers'    => 1,
        	'forums'   		   => 1,
        );
                     
		//Remove the nav items
		foreach ( array_keys( $hide_tabs ) as $tab ) {
			bp_core_remove_nav_item( $tab, 'members' );
		}   

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
