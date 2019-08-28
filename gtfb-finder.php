<?php
/*
 * Plugin Name: GTFB / Finder
 * Description: This plugin search for pictures on some popular resources.
 * Version:     0.2.2
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gtfb-f
 * Domain Path: /languages
 */
/**
 * Date: 05.08.2019
 * Time: 12:27
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


$upload_dir = wp_upload_dir();

define( 'GTFB_F_VERSION', '0.2.2' );
define( 'GTFB_F_URI', plugin_dir_url(__FILE__));
define( 'GTFB_F_UPLOAD_PATH', $upload_dir['basedir'].'/gtfb.finder');
define( 'GTFB_F_UPLOAD_URL', $upload_dir['basedir'].'/gtfb.finder/');
define( 'GTFB_F_MAX_DOWNLOAD_WIDTH', 1600);
define( 'GTFB_F_MAX_DOWNLOAD_HEIGHT', 1200);
define( 'GTFB_F_PEXELS_KEY', '563492ad6f91700001000001f103685e04ab4c6d92852ee97f7a314b');
//define( 'GTFB_F_PEXELS_KEY', '563492ad6f91700001000001f27710937a744dc14b607b8c6d8d72d5');
//define( 'GTFB_F_PIXABAY_KEY', '9203188-0acd8506ab42491c4f6cde8d5');
define( 'GTFB_F_PIXABAY_KEY', '1498928-f190b376157b831824bdfb89b');
define( 'GTFB_F_UNSPLASH_KEY', '3705ae2c5aa3644ff3bab3b5768e3f4a67efdff0628cf6a8e7bd14f640044de3');
//define( 'GTFB_F_UNSPLASH_KEY', '5746b12f75e91c251bddf6f83bd2ad0d658122676e9bd2444e110951f9a04af8');
define( 'GTFB_F_FLATICON_KEY', 'ecae6390cee248f260704f34fb81ac0bd8619c90');
define( 'GTFB_F_ICONFINDER_CLIENT_ID', 'tuLM556aa4h6sHszCaLn9lJI4RZbs54l59540iAdZJKpmuU95N7tghPhqK4Gdeez');
define( 'GTFB_F_ICONFINDER_CLIENT_SECRET', 'b6Zjf2ikeRm85XD3eHbQrMzN7hOsWZs3MyCeonrDfrjbhNZTTPuLw8rSrnp0qhmF');
//define("ALLOW_UNFILTERED_UPLOADS", true);


if ( ! class_exists( 'gtfbFinder' ) ) {
  class gtfbFinder {
    /**
     *@var GTFB_Finder_Loader
     */
    private $loader;

    /**
     *@var gtfbFinder
     */
    static $_instance;

    /**
     * @return gtfbFinder
     */
    public static function instance()
    {
      if ( is_null( self::$_instance ) ) {
        self::$_instance = new self();
      }

      return self::$_instance;
    }

    private function  __construct() {
      add_action( 'plugins_loaded', array( $this, 'GTFB_F_load_textdomain' ) );
      add_action( 'admin_menu', array( $this, 'GTFB_F_menu' ) );
      add_action( 'admin_enqueue_scripts', array( $this, 'GTFB_F_load_scripts' ) );

      include_once 'backend/search.php';
      include_once 'backend/file.php';
      include_once 'loader.php';
      $this->loader = GTFB_Finder_Loader::instance();
      new gtfbIsSearch();
      new gtfbIsFile();
    }

    //Добавляем локализацию
    function GTFB_F_load_textdomain() {
      load_plugin_textdomain( 'gtfb-f', false, basename( dirname( __FILE__ ) ) . '/languages/' );
    }

    //Загружаем скрипты
    function GTFB_F_load_scripts() {

      $this->loader->enqueue_admin_scripts();
    }

    //Добавляем меню
    function GTFB_F_menu() {
      add_submenu_page( 'upload.php', esc_html__( 'Finder', 'gtfb-f' ), esc_html__( 'Finder', 'gtfb-f' ), 'manage_options', 'gtfb-f-media', array(
        $this,
        'GTFB_F_media_menu'
      ));

    }

    function GTFB_F_media_menu() {
      ?>
      <div class="GTFB_F_media_container">
        <div class="GTFB_F_header">
          <h1><?php echo esc_html__( 'Finder', 'gtfb-f' ); ?>
            <span><?php esc_html_e( 'You can search & save the image(s) to Media Library.', 'gtfb-f' ); ?></span>
          </h1>
        </div>
        <?php self::GTFB_F_source_panel(); ?>
        <?php self::GTFB_F_result_panel(); ?>
        <?php self::GTFB_F_more_result_panel(); ?>
      </div>
      <?php
    }


    function GTFB_F_source_panel() {
      ?>
      <div class="GTFB_F_source_panel">
        <div>
          <input type="text" id="GTFB_F_search_input" name="wppx_input" class="form-control" placeholder="<?php esc_html_e( 'keyword', 'gtfb-f' ); ?>"/>
          <input type="submit" id="GTFB_F_search_btn" class="btn btn-primary btn-sm" value="<?php esc_html_e( 'Search', 'gtfb-f' ); ?>" GTFB_F_load_text="<?php esc_html_e( 'Loading..', 'gtfb-f' ); ?>" GTFB_F_text="<?php esc_html_e( 'Search', 'gtfb-f' ); ?>"/>
        </div>
        <div>
          <span><?php echo esc_html__( 'Choose source:', 'gtfb-f' ); ?></span>
          <select id="GTFB_F_source_input" name="GTFB_F_source_input" class="form-control">
            <option value="pexels">Pexels</option>
            <option value="pixabay">Pixabay</option>
            <option value="unsplash">Unsplash</option>
            <option value="flaticon">Flaticon</option>
            <option value="iconfinder">Iconfinder</option>
          </select>
        </div>

      </div>
      <?php
    }

    function GTFB_F_result_panel() {
      ?>
      <div class="GTFB_F_result_panel" id="GTFB_F_result_panel"></div>
      <?php
    }

    function GTFB_F_more_result_panel() {
      ?>
      <div class="GTFB_F_more_result_panel" id="GTFB_F_more_result_panel"></div>
      <?php
    }



  }

  gtfbFinder::instance();
}
