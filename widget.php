<?php
date_default_timezone_set('UTC');
require_once dirname( __FILE__ ) . '/OAuth/tmhOAuth.php';
require_once dirname( __FILE__ ) . '/OAuth/tmhUtilities.php';
require_once dirname( __FILE__ ) . '/edcrypt.php';


add_action('widgets_init','jpst_register_widget');

function jpst_register_widget() {
	register_widget('jpst_widget');
	register_widget('Recent_Posts_extended');
}


class jpst_widget extends WP_Widget {

	function __construct(){
		$widget_ops = array(
			'classname' => 'jpst_twidget',
			'description' => __('Display a Users timeline Widget','jpst-plugin'),
			''
			);
			parent::__construct('jpst_feed_widget',__('Twitter Widget w/ Cache','jpst-plugin'),$widget_ops);
	
	}
	
	function form($instance){
		$now = time();
		$defaults = array(
			'title' => 'Twitter(%usern%)',
			'user' => '',
			'numtweets' => '5',
			'frequency' => '6',
			'inclrt' => 1,
			'exclrep' => 0,
			'lastreq' => $now,
		);
		
		
		$instance = wp_parse_args((array) $instance, $defaults);
		$title = strip_tags($instance['title']);
		$user = strip_tags($instance['user']);
		$numtweets = strip_tags($instance['numtweets']);
		$frequency = strip_tags($instance['frequency']);
		$inclrt = $instance['inclrt'];
		$exclrep = $instance['exclrep'];
		?>
		<p><?php echo __('Last Tweet Retrival','jpst-plugin') . "<br/>" . date("m/d/Y G:i:s",$instance['lastreq']) . " UTC" ?></p>
		<p><?php _e('Title','jpst-plugin') ?>: 
		  <input class="widefat" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		</p>
		<p><?php _e('Username','jpst-plugin') ?>: 
		  <input class="widefat" name="<?php echo $this->get_field_name('user'); ?>" type="text" value="<?php echo esc_attr($user); ?>" />
		</p>
		<p><?php _e('Number of Tweets','jpst-plugin') ?>: 
		  <input class="widefat" name="<?php echo $this->get_field_name('numtweets'); ?>"	type="text" value="<?php echo esc_attr($numtweets); ?>" />
		</p>
		<p><?php echo __('Frequency of retrieving tweets','jpst-plugin') . '(' . __('Minutes','jpst-plugin') . ')' ?>: 
		  <input class="widefat" name="<?php echo $this->get_field_name('frequency'); ?>"	type="text" value="<?php echo esc_attr($frequency); ?>" />
		</p>
		<p><?php echo __('Include ReTweets?','jpst-plugin') ?>: 
		  <input type="checkbox" name="<?php echo $this->get_field_name('inclrt'); ?>" <?php if ($inclrt == 1) echo 'checked=checked'; ?> value="<?php echo $inclrt; ?>" />
		</p>
		<p><?php echo __('Exclude Replies?','jpst-plugin') ?>: 
		  <input type="checkbox" name="<?php echo $this->get_field_name('exclrep'); ?>"	<?php if ($exclrep == 1) echo 'checked=checked'; ?> value="<?php echo $exclrep; ?>" />
		</p>

<?php }
	
	
	function update($new_instance,$old_instance) {
		$instance = $old_instance;
		delete_transient('thetweets-' . $this->id);
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['user'] = strip_tags($new_instance['user']);
		$instance['inclrt'] = (isset( $new_instance['inclrt'] ) ? 1 : 0);
		$instance['exclrep'] = (isset( $new_instance['exclrep'] ) ? 1 : 0);
		$instance['numtweets'] = strip_tags($new_instance['numtweets']);
		$instance['frequency'] = strip_tags($new_instance['frequency']);
		
		return $instance;
	}
	
	function widget($args, $instance) {
		extract($args);
		
		echo $before_widget;
		$this->displayTweets($instance);
		echo $after_widget;

	}
	
	function displayTweets($instance) {
		extract(get_option('jpst_oauth'));
		$exp = $instance['frequency'] * 60;
		$cache = get_transient('thetweets-' . $this->id);
		if(empty($cache)) { 

			$tmhOAuth = new tmhOAuth(array(
			  'consumer_key'    => decrypt($jpst_consumer_key),
			  'consumer_secret' => decrypt($jpst_consumer_secret),
			  'user_token'      => decrypt($jpst_user_token),
			  'user_secret'     => decrypt($jpst_user_secret)
			));
			
			$code = $tmhOAuth->request('GET', $tmhOAuth->url('1/statuses/user_timeline'), array(
			  'include_entities' => '1',
			  'include_rts'      => $instance['inclrt'],
			  'exclude_replies'  => $instance['exclrep'],
			  'screen_name'      => $instance['user'],
			  'count'            => $instance['numtweets']
			));
			
			if ($code == 200) {
			  $response = $tmhOAuth->response['response'];
			  //Update instance with json of tweets and time of request for caching
			  //print_r($tmhOAuth->response['headers']);
			  set_transient('thetweets-' . $this->id, $response, $exp);

			  //echo '  New Results<br/>';
			  $this->createMarkup($response, $instance['title']);
			  
			} else {
			  tmhUtilities::pr($tmhOAuth->response);
			}
			
		} else {
			//echo 'Cached Results<br/>';
			$this->createMarkup($cache, $instance['title']);
		};
	
	
	}
	
