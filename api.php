<?php
add_action( 'rest_api_init', 'register_api_hooks' );

// https://xyz.com/wp-json/custom-plugin/login
function register_api_hooks() {
  register_rest_route(
    'custom-plugin', '/login/',
    array(
      'methods'  => 'POST',
      'callback' => 'login',
    )
  );
}

function login($request){
    $creds = array();
    $creds['user_login'] = $request["username"];
    $creds['user_password'] =  $request["password"];
    $creds['remember'] = true;
    $user = wp_signon( $creds, false );

    if ( is_wp_error($user) )
{
       $return = array(
    'message' => __( 'Email & Password Not Matched', 'textdomain' )
);
 
       wp_send_json_error( $return );
}
else
{
      $user_meta=get_userdata($user->ID);
$return = array(
'ID'      => $user->ID,
'username'      => $user->user_login,
'email'      => $user->user_email,
'role'      => $user_meta->roles[0]
);
wp_send_json_success( $return );
}
}

add_action( 'after_setup_theme', 'custom_login' );




add_action('rest_api_init', 'wp_rest_user_endpoints');
/**
 * Register a new user
 *
 * @param  WP_REST_Request $request Full details about the request.
 * @return array $args.
 **/
  // https://xyz.com/wp-json/wp/v2/users/register
  
function wp_rest_user_endpoints($request) {
  /**
   * Handle Register User request.
   */
  register_rest_route('wp/v2', 'users/register', array(
    'methods' => 'POST',
    'callback' => 'wc_rest_user_endpoint_handler',
  ));
}
function wc_rest_user_endpoint_handler($request = null) {
  $response = array();
  $parameters = $request->get_json_params();
  $username = sanitize_text_field($parameters['username']);
  $email = sanitize_text_field($parameters['email']);
  $password = sanitize_text_field($parameters['password']);
  // $role = sanitize_text_field($parameters['role']);
  $error = new WP_Error();
  if (empty($username)) {
    $error->add(400, __("Username field 'username' is required.", 'wp-rest-user'), array('status' => 400));
    return $error;
  }
  if (empty($email)) {
    $error->add(401, __("Email field 'email' is required.", 'wp-rest-user'), array('status' => 400));
    return $error;
  }
  if (empty($password)) {
    $error->add(404, __("Password field 'password' is required.", 'wp-rest-user'), array('status' => 400));
    return $error;
  }
  // if (empty($role)) {
  //  $role = 'subscriber';
  // } else {
  //     if ($GLOBALS['wp_roles']->is_role($role)) {
  //      // Silence is gold
  //     } else {
  //    $error->add(405, __("Role field 'role' is not a valid. Check your User Roles from Dashboard.", 'wp_rest_user'), array('status' => 400));
  //    return $error;
  //     }
  // }
  $user_id = username_exists($username);
  if (!$user_id && email_exists($email) == false) {
    $user_id = wp_create_user($username, $password, $email);
    if (!is_wp_error($user_id)) {
      // Ger User Meta Data (Sensitive, Password included. DO NOT pass to front end.)
      $user = get_user_by('id', $user_id);
      // $user->set_role($role);
      $user->set_role('subscriber');
      // WooCommerce specific code
      if (class_exists('WooCommerce')) {
        $user->set_role('customer');
      }
      // Ger User Data (Non-Sensitive, Pass to front end.)
      $response['code'] = 200;
      $response['message'] = __("User '" . $username . "' Registration was Successful", "wp-rest-user");
    } else {
      return $user_id;
    }
  } else {
    $error->add(406, __("Email already exists, please try 'Reset Password'", 'wp-rest-user'), array('status' => 400));
    return $error;
  }
  return new WP_REST_Response($response, 123);
}
?>
