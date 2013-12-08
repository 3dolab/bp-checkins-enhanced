<?php
/*
 * Borrowed heavily from Ronald Huereca.  Thanks!
 */
if(!class_exists('quick_Ajax_Registration')){
class quick_Ajax_Registration {

	//Constructors
	function Ajax_Registration() {
		$this->__construct();
	}
	function __construct() {
		//add scripts
		//add_action( 'wp_print_scripts', array( &$this, 'add_scripts' ) );
		//add_action( 'template_redirect', array( &$this, 'add_scripts' ) );
		add_action( 'maintenance_head', array( &$this, 'print_scripts' ) );
		//ajax
		add_action( 'wp_ajax_nopriv_submitajaxregistration', array( &$this, 'ajax_process_registration' ) );
		add_action( 'wp_ajax_submitajaxregistration', array( &$this, 'ajax_process_registration' ) );
		//add_action( 'login_form', array( &$this, 'quick_ajax_registration_form' ) );
		add_action( 'maintenance_after_login_form', array( &$this, 'quick_ajax_registration_form' ) );
	}
	//Add the registration script to a page
	function add_scripts() { 
		if ( is_admin() )
                    return;
                wp_enqueue_script( 'json2' );
		wp_enqueue_script( 'ajax-registration-js', plugins_url( 'js/registration.full.js' ,__FILE__ ), array( 'jquery' ), '1.6' );
		wp_localize_script( 'ajax-registration-js', 'ajaxregistration', array( 'Ajax_Url' => admin_url( 'admin-ajax.php' ),
			'waiting' 	=> __('Waiting...', 'bp-checkins-enhanced'),
			'inuse'		=> __('E-mail address is already in use', 'bp-checkins-enhanced'),
			'thankyou'	=> __('Thank You for your visit', 'bp-checkins-enhanced'),
			'go'		=> __('Go!', 'bp-checkins-enhanced'),
			'success'	=> __('Registration Successful', 'bp-checkins-enhanced')
			) );
                
	}
	function print_scripts() { 
		if ( is_admin() )
                    return;
			echo '<script type="text/javascript" src="'.includes_url( 'js/json2.js').'"></script>';
			echo '<script type="text/javascript" src="'.plugins_url( 'js/registration.full.js' ,__FILE__ ).'"></script>';
			echo '<script>var ajaxregistration = {"Ajax_Url":"'.admin_url( 'admin-ajax.php' ).'",
				"waiting":"'.__('Waiting...', 'bp-checkins-enhanced').'",
				"inuse":"'.__('E-mail address is already in use', 'bp-checkins-enhanced').'",
				"thankyou":"'.__('Thank You for your visit', 'bp-checkins-enhanced').'",
				"go":"'.__('Go!', 'bp-checkins-enhanced').'",
				"success":"'.__('Registration Successful', 'bp-checkins-enhanced').'",
				};</script>';
	}
        function ajax_process_registration() {
            
		//Verify the nonce
		check_ajax_referer( 'submit_ajax-registration' );
                require_once( ABSPATH . WPINC . '/class-json.php' );
		//Get post data
		if ( !isset( $_POST['ajax_form_data'] ) ) die("-1");
		parse_str( $_POST['ajax_form_data'], $form_data );
		//Get the form fields
		$email = sanitize_text_field( $form_data['email'] );
		//$citta = sanitize_text_field( $form_data['cimy_uef_CITTA'] );
		//$citta = $form_data['cimy_uef_CITTA'];

		$error_response = $success_response = new Services_JSON();
		$errors = new WP_Error();
		//Check required fields

		if ( empty( $email ) )
			$errors->add( 'email', __( 'You must fill out an e-mail address', 'bp-checkins-enhanced' ) );

		//Do e-mail address validation
		if ( !is_email( $email ) ) 
			$errors->add( 'email', __( 'E-mail address is invalid', 'bp-checkins-enhanced' ) );
		
		if ( email_exists( $email ) ) 
			$errors->add( 'email', __( 'E-mail address is already in use', 'bp-checkins-enhanced' ) );
/* 
		if ( empty ( $citta ) || $citta == '-scegli-'){
			$errors->add( 'email', __( 'You must fill out your city', 'bp-checkins-enhanced' ) );
			echo $error_response->encode($errors);
			die();
		}
*/
		if ( count ( $errors->get_error_codes() ) > 0 ) {
			echo $error_response->encode($errors);
			die();
		}
		//Everything has been validated, proceed with creating the user
		//Create the user
		$user_pass = wp_generate_password();

		$user = array(
			'user_login' => $email,
			'user_pass' => $user_pass,
			'user_email' => $email,
			'show_admin_bar_front' => 'false',
            //'role' => 'wpec_dd_subscriber'
			'role' => 'pending'
		);
                
		$user_id = wp_insert_user( $user );

                if( is_wp_error($user_id) ) {
					$errors->add( 'user', __( 'Could not add user', 'bp-checkins-enhanced' ) );
                }

		//$cityresult = set_cimyFieldValue($user_id, 'CITTA', $citta);

		//If any further errors, send response
		if ( count ( $errors->get_error_codes() ) > 0 ) {
			echo $error_response->encode($errors);
			die();
		}


		/*Send e-mail to admin and new user -
		You could create your own e-mail instead of using this function */
                 wp_new_user_notification( $user_id, $user_pass );

		//Send back a response
		$success = array(
                    'data' => __( 'User registration successful. Please check your e-mail', 'bp-checkins-enhanced' )
		);
		echo $success_response->encode($success);
		die();

	} //end ajax_process_registration
      
	  	function quick_ajax_registration_form() {

            ?>
	
    <form style="float:right; margin: 2em 0 0 2em;" class='ajax-registration-form login-form'>
		<?php echo wp_nonce_field( 'submit_ajax-registration', '_registration_nonce', true, false ); ?>
		<div class="header">
			<h1><?php  _e( 'Receive Updates', 'bp-checkins-enhanced' ); ?></h1>
		</div>
		<div class="content">
			<div class='ajax-registration-list content footer subscribe'>
			<input type='text' size='30' name='email' class="email input<?php //_e( 'enter e-mail address', 'bp-checkins-enhanced' ); ?>"/></li>
		</div>
		<div class="footer">
			<input type='submit' class="ajax-submit subscribe_registration button" value='<?php _e( 'Subscribe', 'bp-checkins-enhanced' ); ?>' name='ajax-submit' /></li>
			<div class='registration-status-message'></div>
			</div>
		</div>
    </form>
	<br style="clear:both;" />
        <?php
        }
	  
    }
	
$ajaxregistration = new quick_Ajax_Registration();
}




if ( !function_exists('wp_new_user_notification') ) {
    function wp_new_user_notification($user_id, $plaintext_pass = '') {
            $user = new WP_User($user_id);

            $user_login = stripslashes($user->user_login);
            $user_email = stripslashes($user->user_email);

            // The blogname option is escaped with esc_html on the way into the database in sanitize_option
            // we want to reverse this for the plain text arena of emails.
            $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

            $message  = sprintf(__('New user registration on your site %s:'), $blogname) . "\r\n\r\n";
            $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
            $message .= sprintf(__('E-mail: %s'), $user_email) . "\r\n";

            @wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);

            if ( empty($plaintext_pass) )
                    return;

            $message  = sprintf(__('Username: %s'), $user_login) . "\r\n";
            $message .= sprintf(__('Password: %s'), $plaintext_pass) . "\r\n";
            $message .= wp_login_url() . "\r\n";

            wp_mail($user_email, sprintf(__('[%s] Your username and password'), $blogname), $message);

    }
}
?>