	function createMarkup($resp, $title) {
	    $timeline = json_decode($resp, true);
		$titext = str_replace(
					array(
					  '%usern%',
					  '%lsttwt%'
					  ),
					array(
						$timeline[0]['user']['screen_name'],
						$this->displayTwtDate($timeline[0]['created_at'])
						),
						$title
					);
		echo "<h3>{$titext}</h3>";

	  foreach ($timeline as $tweet) :
		$entified_tweet = tmhUtilities::entify_with_options($tweet, array('target' => '_blank'));
		$is_retweet = isset($tweet['retweeted_status']);

		$created_at = $this->displayTwtDate($tweet['created_at']);

		$permalink  = str_replace(
		  array(
			'%screen_name%',
			'%id%',
			'%created_at%'
		  ),
		  array(
			$tweet['user']['screen_name'],
			$tweet['id_str'],
			$created_at,
		  ),
		  '<a href="https://twitter.com/%screen_name%/status/%id%" target="_blank" >%created_at%</a>'
		);
		
	  ?>
	  <div id="<?php echo $tweet['id_str']; ?>" style="margin-bottom: 1em; word-wrap:break-word;">
		<span><?php echo $entified_tweet ?></span><br>
		<small><?php echo $permalink ?><?php if ($is_retweet) : ?> is retweet<?php endif; ?>
		<span>via <?php echo str_replace('<a ','<a target="_blank"',$tweet['source']);?></span></small>
	  </div>
	<?php
	  endforeach;

	}
	
	function displayTwtDate($dt) {
		
		$diff = time() - strtotime($dt);
		if ($diff < 60*60)
		  return floor($diff/60) . ' minutes ago';
		elseif ($diff < 60*60*24)
		  return floor($diff/(60*60)) . ' hours ago';
		else
		  return date('d M', strtotime($dt));
	
	}


}


class Recent_Posts_extended extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'widget_recent_entries_extended', 'description' => __( "The most recent custom posts on your site") );
		parent::__construct('recent-posts-custom', __('Recent Custom Posts'), $widget_ops);
		$this->alt_option_name = 'widget_recent_entries_extended';

		add_action( 'save_post', array(&$this, 'flush_widget_cache') );
		add_action( 'deleted_post', array(&$this, 'flush_widget_cache') );
		add_action( 'switch_theme', array(&$this, 'flush_widget_cache') );
	}

	function widget($args, $instance) {
		$cache = wp_cache_get('widget_recent_posts_extended', 'widget');

		if ( !is_array($cache) )
			$cache = array();

		if ( ! isset( $args['widget_id'] ) )
			$args['widget_id'] = $this->id;

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		ob_start();
		extract($args);

		$title = apply_filters('widget_title', empty($instance['title']) ? __('Recent Posts') : $instance['title'], $instance, $this->id_base);
		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) )
 			$number = 10;

		$cstposttype = $instance['customtype'];
		$r = new WP_Query(array('post_type' => $cstposttype, 'posts_per_page' => $number, 'no_found_rows' => true, 'post_status' => 'publish', 'ignore_sticky_posts' => true));
		if ($r->have_posts()) :
?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<ul>
		<?php  while ($r->have_posts()) : $r->the_post(); ?>
		<li><a href="<?php the_permalink() ?>" title="<?php echo esc_attr(get_the_title() ? get_the_title() : get_the_ID()); ?>"><?php if ( get_the_title() ) the_title(); else the_ID(); ?></a></li>
		<?php endwhile; ?>
		</ul>
		<?php echo $after_widget; ?>
<?php
		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();

		endif;

		$cache[$args['widget_id']] = ob_get_flush();
		wp_cache_set('widget_recent_posts_extended', $cache, 'widget');
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$instance['customtype'] = $new_instance['customtype'];
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['widget_recent_entries_extended']) )
			delete_option('widget_recent_entries_extended');

		return $instance;
	}

	function flush_widget_cache() {
		wp_cache_delete('widget_recent_posts_extended', 'widget');
	}

	function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$number = isset($instance['number']) ? absint($instance['number']) : 5;
		$selectedct = isset($instance['customtype']) ? esc_attr($instance['customtype']) : 'work';
		
		$cstdropdown = '<select id="' . $this->get_field_id('customtype') . '" name="' . $this->get_field_name('customtype') . '" >';
		$args=array(
			  'public'   => true,
			  '_builtin' => false
			); 
		$output = 'names'; // names or objects, note names is the default
		$operator = 'and'; // 'and' or 'or'
		$post_types=get_post_types($args,$output,$operator); 
		  foreach ($post_types  as $post_type ) {
			$cstdropdown .= ($selectedct != $post_type) ? "<option value='{$post_type}'>". $post_type. '</option>': "<option value='{$post_type}' selected='selected' >". $post_type. '</option>' ;
		  }
			  
		$cstdropdown .= '</select>';
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of posts to show:'); ?></label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
		
		<p><label for="<?php echo $this->get_field_id('customtype'); ?>"><?php _e('Post Type:'); ?></label>
		<?php echo $cstdropdown; ?>
<?php
	}
}

?>