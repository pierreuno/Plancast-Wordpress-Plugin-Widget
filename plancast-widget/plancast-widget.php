<?php
/**
 * Plugin Name: Plancast Widget
 * Plugin URI: http://github.com/pierreuno/Plancast-Wordpress-Plugin-Widget/tree/master/plancast-widget/
 * Description: A widget that displays your plans or friends plans from PlanCast.
 * Version: 0.1beta
 * Author: Pierre Hunault @Exygy
 * Author URI: http://www.pierrehunault.com
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * Add function to widgets_init that'll load our widget.
 */
add_action( 'widgets_init', 'plancast_load_widgets' );

add_action('wp_print_styles', 'add_plancast_stylesheet');

/**
 * Register the widget.
 * 'PlanCast_Widget' is the widget class used below.
 */
function plancast_load_widgets() {
	register_widget( 'PlanCast_Widget' );
}

/**
 * Register the style.
 */
function add_plancast_stylesheet() {
	$myStyleUrl = WP_PLUGIN_URL . '/plancast-widget/plancast-widget.css';
	$myStyleFile = WP_PLUGIN_DIR . '/plancast-widget/plancast-widget.css';
	if ( file_exists($myStyleFile) ) {
		wp_register_style('plancastStyleSheets', $myStyleUrl);
		wp_enqueue_style( 'plancastStyleSheets');
	}
}


class PlanCast_Widget extends WP_Widget {

	/**
	 * Widget setup.
	 */
	function PlanCast_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'plancast', 'description' => __('A widget that displays plancast plans', 'plancast') );

		/* Widget control settings. */
		$control_ops = array( 'width' => 400, 'height' => 300, 'id_base' => 'plancast-widget' );

		/* Create the widget. */
		$this->WP_Widget( 'plancast-widget', __('Plancast Widget', 'plancast'), $widget_ops, $control_ops );
	}


	/**
	 * How to display the widget on the screen.
	 */
	function widget( $args, $instance ) {
		extract( $args );

		/* Our variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title'] );
		$login = $instance['login'];
		$password = $instance['password'];
		$display = $instance['display'];
		$limited_height = $instance['limited_height'];
		
		if($instance['display_powerby'] == "on"){$display_powerby = 1;}else{$display_powerby = 0;};
		if($instance['display_plancastlogo'] == "on"){$display_plancastlogo = 1;}else{$display_plancastlogo = 0;};
		
		

		
		if($instance['plan_number'] != null && $instance['plan_number'] != ""){
			$plan_number = $instance['plan_number'];				
		}else{
			$plan_number = 10;	
		}		



		if ( $login ){ 	
			
				//because the API return X-1 element, idk why
				$url_count = $plan_number + 1;
				
				switch ($display) {
					case "user_plans":
						//API Url: user/show (get the user info)
						$jsonUrl = "http://api.plancast.com/02/users/show.json?username=".$login;
						//get the json string
						$jsonString = file_get_contents($jsonUrl,0);						
						if($jsonString != ""){
							//convert the jsonString into a jsonObject
							$jsonObject = json_decode($jsonString);
							$user_name = $jsonObject->name;
							$user_picture = $jsonObject->pic_square;
							$user_username = $jsonObject->username;							
						}	
						$jsonString = ""; //security		
						//API Url: plans/user
						$jsonUrl = "http://api.plancast.com/02/plans/user.json?username=".$login."&count=".$url_count;
					break;
					case "friends_plans":
						//API Url: plans/home
						$jsonUrl = "http://".$login.":".$password."@api.plancast.com/02/plans/home.json?count=".$url_count;
					break;
				}	
				//get the json string
				$jsonString = file_get_contents($jsonUrl,0);			
				//error if empty
				if($jsonString != ""){
					//convert the jsonString into a jsonObject
					$jsonObject = json_decode($jsonString);
//START DISPLAY -------------------------------------------------------------------------------
					/* Before widget (defined by themes). */
					echo $before_widget;
							
					/* Display the widget title if one was input (before and after defined by themes). */
					if ( $title )
						echo $before_title . $title . $after_title;

					echo '<div id="pc_widget">';

					//display the user profile
					if($display == "user_plans"){
						echo '<div id="pc_user_profile">';
							echo '<div><img id="pc_user_picture" src="'.$user_picture.'" alt="'.$user_name.'"/></div>';
							echo '<div id="pc_user_info"><a target="_blank" href="http://www.plancast.com/'.$user_username.'"><div id="pc_user_name">'.$user_name.'</div><div id="pc_user_username">@'.$user_username.'</div></a></div>';
						echo "</div>";						
					}
					
					if($display == "friends_plans" && $display_plancastlogo == 1){
							echo '<a class="pc_link_to_plancast" id="pc_header_link_to_plancast" target="_blank" href="http://www.plancast.com"><img src="http://plancast.com/images/logo5.png" alt="Go to Plancast"></a>';
					}
					
					//display the plans
					if($jsonObject->plans != "" && $jsonObject->plans != null){
						echo '<div id="pc_div_plan" ';
							if($limited_height != "" && $limited_height != null){ echo 'style="height:'.$limited_height.'px;overflow:auto;"';}
						echo '>';	
						for ($i = 0; $i < $plan_number; $i++) {
							if($jsonObject->plans[$i] != "" && $jsonObject->plans[$i] != null){
								echo '<div class="pc_plan">';
								
								//display the picture + user name
								if($display == "friends_plans"){
									echo '<div class="pc_list_user">';
										echo '<img src="'.$jsonObject->plans[$i]->attendee->pic_square.'" alt="'.$jsonObject->plans[$i]->attendee->name.'"/>';			
										echo '<div class="pc_user_name">'.$jsonObject->plans[$i]->attendee->name.'</div>';
										echo '<div class="pc_user_username"></div>';
									echo '</div>';
								}
								
								echo '<a target="_blank" href="'.$jsonObject->plans[$i]->plan_url.'">';
								echo '<div class="pc_plan_what">'.$jsonObject->plans[$i]->what.'</div>';
								echo '<div class="pc_plan_where_when">'.$jsonObject->plans[$i]->when.' | '.$jsonObject->plans[$i]->where.'</div>';						
								echo '</a></div>';									
							}
						}	
						
						if($display == "user_plans" && $display_plancastlogo == 1){
							echo '<a class="pc_link_to_plancast" id="pc_footer_link_to_plancast" target="_blank" href="http://www.plancast.com"><img src="http://plancast.com/images/logo5.png" alt="Go to Plancast"></a>';
						}
						
						echo '</div>'; //close id=pc_div_plan div	
						
						if($display_powerby == 1){
							echo '<div id="exygy_logo">Powered by <a target="_blank" href="http://exygy.com">Exygy&copy;</a></div>';
						}
						
					}else{ //
						echo "<p>No plans yet.</p>";
					}
					echo '</div>'; //close id=pc_widget div
				}else{
						echo "<p>Wrong login or password.</p>";						
				}
		 }else{
		 	echo "<p>Please specify a login to enable this widget.</p>";	
		 }			
		/* After widget (defined by themes). */
		echo $after_widget;
