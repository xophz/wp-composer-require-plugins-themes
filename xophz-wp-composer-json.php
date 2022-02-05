<?php
  /*
    Plugin Name: Xoph'z Wordpress to Composer.json 
    Plugin URI: http://github.com/knerd/xophz-wp-composer-json
    Description: Adds a wake lock to the front end of your site to help your users screen from falling asleep.
    Author: Xopher Pollard
    Version: 0.0.1
    Author URI: http://github.com/xopherdeep/
  */
use Composer\Console\Application;
    use Symfony\Component\Console\Input\ArrayInput;
    use Symfony\Component\Console\Output\BufferedOutput;

// require all of our src files
require_once( plugin_dir_path(__FILE__) . '/lib/autoload.php'); // <- change happening here

function custom_menu() { 
  add_management_page( 
      'WP Composer to JSON', 
      'WP to Composer', 
      'edit_posts', 
      'wp-composer-json', 
      'my_function', 
     );
}
function my_function()
{
  ?>
  <h1>Wordpress to composer.json file</h1>
  <hr/>
  <pre>
  - Get list of plugins
  - For each plugin...
    - check for public plugin repo at wpackist
    - If public repo available
      - add info to repository
      - add repo to require command
    - else - check for published repo? packigst?
  - save list of repos to composer.json
  - ask to run composer require $PLUGINS

  - do same thing for themes.
  - test if new composer.json works without issue
  - if successful - save composer.json
  - else - save old composer.json
  <!-- <textarea value="<?= $composerJson?>"/> -->
  </pre>
  <form action="http://hallofthegods.docksal/wp-admin/tools.php?page=wp-composer-json" method="GET">
    composer require EVERYTHING?
    <input type="hidden" value="true" name="batch_install"/>
    <input type="hidden" name="page" value="wp-composer-json"/>
    <button class="button button-primary">Go for it!</button>
  </form>
  <?php
    // - Get list of plugins
    $all_plugins = get_plugins();
    // $wpackagist = "https://wpackagist.org/p/wpackagist-{TYPE}/{basename}.json";
    $wordpress = "https://wordpress.org/plugins/";
    // var_dump($json);
    extract($_GET, EXTR_PREFIX_ALL, "get");

    if($get_type && $get_basename){
      composer_require($get_type, $get_basename);
    }

    ob_end_flush();
    session_start();

    ob_start();
    $type = "plugin";

    $json = json_decode(file_get_contents( ABSPATH . '../composer.json'), true);

    if($get_batch_install){
      foreach ($all_plugins as $file => $plugin_data) {
        if( 
          array_key_exists($package, $json['require']) 
        ){
          // unset($_SESSION["wp-composer-json"][$package]);
          continue;
        }
        $basename = dirname( $file );
        composer_require($type, $basename);
      }
    }

    $json = json_decode(file_get_contents( ABSPATH . '../composer.json'), true);

    foreach ($all_plugins as $file => $plugin_data) {
      $basename = dirname( $file );
      $version = $plugin_data['Version'];
      $package = "wpackagist-{$type}/{$basename}";
      if( 
        array_key_exists($package, $json['require']) 
        || array_key_exists($package, $_SESSION["wp-composer-json"]) 
      ){
        // unset($_SESSION["wp-composer-json"][$package]);
        continue;
      }
      ?>
        <form action="http://hallofthegods.docksal/wp-admin/tools.php?page=wp-composer-json" method="GET">
          <?= $plugin_data['Name']?>
          <input type="hidden" name="page" value="wp-composer-json"/>
          <input type="hidden" name="basename" value="<?= $basename ?>"/>
          <input type="hidden" name="type" value="plugin"/>
          <input type="hidden" name="version" value="<?= $version ?>"/>
          <button class="button button-primary">composer require <?= $basename ?></button>
          <br/>
          <br/>
        </form>
        <hr/>
      <?php
    }

    ?>
        <h3>
          Attempts...
        </h3>
    <?php
      foreach ($_SESSION["require"] as $package => $output) {
        echo "<h4>{$package}</h4>";
        echo "$output";
        echo "<br/>";
        # code...
      }
}

add_action('admin_menu', 'custom_menu');

function url_exists($url) {
    return curl_init($url) !== false;
}

$prefix = 'igp';
$panel  = new \TDP\OptionsKit( $prefix );
$panel->set_page_title( __( 'Wordpress to Composer.json' ) );

/**
 * Setup the menu for the options panel.
 *
 * @param array $menu
 *
 * @return array
 */
function igp_setup_menu( $menu ) {
	// These defaults can be customized
	$menu['parent'] = 'tools.php';
	// $menu['menu_title'] = 'Settings Panel';
	// $menu['capability'] = 'manage_options';

	$menu['page_title'] = __( 'WP to Composer' );
	$menu['menu_title'] = $menu['page_title'];

	return $menu;
}

add_filter( 'igp_menu', 'igp_setup_menu' );

/**
 * Register settings tabs.
 *
 * @param array $tabs
 *
 * @return array
 */
function igp_register_settings_tabs( $tabs ) {
	return array(
		'general' => __( 'General' ),
	);
}

add_filter( 'igp_settings_tabs', 'igp_register_settings_tabs' );

/**
 * Register settings subsections (optional)
 *
 * @param array $subsections
 *
 * @return array
 */
function igp_register_settings_subsections( $subsections ) {
	return $subsections;
}

add_filter( 'igp_registered_settings_sections', 'igp_register_settings_subsections' );

/**
 * Register settings fields for the options panel.
 *
 * @param array $settings
 *
 * @return array
 */
function igp_register_settings( $settings ) {
	$settings = array(
		'general' => array(
			array(
				'id'   => 'api_key',
				'name' => __( 'API Key' ),
				'desc' => __( 'Add your API key to get started' ),
				'type' => 'text',
			),
			array(
				'id'   => 'results_limit',
				'name' => __( 'Results Limit' ),
				'type' => 'text',
				'std'  => 10,
			),
			array(
				'id'   => 'start_date',
				'name' => __( 'Start Date' ),
				'type' => 'text',
			),
		),
	);

	return $settings;
}

add_filter( 'igp_registered_settings', 'igp_register_settings' );

function output($str) {
    echo $str;
    ob_end_flush();
    ob_flush();
    flush();
    ob_start();
}


function composer_require($type, $basename){
    ob_end_flush();
    ob_flush();
    if($type && $basename){
      // Let's attempt to run composer require
      $project_root = ABSPATH;
      // Lets get to the root dir with composer.json
      if(file_exists("$project_root/composer.json")){
        $dir = $project_root;
      } else if (file_exists("$project_root/../composer.json")) {
        $dir = "$project_root/..";
      }

      chdir($dir);
      // "":"1.0.77.34"
      //Create the commands
      $package = "wpackagist-{$type}/{$basename}";
      $args = [
        'command' => "require" , 
        'packages' => [$package],
        // 'version' => $get_version ?? null
      ];
      $input = new ArrayInput($args);

      //Create the application and run it with the commands
      $application = new Application();
      $application->setAutoExit(false);
      $application->setCatchExceptions(false);
      $output = new BufferedOutput();
      $application = new Application();
      $application->setAutoExit(false);
      $application->run($input, $output); //, $output);
      ob_start();
      session_start();
      $_SESSION["wp-composer-json"][$package] = "failed" ;
      $out = $output->fetch();
      output("<pre>".$out."</pre>");
    }
}