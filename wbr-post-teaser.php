<?php
/**
 * Plugin Name: WBR Post Teaser
 * Plugin URI: http://github.com/iamwebrocker/wbr-post-teaser
 * Description: This plugin adds a widget to the sidebar.
 * Version: 0.12
 * Author: Tom Arnold
 * Author URI: http://www.webrocker.de
/* -------------------------------------------------- */
/* test mit sidebar-widgets, wp 2.8 style
/* -------------------------------------------------- */

class WBR_Widget_Post_Teaser extends WP_Widget {

	function WBR_Widget_Post_Teaser(){
		$widget_ops = array(
			'classname' => 'widget_wbr_post_teaser',
			'description' => __( "Gig Teaser")
		);
		$this->WP_Widget('wbr_post_teaser', __('WBR Post-Teaser'), $widget_ops);
	}
	
	function widget( $args, $instance ) {
		extract($args);
		$out = $before_widget;
		if( $instance['title'] != ''){
			$out .= $before_title;
			$out .= $instance['title'];
			$out .= $after_title;
		} 
		$teaserPosts = new WP_Query();
		$query_str = 'showposts='.$instance['number'];
		if( $instance['postcat'] != '' ){
			$query_str .= '&cat='.$instance['postcat'];
		}
		if( $instance['postorder'] != '' ){
			$query_str .= '&orderby=meta_value&meta_key='.$instance['postorder'];
		}
		$teaserPosts->query( $query_str );
		setlocale (LC_TIME, "de_DE");
		if ( $teaserPosts->have_posts() ) {
			$out .= '<ul class="wbr_post_teaser_box">'."\n";
			while ( $teaserPosts->have_posts() ) {
				$teaserPosts->the_post();
				$postID = get_the_ID();
				if( $instance['postorder'] != '' ){
					// heute:
					$today = date('Y-m-d');
					// dann:
					$gigdate = get_post_meta($postID, 'Date',true);
					//
					if ( strtotime($today) > strtotime($gigdate) )
						$class = 'outdated';
					$meta_date = strftime( $instance['dateformat'], strtotime($gigdate) );
					$teaser_title = '<span class="wbr_post_teaser_date">'.$meta_date.':</span> <span class="wbr_post_teaser_city">'.get_post_meta($postID, 'City',true).',</span> <span class="wbr_post_teaser_venue">'.get_post_meta($postID, 'Venue',true).'</span>'."\n";
					$meta_start = ( '' !== get_post_meta($postID, 'Start',true) )?'<li class="wbr_post_teaser_meta_item"><span class="wbr_post_teaser_meta_label">Beginn:</span> <span class="wbr_post_teaser_meta_value">'.get_post_meta($postID, 'Start',true).'</span></li>'."\n":'';
					$meta_cost = ( '' !== get_post_meta($postID, 'Price',true) )?'<li class="wbr_post_teaser_meta_item"><span class="wbr_post_teaser_meta_label">Eintritt:</span> <span class="wbr_post_teaser_meta_value">'.get_post_meta($postID, 'Price',true).'</span></li>'."\n":'';
					if( $meta_start !== '' || $meta_scost !== '' ){
						$teaser_content = '<ul class="wbr_post_teaser_meta_box">'."\n";
						$teaser_content .= $meta_start;
						$teaser_content .= $meta_cost;
						$teaser_content .= '<!-- .wbr_post_teaser_meta_box --></ul>'."\n";
					}
				} else {
					$teaser_title = get_the_title();
					$teaser_content = '<p>'.get_the_excerpt().'</p>'."\n";
				}
				$out .= '<li class="wbr_post_teaser_item '.$class.'">'."\n";
				$out .= '<h3><a href="' . get_permalink($myID) . '" rel="bookmark" title="' . sprintf(__('Permanent Link to %s', 'kubrick'), the_title_attribute('echo=0')) . '">' . $teaser_title . '</a></h3>'."\n";
				$out .= $teaser_content;				
				$out .= '<!-- .wbr_post_teaser_item --></li>'."\n";
			}
			$out .= '<!-- .wbr_post_teaser_box --></ul>'."\n";
		}
		$out .= $after_widget;
		wp_reset_query();
		echo $out;		
	}
	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['postorder'] = strip_tags($new_instance['postorder']);
		$instance['dateformat'] = strip_tags($new_instance['dateformat']);
		$instance['postcat'] = (int) $new_instance['postcat'];
		$instance['number'] = (int) $new_instance['number'];
		return $instance;
	}
	
	function form( $instance ) {
		// backend
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'postorder' => '', 'dateformat' => '' ) );
		$title = strip_tags($instance['title']);
		$postorder = strip_tags($instance['postorder']);
		$dateformat = strip_tags($instance['dateformat']);
		if ( $dateformat == '')
			$dateformat = '%a, %d.%m.%y';
		if ( !$postcat = (int) $instance['postcat'] )
			$postcat = '';
		if ( !$number = (int) $instance['number'] )
			$number = 5;
			
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>
		<p><label for="<?php echo $this->get_field_id('postcat'); ?>"><?php _e('Category '); ?>ID:</label>
		<input id="<?php echo $this->get_field_id('postcat'); ?>" name="<?php echo $this->get_field_name('postcat'); ?>" type="text" value="<?php echo $postcat; ?>" size="3" /><br />
		<small><?php _e('(ID der Gigs-Kategorie)'); ?></small></p>
		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of Posts to show:'); ?></label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /><br />
		<small><?php _e('(at most 15)'); ?></small></p>
		<p><label for="<?php echo $this->get_field_id('postorder'); ?>"><?php _e('Custom Order Field:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('postorder'); ?>" name="<?php echo $this->get_field_name('postorder'); ?>" type="text" value="<?php echo esc_attr($postorder); ?>" /></p>
		<p><label for="<?php echo $this->get_field_id('dateformat'); ?>"><?php _e('Dateformat:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('dateformat'); ?>" name="<?php echo $this->get_field_name('dateformat'); ?>" type="text" value="<?php echo esc_attr($dateformat); ?>" /><br />
		<small><?php _e('zb %d.%m.%Y'); ?></small></p>
<?php		
	}
}
function wbr_postteaser_init() {
	register_widget('WBR_Widget_Post_Teaser');
}
add_action('init', 'wbr_postteaser_init', 1);
?>