//END DISPLAY -------------------------------------------------------------------------------		
	}

	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['login'] = strip_tags( $new_instance['login'] );
		$instance['password'] = strip_tags( $new_instance['password'] );		$instance['display'] = strip_tags( $new_instance['display'] );		
		$instance['limited_height'] = strip_tags( $new_instance['limited_height'] );		$instance['plan_number'] = strip_tags( $new_instance['plan_number'] );
		$instance['display_plancastlogo'] = strip_tags( $new_instance['display_plancastlogo'] );
		$instance['display_powerby'] = $new_instance['display_powerby'];
		
		return $instance;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'title' => __('PlanCast', 'plancast'), 'plan_number' => 10 );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:96%;" type="text"/>
		</p>

		<!-- Your Login: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'login' ); ?>"><?php _e('User Name: *', 'plancast'); ?></label>
			<input id="<?php echo $this->get_field_id( 'login' ); ?>" name="<?php echo $this->get_field_name( 'login' ); ?>" value="<?php echo $instance['login']; ?>" style="width:96%;" type="text"/>
		</p>
		
		<!-- Your Password: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'password' ); ?>"><?php _e('Password:', 'plancast'); ?></label>
			<input id="<?php echo $this->get_field_id( 'password' ); ?>" name="<?php echo $this->get_field_name( 'password' ); ?>" value="<?php echo $instance['password']; ?>" style="width:96%;" type="password"/>
		</p>
		
		<!-- Sex: Select Box -->
		<p>
			<label for="<?php echo $this->get_field_id( 'display' ); ?>"><?php _e('Info to display:', 'plancast'); ?></label> 
			<select id="<?php echo $this->get_field_id( 'display' ); ?>" name="<?php echo $this->get_field_name( 'display' ); ?>" class="widefat" style="width:100%;">
				<option value="user_plans" <?php if ( 'user_plans' == $instance['display'] ) echo 'selected="selected"'; ?>>Specified User Plans (no password required)</option>
				<option value="friends_plans" <?php if ( 'friends_plans' == $instance['display'] ) echo 'selected="selected"'; ?>>My Friends Plans (login/password required)</option>
			</select>
		</p>
		
		<!-- Number of plan to display: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'plan_number' ); ?>"><?php _e('Number of plans to display: (limited to 100 | default: 10)', 'plancast'); ?></label>
			<input id="<?php echo $this->get_field_id( 'plan_number' ); ?>" name="<?php echo $this->get_field_name( 'plan_number' ); ?>" value="<?php echo $instance['plan_number']; ?>" style="width:96%;" type="text"/>
		</p>
		
		<!-- Limit the height of the div: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'limited_height' ); ?>"><?php _e('Limit the height to: (in pixels | leave empty for full height)', 'plancast'); ?></label>
			<input id="<?php echo $this->get_field_id( 'limited_height' ); ?>" name="<?php echo $this->get_field_name( 'limited_height' ); ?>" value="<?php echo $instance['limited_height']; ?>" style="width:96%;" type="text"/>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'display_plancastlogo' ); ?>"><?php _e('Display the Plancast logo', 'plancast'); ?></label>
			<input type="checkbox" <?php checked( (bool) $instance['display_plancastlogo'], true ); ?> id="<?php echo $this->get_field_id( 'display_plancastlogo' ); ?>" name="<?php echo $this->get_field_name( 'display_plancastlogo' ); ?>" />
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'display_powerby' ); ?>"><?php _e('Display "Power By"', 'plancast'); ?></label>
			<input type="checkbox" <?php checked( (bool) $instance['display_powerby'], true ); ?> id="<?php echo $this->get_field_id( 'display_powerby' ); ?>" name="<?php echo $this->get_field_name( 'display_powerby' ); ?>" />
		</p>

	<?php
	}
}

?>