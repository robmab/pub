<?php
/**
 * Acme Themes - Theme Info Admin Menu
 * @package Acme Themes
 * @subpackage Admin
 */
if ( ! class_exists( 'Fitness_Hub_Theme_Info' ) ) {
    class Fitness_Hub_Theme_Info{

        private $config;
        private $theme_name;
        private $theme_slug;
        private $theme_version;
        private $page_title;
        private $menu_title;
        private $tabs;

        /**
         * Constructor.
         */
        public function __construct( $config ) {
            $this->config = $config;
            $this->prepare_class();
            
            /*admin menu*/
            add_action( 'admin_menu', array( $this, 'at_admin_menu' ) );

            /* enqueue script and style for about page */
            add_action( 'admin_enqueue_scripts', array( $this, 'style_and_scripts' ) );

            /* ajax callback for dismissable required actions */
            add_action( 'wp_ajax_at_theme_info_update_recommended_action', array( $this, 'update_recommended_action_callback' ) );
        }

        /**
         * Prepare and setup class properties.
         */
        public function prepare_class() {
            $theme = wp_get_theme();
            if ( is_child_theme() ) {
                $this->theme_name = esc_attr( $theme->parent()->get( 'Name' ) );
            } else {
                $this->theme_name = esc_attr ( $theme->get( 'Name' ) );
            }
            $this->theme_slug    = esc_attr( $theme->get_template() );
            $this->theme_version = esc_attr( $theme->get( 'Version' ) );
            $this->page_title    = isset( $this->config['page_title'] ) ? $this->config['page_title'] : esc_html__('Info','fitness-hub'). $this->theme_name;
            $this->menu_title    = isset( $this->config['menu_title'] ) ? $this->config['menu_title'] : esc_html__('Info','fitness-hub') . $this->theme_name;
            $this->notification  = isset( $this->config['notification'] ) ? $this->config['notification'] : ( '<p>' . sprintf( esc_html__('Welcome! Thank you for choosing %1$s! To fully take advantage of the best our theme can offer please make sure you visit our %2$swelcome page%3$s.','fitness-hub'), $this->theme_name, '<a href="' . esc_url( admin_url( 'themes.php?page=' . $this->theme_slug . '-info' ) ) . '">', '</a>' ) . '</p><p><a href="' . esc_url( admin_url( 'themes.php?page=' . $this->theme_slug . '-info' ) ) . '" class="button text-decoration-none">' . sprintf( __('Get started with %s','fitness-hub'), $this->theme_name ) . '</a></p>' );
            $this->tabs          = isset( $this->config['tabs'] ) ? $this->config['tabs'] : array();
        }

        /**
         * Return the valid array of recommended actions.
         * @return array The valid array of recommended actions.
         */
        /**
         * Dismiss required actions
         */
        public function update_recommended_action_callback() {

            /*getting for provided array*/
            $recommended_actions = isset( $this->config['recommended_actions'] ) ? $this->config['recommended_actions'] : array();

            /*from js action*/
            $action_id = esc_attr( ( isset( $_GET['id'] ) ) ? $_GET['id'] : 0 );
            $todo = esc_attr( ( isset( $_GET['todo'] ) ) ? $_GET['todo'] : '' );

            /*getting saved actions*/
            $saved_actions = get_option( $this->theme_slug . '_recommended_actions' );

            echo esc_html( wp_unslash( $action_id ) ); /* this is needed and it's the id of the dismissable required action */

            if ( ! empty( $action_id ) ) {

                if( 'reset' == $todo ){
                    $saved_actions_new = array();
                    if ( ! empty( $recommended_actions ) ) {

                        foreach ( $recommended_actions as $recommended_action ) {
                            $saved_actions[ $recommended_action['id'] ] = true;
                        }
                        update_option( $this->theme_slug . '_recommended_actions', $saved_actions_new );
                    }
                }
                /* if the option exists, update the record for the specified id */
                elseif ( !empty( $saved_actions) and is_array( $saved_actions ) ) {

                    switch ( esc_html( $todo ) ) {
                        case 'add';
                            $saved_actions[ $action_id ] = true;
                            break;
                        case 'dismiss';
                            $saved_actions[ $action_id ] = false;
                            break;
                    }
                    update_option( $this->theme_slug . '_recommended_actions', $saved_actions );

                    /* create the new option,with false for the specified id */
                }
                else {
                    $saved_actions_new = array();
                    if ( ! empty( $recommended_actions ) ) {

                        foreach ( $recommended_actions as $recommended_action ) {
                            echo esc_attr( $recommended_action['id'] );
                            echo " ".esc_attr ( $todo );
                            if ( $recommended_action['id'] == $action_id ) {
                                switch ( esc_html( $todo ) ) {
                                    case 'add';
                                        $saved_actions_new[ $action_id ] = true;
                                        break;
                                    case 'dismiss';
                                        $saved_actions_new[ $action_id ] = false;
                                        break;
                                }
                            }
                        }
                    }
                    update_option( $this->theme_slug . '_recommended_actions', $saved_actions_new );
                }
            }
            exit;
        }

        private function get_recommended_actions() {
            $saved_actions = get_option( $this->theme_slug . '_recommended_actions' );
            if ( ! is_array( $saved_actions ) ) {
                $saved_actions = array();
            }
            $recommended_actions = isset( $this->config['recommended_actions'] ) ? $this->config['recommended_actions'] : array();
            $valid       = array();
            if( isset( $recommended_actions ) && is_array( $recommended_actions ) ){
                foreach ( $recommended_actions as $recommended_action ) {
                    if (
                        (
                            ! isset( $recommended_action['check'] ) ||
                            ( isset( $recommended_action['check'] ) && ( $recommended_action['check'] == false ) )
                        )
                        &&
                        ( ! isset( $saved_actions[ $recommended_action['id'] ] ) ||
                            ( isset( $saved_actions[ $recommended_action['id']] ) && ($saved_actions[ $recommended_action['id']] == true ) )
                        )
                    ) {
                        $valid[] = $recommended_action;
                    }
                }
            }
            return $valid;
        }

        private function count_recommended_actions() {
            $count = 0;
            $actions_count = $this->get_recommended_actions();
            if ( ! empty( $actions_count ) ) {
                $count = count( $actions_count );
            }
            return $count;
        }
        
        /**
         * Adding Theme Info Menu under Appearance.
         */
        function at_admin_menu() {

            if ( ! empty( $this->page_title ) && ! empty( $this->menu_title ) ) {
                $count = $this->count_recommended_actions();
                $menu_title = $count > 0 ? esc_html( $this->menu_title ) . '<span class="badge-action-count">' . esc_html( $count ) . '</span>' : esc_html( $this->menu_title );
                /* Example
                 * add_theme_page('My Plugin Theme', 'My Plugin', 'edit_theme_options', 'my-unique-identifier', 'my_plugin_function');
                 * */
                add_theme_page( $this->page_title, $menu_title, 'edit_theme_options', $this->theme_slug . '-info', array(
                    $this,
                    'at_theme_info_screen',
                ) );
            }
        }

        /**
         * Render the info content screen.
         */
        public function at_theme_info_screen() {

            if ( ! empty( $this->config['info_title'] ) ) {
                $welcome_title = $this->config['info_title'];
            }
            if ( ! empty( $this->config['info_content'] ) ) {
                $welcome_content = $this->config['info_content'];
            }
            if ( ! empty( $this->config['quick_links'] ) ) {
                $quick_links = $this->config['quick_links'];
            }

            if (
                ! empty( $welcome_title ) ||
                ! empty( $welcome_content ) ||
                ! empty( $quick_links ) ||
                ! empty( $this->tabs )
            ) {
                echo '<div class="wrap about-wrap info-wrap epsilon-wrap">';

                if ( ! empty( $welcome_title ) ) {
                    echo '<h1>';
                    echo esc_html( $welcome_title );
                    if ( ! empty( $this->theme_version ) ) {
                        echo esc_html( $this->theme_version ) . ' </sup>';
                    }
                    echo '</h1>';
                }
                if ( ! empty( $welcome_content ) ) {
                    echo '<div class="about-text">' . wp_kses_post( $welcome_content ) . '</div>';
                }

                $notice_nag = get_option( 'fitness_hub_admin_notice_welcome' );
                if ( ! $notice_nag ) {
                    echo '<div class="at-gsm-notice">
                        <small class="plugin-install-notice">'.esc_html__('Clicking the button below will install and activate the Advanced Import plugin.','fitness-hub').'</small>
                        <a class="at-gsm-btn button button-primary button-hero" href="#" data-name="" data-slug="" aria-label="'.esc_html__('Get started with Fitness Hub','fitness-hub').'">
                         '.esc_html__('Get started with Fitness Hub','fitness-hub').'                   
                         </a>
                    </div>';
                }
                echo '<a href="https://www.acmethemes.com/" target="_blank" class="wp-badge epsilon-info-logo"></a>';

                /*quick links*/
                if( !empty( $quick_links ) && is_array( $quick_links ) ){
                    echo '<p class="quick-links">';
                    foreach ( $quick_links as $quick_key => $quick_link ) {
                        $button = 'button-secondary';
                        if( 'pro_url' == $quick_key ){
                            $button = 'button-primary';
                        }
                        echo '<a href="'.esc_url( $quick_link['url'] ).'" class="button '.esc_attr( $button ).'" target="_blank">'.esc_html( $quick_link['text'] ).'</a>';
                    }
                    echo "</p>";
                }

                /* Display tabs */
                if ( ! empty( $this->tabs ) ) {
                    $current_tab = isset( $_GET['tab'] ) ? wp_unslash( $_GET['tab'] ) : 'getting_started';

                    echo '<h2 class="nav-tab-wrapper wp-clearfix">';
                    $count = $this->count_recommended_actions();
                    foreach ( $this->tabs as $tab_key => $tab_name ) {

                        echo '<a href="' . esc_url( admin_url( 'themes.php?page=' . $this->theme_slug . '-info' ) ) . '&tab=' . esc_attr( $tab_key ). '" class="nav-tab ' . ( $current_tab == $tab_key ? 'nav-tab-active' : '' ) . '" role="tab" data-toggle="tab">';
                        echo esc_html( $tab_name );
                        if ( $tab_key == 'recommended_actions' ) {
                            if ( $count > 0 ) {
                                echo '<span class="badge-action-count">' . esc_html( $count ) . '</span>';
                            }
                        }
                        echo '</a>';
                    }

                    echo '</h2>';

                    /* Display content for current tab, dynamic method according to key provided*/
                    if ( method_exists( $this, $current_tab ) ) {

                        echo "<div class='changelog point-releases'>";
                        $this->$current_tab();
                        echo "</div>";
                    }
                }
                echo '</div><!--/.wrap.about-wrap-->';
            }
        }

        /**
         * Getting started tab
         */
        public function getting_started() {
            if ( ! empty( $this->config['getting_started'] ) ) {
                $getting_started = $this->config['getting_started'];
                if ( ! empty( $getting_started ) ) {

                    /*defaults values for getting_started array */
                    $defaults = array(
                        'title'     => '',
                        'desc'       => '',
                        'recommended_actions'=> '',
                        'link_title'   => '',
                        'link_url'   => '',
                        'is_button' => false,
                        'is_new_tab' => false
                    );

                    echo '<div class="feature-section three-col">';

                    foreach ( $getting_started as $getting_started_item ) {

                        /*allowed 6 value in array */
                        $instance = wp_parse_args( (array) $getting_started_item, $defaults );
                        /*default values*/
                        $title = esc_html( $instance[ 'title' ] );
                        $desc = wp_kses_post( $instance[ 'desc' ] );
                        $link_title = esc_html( $instance[ 'link_title' ] );
                        $link_url = esc_url( $instance[ 'link_url' ] );
                        $is_button = $instance[ 'is_button' ];
                        $is_new_tab = $instance[ 'is_new_tab' ];


                        echo '<div class="col">';
                        if ( ! empty( $title ) ) {
                            echo '<h3>' . $title . '</h3>';
                        }
                        if ( ! empty( $desc ) ) {
                            echo '<p>' . $desc . '</p>';
                        }
                        if ( ! empty( $link_title ) && ! empty( $link_url ) ) {

                            echo '<p>';
                            $button_class = '';
                            if ( $is_button ) {
                                $button_class = 'button button-primary';
                            }

                            $count = $this->count_recommended_actions();

                            if ( $getting_started_item['recommended_actions'] && isset( $count ) ) {
                                if ( $count == 0 ) {
                                    echo '<span class="dashicons dashicons-yes"></span>';
                                } else {
                                    echo '<span class="dashicons dashicons-no-alt"></span>';
                                }
                            }

                            $button_new_tab = '_self';
                            if ( $is_new_tab ) {
                                $button_new_tab = '_blank';
                            }

                            echo '<a target="' . esc_attr( $button_new_tab ) . '" href="' . esc_url( $getting_started_item['link_url'] ) . '"class="' . esc_attr( $button_class ) . '">' . esc_attr( $getting_started_item['link_title'] ) . '</a>';
                            echo '</p>';
                        }
                        echo '</div><!-- .col -->';
                    }
                    echo '</div><!-- .feature-section three-col -->';
                }
            }
        }

        /**
         * Recommended Actions tab
         */
        public function check_plugin_status( $slug ) {

	        if( 'wpforms-lite' == $slug ){
		        $slug1 = 'wpforms';
	        }
	        else{
		        $slug1 = $slug;
	        }

            $path = WPMU_PLUGIN_DIR . '/' . $slug . '/' . $slug1 . '.php';
            if ( ! file_exists( $path ) ) {
                $path = WP_PLUGIN_DIR . '/' . $slug . '/' . $slug1 . '.php';
                if ( ! file_exists( $path ) ) {
                    $path = false;
                }
            }

            if ( file_exists( $path ) ) {
                include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

                $needs = is_plugin_active( $slug . '/' . $slug1 . '.php' ) ? 'deactivate' : 'activate';

                return array( 'status' => is_plugin_active( $slug . '/' . $slug1 . '.php' ), 'needs' => $needs );
            }

            return array( 'status' => false, 'needs' => 'install' );
        }

        public function create_action_link( $state, $slug ) {
	        if( 'wpforms-lite' == $slug ){
		        $slug1 = 'wpforms';
	        }
	        else{
		        $slug1 = $slug;
	        }

            switch ( $state ) {
                case 'install':
                    return wp_nonce_url(
                        add_query_arg(
                            array(
                                'action' => 'install-plugin',
                                'plugin' => $slug
                            ),
                            network_admin_url( 'update.php' )
                        ),
                        'install-plugin_' . $slug
                    );
                    break;

                case 'deactivate':
                    return add_query_arg(
                            array(
                                'action'        => 'deactivate',
                                'plugin'        => rawurlencode( $slug . '/' . $slug1 . '.php' ),
                                'plugin_status' => 'all',
                                'paged'         => '1',
                                '_wpnonce'      => wp_create_nonce( 'deactivate-plugin_' . $slug . '/' . $slug1 . '.php' )
                                ),
                            network_admin_url( 'plugins.php' )
                    );
                    break;

                case 'activate':
                    return add_query_arg(
                            array(
                                'action'        => 'activate',
                                'plugin'        => rawurlencode( $slug . '/' . $slug1 . '.php' ),
                                'plugin_status' => 'all',
                                'paged'         => '1',
                                '_wpnonce'      => wp_create_nonce( 'activate-plugin_' . $slug . '/' . $slug1 . '.php' )
                            ),
                            network_admin_url( 'plugins.php' )
                    );
                    break;
            }
        }

        public function recommended_actions() {

            $recommended_actions = isset( $this->config['recommended_actions'] ) ? $this->config['recommended_actions'] : array();
            $hooray = true;
            if ( ! empty( $recommended_actions ) ) {

                echo '<div class="feature-section action-recommended demo-import-boxed" id="plugin-filter">';

                if ( ! empty( $recommended_actions ) && is_array( $recommended_actions ) ) {

                    /*get saved recommend actions*/
                    $saved_recommended_actions = get_option( $this->theme_slug . '_recommended_actions' );

                    /*defaults values for getting_started array */
                    $defaults = array(
                        'title'         => '',
                        'desc'          => '',
                        'check'         => false,
                        'plugin_slug'   => '',
                        'id'            => ''
                    );
                    foreach ( $recommended_actions as $action_key => $action_value ) {
                        $instance = wp_parse_args( (array) $action_value, $defaults );

                        /*allowed 5 value in array */
                        $title = $instance[ 'title' ];
                        $desc = $instance[ 'desc' ];
                        $check = $instance[ 'check' ];
                        $plugin_slug = $instance[ 'plugin_slug' ];
                        $id = $instance[ 'id' ];

                        $hidden = false;

                        /*magic check for recommended actions*/
                        if (
                            isset( $saved_recommended_actions[ $id ] ) &&
                            $saved_recommended_actions[ $id ] == false ) {
                            $hidden = true;
                        }
                        if ( $hidden ) {
                            //continue;
                        }
                        $done = '';
                        if ( $check ) {
                           $done = 'done';
                        }

                        echo "<div class='at-theme-info-action-recommended-box {$done}'>";

                        if ( ! $hidden ) {
                            echo '<span data-action="dismiss" class="dashicons dashicons-visibility at-theme-info-recommended-action-button" id="' . esc_attr( $action_value['id'] ) . '"></span>';
                        } else {
                            echo '<span data-action="add" class="dashicons dashicons-hidden at-theme-info-recommended-action-button" id="' . esc_attr( $action_value['id'] ) .'"></span>';
                        }

                        if ( ! empty( $title) ) {
                            echo '<h3>' . wp_kses_post( $title ) . '</h3>';
                        }

                        if ( ! empty( $desc ) ) {
                            echo '<p>' . wp_kses_post( $desc ) . '</p>';
                        }

                        if ( ! empty( $plugin_slug ) ) {

                            $active = $this->check_plugin_status( $action_value['plugin_slug'] );
                            $url    = $this->create_action_link( $active['needs'], $action_value['plugin_slug'] );
                            $label  = '';
                            $class  = '';
                            switch ( $active['needs'] ) {

                                case 'install':
                                    $class = 'install-now button';
                                    $label = esc_html__( 'Install', 'fitness-hub' );
                                    break;

                                case 'activate':
                                    $class = 'activate-now button button-primary';
                                    $label = esc_html__( 'Activate', 'fitness-hub' );

                                    break;

                                case 'deactivate':
                                    $class = 'deactivate-now button';
                                    $label = esc_html__( 'Deactivate', 'fitness-hub' );

                                    break;
                            }

                            ?>
                            <p class="plugin-card-<?php echo esc_attr( $action_value['plugin_slug'] ) ?> action_button <?php echo ( $active['needs'] !== 'install' && $active['status'] ) ? 'active' : '' ?>">
                                <a data-slug="<?php echo esc_attr( $action_value['plugin_slug'] ) ?>"
                                   class="<?php echo esc_attr( $class ); ?>"
                                   href="<?php echo esc_url( $url ) ?>"> <?php echo esc_html( $label ) ?> </a>
                            </p>
                            <?php
                        }
                        echo '</div>';
                        $hooray = false;
                    }
                }
                if ( $hooray ){
                    echo '<span class="hooray">' . esc_html__( 'Hooray! There are no recommended actions for you right now.', 'fitness-hub' ) . '</span>';
                    echo '<a data-action="reset" id="reset" class="reset-all" href="#">'.esc_html__('Show All Recommended Actions','fitness-hub').'</a>';
                }
                echo '</div>';
            }
        }

        /**
         * Recommended plugins tab
         */
        /*
		 * Call plugin api
		 */
        public function call_plugin_api( $slug ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );

            if ( false === ( $call_api = get_transient( 'at_theme_info_plugin_information_transient_' . $slug ) ) ) {
                $call_api = plugins_api( 'plugin_information', array(
                    'slug'   => $slug,
                    'fields' => array(
                        'downloaded'        => false,
                        'rating'            => false,
                        'description'       => false,
                        'short_description' => true,
                        'donate_link'       => false,
                        'tags'              => false,
                        'sections'          => true,
                        'homepage'          => true,
                        'added'             => false,
                        'last_updated'      => false,
                        'compatibility'     => false,
                        'tested'            => false,
                        'requires'          => false,
                        'downloadlink'      => false,
                        'icons'             => true
                    )
                ) );
                set_transient( 'at_theme_info_plugin_information_transient_' . $slug, $call_api, 30 * MINUTE_IN_SECONDS );
            }

            return $call_api;
        }
        public function get_plugin_icon( $arr ) {

            if ( ! empty( $arr['svg'] ) ) {
                $plugin_icon_url = $arr['svg'];
            } elseif ( ! empty( $arr['2x'] ) ) {
                $plugin_icon_url = $arr['2x'];
            } elseif ( ! empty( $arr['1x'] ) ) {
                $plugin_icon_url = $arr['1x'];
            } else {
                $plugin_icon_url = get_template_directory_uri() . '/acmethemes/at-theme-info/images/placeholder_plugin.png';
            }

            return $plugin_icon_url;
        }
        public function recommended_plugins() {
            $recommended_plugins = $this->config['recommended_plugins'];

            if ( ! empty( $recommended_plugins ) ) {
                if ( ! empty( $recommended_plugins ) && is_array( $recommended_plugins ) ) {

                    echo '<div class="feature-section recommended-plugins three-col demo-import-boxed" id="plugin-filter">';

                    foreach ( $recommended_plugins as $recommended_plugins_item ) {

                        if ( ! empty( $recommended_plugins_item['slug'] ) ) {
                            $info   = $this->call_plugin_api( $recommended_plugins_item['slug'] );
                            if ( ! empty( $info->icons ) ) {
                                $icon = $this->get_plugin_icon( $info->icons );
                            }

                            $active = $this->check_plugin_status( $recommended_plugins_item['slug'] );

                            if ( ! empty( $active['needs'] ) ) {
                                $url = $this->create_action_link( $active['needs'], $recommended_plugins_item['slug'] );
                            }

                            echo '<div class="col plugin_box">';
                            if ( ! empty( $icon ) ) {
                                echo '<img src="'.esc_url( $icon ).'" alt="plugin box image">';
                            }
                            if ( ! empty(  $info->version ) ) {
                                echo '<span class="version">'. ( ! empty( $this->config['recommended_plugins']['version_label'] ) ? esc_html( $this->config['recommended_plugins']['version_label'] ) : '' ) . esc_html( $info->version ).'</span>';
                            }
                            if ( ! empty( $info->author ) ) {
                                echo '<span class="separator">'.esc_html__('|','fitness-hub') .'</span>' . wp_kses_post( $info->author );
                            }

                            if ( ! empty( $info->name ) && ! empty( $active ) ) {
                                echo '<div class="action_bar ' . ( ( $active['needs'] !== 'install' && $active['status'] ) ? 'active' : '' ) . '">';
                                echo '<span class="plugin_name">' . ( ( $active['needs'] !== 'install' && $active['status'] ) ? esc_html__('Active:','fitness-hub') : '' ) . esc_html( $info->name ) . '</span>';
                                echo '</div>';

                                $label = '';

                                switch ( $active['needs'] ) {

                                    case 'install':
                                        $class = 'install-now button';
                                        $label = esc_html__( 'Install', 'fitness-hub' );
                                        break;

                                    case 'activate':
                                        $class = 'activate-now button button-primary';
                                        $label = esc_html__( 'Activate', 'fitness-hub' );

                                        break;

                                    case 'deactivate':
                                        $class = 'deactivate-now button';
                                        $label = esc_html__( 'Deactivate', 'fitness-hub' );

                                        break;
                                }

                                echo '<span class="plugin-card-' . esc_attr( $recommended_plugins_item['slug'] ) . ' action_button ' . ( ( $active['needs'] !== 'install' && $active['status'] ) ? 'active' : '' ) . '">';
                                echo '<a data-slug="' . esc_attr( $recommended_plugins_item['slug'] ) . '" class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
                                echo '</span>';
                            }
                            echo '</div><!-- .col.plugin_box -->';
                        }
                    }
                    echo '</div><!-- .recommended-plugins -->';
                }
            }
        }

        /**
         * Child themes
         */
        public function child_themes() {
            echo '<div id="child-themes" class="at-theme-info-tab-pane">';
            $child_themes = isset( $this->config['child_themes'] ) ? $this->config['child_themes'] : array();
            if ( ! empty( $child_themes ) ) {

                /*defaults values for child theme array */
                $defaults = array(
                    'title'        => '',
                    'screenshot'   => '',
                    'download_link'=> '',
                    'preview_link' => ''
                );
                if ( ! empty( $child_themes ) && is_array( $child_themes ) ) {
                    echo '<div class="at-about-row">';
                    $i = 0;
                    foreach ( $child_themes as $child_theme ){
                        $instance = wp_parse_args( (array) $child_theme, $defaults );

                        /*allowed 5 value in array */
                        $title = $instance[ 'title' ];
                        $screenshot = $instance[ 'screenshot'];
                        $download_link = $instance[ 'download_link'];
                        $preview_link = $instance[ 'preview_link'];

                        if( !empty( $screenshot) ){
                            echo '<div class="at-about-child-theme">';
                            echo '<div class="at-theme-info-child-theme-image">';

                            echo '<img src="' . esc_url( $screenshot ) . '" alt="' . ( ! empty( $title ) ? esc_attr( $title ) : '' ) . '" />';

                            if ( ! empty( $title ) ) {
                                echo '<div class="at-theme-info-child-theme-details">';
                                echo '<div class="theme-details">';
                                echo '<span class="theme-name">' . esc_html( $title  ). '</span>';
                                if ( ! empty( $download_link ) ) {
                                    echo '<a href="' . esc_url( $download_link ) . '" class="button button-primary install right">' . esc_html__( 'Download','fitness-hub' ) . '</a>';
                                }
                                if ( ! empty( $preview_link ) ) {
                                    echo '<a class="button button-secondary preview right" target="_blank" href="' . esc_url( $preview_link ) . '">' . esc_html__( 'Live Preview','fitness-hub' ). '</a>';
                                }
                                echo '</div>';
                                echo '</div>';
                            }

                            echo "</div>";
                            echo "</div>";
                            $i++;
                        }
                        if( 0 == $i % 3 ){
                            echo "</div><div class='at-about-row'>";/*.at-about-row end-start*/
                        }
                    }

                    echo '</div>';/*.at-about-row end*/
                }// End if().
            }// End if().
            echo '</div>';
        }

        /**
         * Support tab
         */
        public function support() {
            echo '<div class="feature-section three-col">';

            if ( ! empty( $this->config['support_content'] ) ) {

                $supports = $this->config['support_content'];

                if ( ! empty( $supports ) ) {

                    /*defaults values for child theme array */
                    $defaults = array(
                        'title' => '',
                        'icon' => '',
                        'desc' => '',
                        'button_label' => '',
                        'button_link' => '',
                        'is_button' => true,
                        'is_new_tab' => true
                    );

                    foreach ( $supports as $support ) {
                        $instance = wp_parse_args( (array) $support, $defaults );

                        /*allowed 7 value in array */
	                    /*default values*/
	                    $title = esc_html( $instance[ 'title' ] );
                        $icon = esc_attr( $instance[ 'icon']);
                        $desc = wp_kses_post( $instance[ 'desc' ] );
                        $button_label = esc_html( $instance[ 'button_label'] );
                        $button_link = esc_url( $instance[ 'button_link'] );
                        $is_button = $instance[ 'is_button'];
                        $is_new_tab = $instance[ 'is_new_tab'];
                        
                        echo '<div class="col">';

                        if ( ! empty( $title ) ) {
                            echo '<h3>';
                            if ( ! empty( $icon ) ) {
                                echo '<i class="' . $icon . '"></i>';
                            }
                            echo $title;
                            echo '</h3>';
                        }

                        if ( ! empty( $desc ) ) {
                            echo '<p><i>' . $desc . '</i></p>';
                        }

                        if ( ! empty( $button_link ) && ! empty( $button_label ) ) {

                            echo '<p>';
                            $button_class = '';
                            if ( $is_button ) {
                                $button_class = 'button button-primary';
                            }

                            $button_new_tab = '_self';
                            if ( isset( $is_new_tab ) ) {
                                if ( $is_new_tab ) {
                                    $button_new_tab = '_blank';
                                }
                            }
                            echo '<a target="' . $button_new_tab . '" href="' . $button_link . '" class="' . $button_class . '">' . $button_label . '</a>';
                            echo '</p>';
                        }
                        echo '</div>';
                    }
                }
            }
            echo '</div>';
        }

        /**
         * Changelog tab
         */
        private function parse_changelog() {
            WP_Filesystem();
            global $wp_filesystem;
            $changelog = $wp_filesystem->get_contents( get_template_directory() . '/changelog.txt' );
            if ( is_wp_error( $changelog ) ) {
                $changelog = '';
            }
            return $changelog;
        }

        public function changelog() {
            $changelog = $this->parse_changelog();
            if ( ! empty( $changelog ) ) {
                echo '<div class="featured-section changelog">';
                echo "<pre class='changelog'>";
                echo wp_kses_post( $changelog );
                echo "</pre>";
                echo '</div><!-- .featured-section.changelog -->';
            }
        }

        /**
         * Free vs PRO tab
         */
        public function free_pro() {
            $free_pro = isset( $this->config['free_pro'] ) ? $this->config['free_pro'] : array();
            if ( ! empty( $free_pro ) ) {
                /*defaults values for child theme array */
                $defaults = array(
                    'title'=> '',
                    'desc' => '',
                    'free' => '',
                    'pro'  => '',
                );

                if ( ! empty( $free_pro ) && is_array( $free_pro ) ) {
                    echo '<div class="feature-section">';
                    echo '<div id="free_pro" class="at-theme-info-tab-pane at-theme-info-fre-pro">';
                    echo '<table class="free-pro-table">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th></th>';
                    echo '<th>' . esc_html__( 'Fitness Hub','fitness-hub' ) . '</th>';
                    echo '<th>' . esc_html__( 'Fitness Hub Pro','fitness-hub' ) . '</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                    foreach ( $free_pro as $feature ) {

                        $instance = wp_parse_args( (array) $feature, $defaults );

                        /*allowed 7 value in array */
                        $title = $instance[ 'title' ];
                        $desc = $instance[ 'desc'];
                        $free = $instance[ 'free'];
                        $pro = $instance[ 'pro'];

                        echo '<tr>';
                        if ( ! empty( $title ) || ! empty( $desc ) ) {
                            echo '<td>';
                            if ( ! empty( $title ) ) {
                                echo '<h3>' . wp_kses_post( $title ) . '</h3>';
                            }
                            if ( ! empty( $desc ) ) {
                                echo '<p>' . wp_kses_post( $desc ) . '</p>';
                            }
                            echo '</td>';
                        }

                        if ( ! empty( $free )) {
                            if( 'yes' === $free ){
                                echo '<td class="only-lite"><span class="dashicons-before dashicons-yes"></span></td>';
                            }
                            elseif ( 'no' === $free ){
                                echo '<td class="only-pro"><span class="dashicons-before dashicons-no-alt"></span></td>';
                            }
                            else{
                                echo '<td class="only-lite">'.esc_html($free ).'</td>';
                            }
                        }
                        if ( ! empty( $pro )) {
                            if( 'yes' === $pro ){
                                echo '<td class="only-lite"><span class="dashicons-before dashicons-yes"></span></td>';
                            }
                            elseif ( 'no' === $pro ){
                                echo '<td class="only-pro"><span class="dashicons-before dashicons-no-alt"></span></td>';
                            }
                            else{
                                echo '<td class="only-lite">'.esc_html($pro ).'</td>';
                            }
                        }
                        echo '</tr>';
                    }

                    echo '<tr class="at-theme-info-text-center">';
                    echo '<td></td>';
                    echo '<td colspan="2"><a href="https://www.acmethemes.com/themes/fitness-hub-pro/" target="_blank" class="button button-primary button-hero"> Fitness Hub Pro</a></td>';
                    echo '</tr>';

                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';
                    echo '</div>';
                }
            }
        }

        /**
         * Support tab
         */
        public function faq() {
            echo '<div class="feature-section three-col faq">';

            if ( ! empty( $this->config['faq'] ) ) {

                $supports = $this->config['faq'];

                if ( ! empty( $supports ) ) {

                    /*defaults values for child theme array */
                    $defaults = array(
                        'title' => '',
                        'icon' => '',
                        'desc' => '',
                        'button_label' => '',
                        'button_link' => '',
                        'is_button' => true,
                        'is_new_tab' => true
                    );

                    foreach ( $supports as $support ) {
                        $instance = wp_parse_args( (array) $support, $defaults );

                        /*allowed 7 value in array */
	                    $title = esc_html( $instance[ 'title' ] );
	                    $icon = esc_attr( $instance[ 'icon']);
	                    $desc = wp_kses_post( $instance[ 'desc' ] );
	                    $button_label = esc_html( $instance[ 'button_label'] );
	                    $button_link = esc_url( $instance[ 'button_link'] );
	                    $is_button = $instance[ 'is_button'];
	                    $is_new_tab = $instance[ 'is_new_tab'];

                        echo '<div class="col-full">';

                        if ( ! empty( $title ) ) {
                            echo '<h3 class="faq-title">';
                            if ( ! empty( $icon ) ) {
                                echo '<i class="' . $icon . '"></i>';
                            }
                            echo $title;
                            echo '</h3>';
                        }
                        echo "<div class='faq-content'>";

                        if ( ! empty( $desc ) ) {
                            echo '<p><i>' . $desc . '</i></p>';
                        }
                        if ( ! empty( $button_link ) && ! empty( $button_label ) ) {

                            echo '<p>';
                            $button_class = '';
                            if ( $is_button ) {
                                $button_class = 'button button-primary';
                            }

                            $button_new_tab = '_self';
                            if ( isset( $is_new_tab ) ) {
                                if ( $is_new_tab ) {
                                    $button_new_tab = '_blank';
                                }
                            }
                            echo '<a target="' . $button_new_tab . '" href="' . $button_link . '" class="' . $button_class . '">' . $button_label . '</a>';
                            echo '</p>';
                        }
                        echo "</div>";

                        echo '</div>';
                    }
                }
            }
            echo '</div>';
        }

        /**
         * Load css and scripts for the about page
         */
        public function style_and_scripts( $hook_suffix ) {

            // this is needed on all admin pages, not just the about page, for the badge action count in the wordpress main sidebar
            wp_enqueue_style( 'fitness-hub-theme-info-css', get_template_directory_uri() . '/acmethemes/at-theme-info/css/at-theme-info.css' );

            if ( 'appearance_page_' . $this->theme_slug . '-info' == $hook_suffix ) {

                wp_enqueue_script( 'fitness-hub-theme-info-js', get_template_directory_uri() . '/acmethemes/at-theme-info/js/at-theme-info.js', array( 'jquery' ) );

                wp_enqueue_style( 'plugin-install' );
                wp_enqueue_script( 'plugin-install' );
                wp_enqueue_script( 'updates' );

                $count = $this->count_recommended_actions();
                wp_localize_script( 'fitness-hub-theme-info-js', 'at_theme_info_object', array(
                    'nr_actions_recommended'      => $count,
                    'ajaxurl'                  => admin_url( 'admin-ajax.php' ),
                    'template_directory'       => get_template_directory_uri()
                ) );
            }
        }
    }
}

$config = array(
    // Page title.
    'page_title'               => esc_html__( 'Fitness Hub Info', 'fitness-hub' ),

    // Menu name under Appearance.
    'menu_title'               => esc_html__( 'Fitness Hub Info', 'fitness-hub' ),

    // Main welcome title
    'info_title'         => sprintf( esc_html__( 'Welcome to %s - ', 'fitness-hub' ), 'Fitness Hub' ),

    // Main welcome content
    'info_content'       => sprintf( esc_html__( '%s is now installed and ready to use. We hope the following information will help you. If you want to ask any query or just want to say hello, you can always contact us. We hope you enjoy it! ', 'fitness-hub' ), '<b>Fitness Hub</b>' ),

    /**
     * Quick links
     */
    'quick_links'                    => array(
        'theme_url'  => array(
                'text' => __('Theme Details','fitness-hub'),
                'url' => 'https://www.acmethemes.com/themes/fitness-hub/'
        ),
        'demo_url'  => array(
            'text' => __('View Demo','fitness-hub'),
            'url' => 'http://www.demo.acmethemes.com/fitness-hub/'
        ),
        'pro_url'  => array(
            'text' => __('View Pro Version','fitness-hub'),
            'url' => 'https://www.acmethemes.com/themes/fitness-hub-pro/'
        ),
        'rate_url'  => array(
            'text' => __('Rate This Theme','fitness-hub'),
            'url' => 'https://wordpress.org/support/theme/fitness-hub/reviews/?filter=5'
        ),
    ),

    /**
     * Tabs array.
     *'tabs'                    => array(
    'getting_started'  => __( 'Getting Started', 'fitness-hub' ),
    'recommended_actions' => __( 'Recommended Actions', 'fitness-hub' ),
    'recommended_plugins' => __( 'Useful Plugins','fitness-hub' ),
    'support'       => __( 'Support', 'fitness-hub' ),
    'changelog'        => __( 'Changelog', 'fitness-hub' ),
    'faq'        => __( 'FAQ', 'fitness-hub' ),
    'free_pro'         => __( 'Free VS PRO', 'fitness-hub' ),
    ),
     * The key needs to be ONLY consisted from letters and underscores. If we want to define outside the class a function to render the tab,
     * the will be the name of the function which will be used to render the tab content.
     */
    'tabs' => array(
        'getting_started'  => esc_html__( 'Getting Started', 'fitness-hub' ),
        'recommended_actions' => esc_html__( 'Recommended Actions', 'fitness-hub' ),
        'recommended_plugins' => esc_html__( 'Useful Plugins','fitness-hub' ),
        'support'       => esc_html__( 'Support', 'fitness-hub' ),
        'changelog'        => esc_html__( 'Changelog', 'fitness-hub' ),
        'faq'        => esc_html__( 'FAQ', 'fitness-hub' ),
        'free_pro'         => esc_html__( 'Free VS PRO', 'fitness-hub' ),
    ),

    /*Getting started tab*/
    'getting_started' => array(
        'first' => array(
            'title' => esc_html__( 'Step 1 : Read full documentation','fitness-hub' ),
            'desc' => esc_html__( 'Please check our full documentation for detailed information on how to Setup and Use Fitness Hub.','fitness-hub' ),
            'link_title' => esc_html__( 'Documentation','fitness-hub' ),
            'link_url' => 'http://www.doc.acmethemes.com/fitness-hub/',
            'is_button' => false,
            'recommended_actions' => false,
            'is_new_tab' => true
        ),
        'second' => array(
            'title' => esc_html__( 'Step 2 : Go to Customizer','fitness-hub' ),
            'desc' => esc_html__( 'All Setting, Theme Options, Widgets and Menus are available via Customize screen. Have a quick look or start customization!','fitness-hub' ),
            'link_title' => esc_html__( 'Go to Customizer','fitness-hub' ),
            'link_url' => esc_url( admin_url( 'customize.php' ) ),
            'is_button' => true,
            'recommended_actions' => false,
            'is_new_tab' => true
        ),
        'third' => array(
            'title' => esc_html__( 'Step 3: Setting Static Front Page','fitness-hub' ),
            'desc' => esc_html__( 'Select A static page than Front page and Posts page to enable Slider and Home Main Content Area','fitness-hub' ),
            'link_title' => esc_html__( 'Static Front Page','fitness-hub' ),
            'link_url' => esc_url( admin_url( 'customize.php' ) ).'?autofocus[section]=static_front_page',
            'is_button' => true,
            'recommended_actions' => false,
            'is_new_tab' => true
        ),
        'forth' => array(
            'title' => esc_html__( 'Step 4 : Setting Featured Section ','fitness-hub' ),
            'desc' => esc_html__( 'Advanced Feature Section is available in this theme. You can add Feature Main Slider and Feature Right Vertical Slider, also you can add widgets before feature. Please do the Step 3 before this.','fitness-hub' ),
            'link_title' => esc_html__( 'Featured Section','fitness-hub' ),
            'link_url' => esc_url( admin_url( 'customize.php' ) ).'?autofocus[panel]=fitness-hub-feature-panel',
            'is_button' => true,
            'recommended_actions' => false,
            'is_new_tab' => true
        ),
        'fifth' => array(
            'title' => esc_html__( 'Step 5 : Setting Home Main Content Area ','fitness-hub' ),
            'desc' => esc_html__( 'You can Add, Edit, Remove or Drag, Drop, Reorder widgets on Home Main Content Area. Please do the Step 3 before this.','fitness-hub' ),
            'link_title' => esc_html__( 'Home Main Content Area','fitness-hub' ),
            'link_url' => esc_url( admin_url( 'customize.php' ) ).'?autofocus[section]=sidebar-widgets-fitness-hub-home',
            'is_button' => true,
            'recommended_actions' => false,
            'is_new_tab' => true
        ),
        'sixth' => array (
	        'title' => esc_html__( 'Do not forget to install WooCommerce','fitness-hub' ),
	        'desc' => esc_html__( 'The theme use WooCommerce Product as Tours and Product Categories as Destinations or Packages, so do not forget to install WooCommerce. You can even create booking site with the help of WooCommerce and its addons','fitness-hub' ),
	        'link_title' => esc_html__( 'Install Recommended Plugins','fitness-hub' ),
	        'link_url' => '?page=fitness-hub-info&tab=recommended_plugins',
	        'is_button' => true,
	        'recommended_actions' => false,
	        'is_new_tab' => false
        ),
    ),

    // recommended actions array.
    'recommended_actions'        => array(
        'demo-content' => array(
            'title' => esc_html__( 'Install Demo Setup','fitness-hub' ),
            'desc' => sprintf( esc_html__( 'For the demo content installation, install the Acme Demo Setup plugin and other Recommended plugins, download demo content from %1$s here %2$s. Then go to "Acme Demo Setup" and import demo. ', 'fitness-hub' ), '<a target="_blank" href="http://www.doc.acmethemes.com/fitness-hub/#DummyData" >','</a>' ),
            'id' => 'acme-demo-setup',
            'check' =>  ( ( function_exists('acme_demo_setup_load_textdomain') )? true : false ),
            'plugin_slug'   => 'acme-demo-setup',
        ),
        'front-page' => array(
            'title' => esc_html__( 'Setting Static Front Page','fitness-hub' ),
            'desc' => sprintf( esc_html__( 'Select A static page than Front page and Posts page to enable Slider and Home Main Content Area %1$s Static Front Page %2$s ', 'fitness-hub' ), '<p><a target="_blank" href="'.esc_url( admin_url( 'customize.php' ) ).'?autofocus[section]=static_front_page'.'" class="button button-primary">','</a></p>' ),
            'id' => 'front-page',
            'check' =>  ( ( 'page' == get_option('show_on_front') )? true : false )
        )
    ),

    // Plugins array.
    'recommended_plugins'        => array(
        'gutentor' => array(
            'slug' => 'gutentor'
        ),
	    'siteorigin-panels' => array(
		    'slug' => 'siteorigin-panels'
	    ),
    ),

    /*FAQ*/
    'faq'      => array(
        'first' => array (
            'title' => esc_html__( 'How to Setup Site like Demo','fitness-hub' ),
            'icon' => 'dashicons dashicons-sos',
            'desc' => sprintf( esc_html__( 'For the demo content installation, install the Acme Demo Setup plugin and other Recommended plugins, download demo content from %1$s here %2$s. Then go to "Acme Demo Setup" and import demo. ', 'fitness-hub' ), '<a target="_blank" href="http://www.doc.acmethemes.com/fitness-hub/#DummyData" >','</a>' ),
            'button_label' => esc_html__( 'Download Demo Content','fitness-hub' ),
            'button_link' => esc_url( 'http://www.doc.acmethemes.com/fitness-hub/#DummyData' ),
            'is_button' => true,
            'is_new_tab' => true
        ),
        'second' => array (
            'title' => esc_html__( 'Feature Section is now Showing','fitness-hub' ),
            'icon' => 'dashicons dashicons-sos',
            'desc' => esc_html__( 'Please go to Setting => Reading, then Select A static page than Homepage and Posts page to enable Slider and Home Main Content Area','fitness-hub' ),
            'button_label' => esc_html__( 'Set Static Page','fitness-hub' ),
            'button_link' => esc_url( admin_url( 'options-reading.php' ) ),
            'is_button' => true,
            'is_new_tab' => true
        ),

        'third' => array (
            'title' => esc_html__( 'Hide Fitness Hub by Acme Themes from footer ? ','fitness-hub' ),
            'icon' => 'dashicons dashicons-sos',
            'desc' => esc_html__( 'Please visit the blog post to know more about it.','fitness-hub' ),
            'button_label' => esc_html__( 'Hide credit text on Footer','fitness-hub' ),
            'button_link' => esc_url( 'https://www.acmethemes.com/blog/2017/01/remove-powered-by-text-on-footer/' ),
            'is_button' => true,
            'is_new_tab' => true
        ),

        'forth' => array (
            'title' => esc_html__( 'Recent Updates of your Site ? ','fitness-hub' ),
            'icon' => 'dashicons dashicons-sos',
            'desc' => esc_html__( 'Where i can get the recent updates of theme related updates ? ','fitness-hub' ),
            'button_label' => esc_html__( 'Visit our site blog page','fitness-hub' ),
            'button_link' => esc_url( 'https://www.acmethemes.com/blog/' ),
            'is_button' => true,
            'is_new_tab' => true
        ),
    ),
    // Support content tab.
    'support_content'      => array(
        'first' => array (
            'title' => esc_html__( 'Contact Support','fitness-hub' ),
            'icon' => 'dashicons dashicons-sos',
            'desc' => esc_html__( 'Got theme support question or found bug? Best place to ask your query is our dedicated Support forum.','fitness-hub' ),
            'button_label' => esc_html__( 'Contact Support','fitness-hub' ),
            'button_link' => esc_url( 'https://www.acmethemes.com/supports/forum/fitness-hub/' ),
            'is_button' => true,
            'is_new_tab' => true
        ),
        'second' => array(
            'title' => esc_html__( 'Documentation','fitness-hub' ),
            'icon' => 'dashicons dashicons-book-alt',
            'desc' => esc_html__( 'Please check our full documentation for detailed information on how to Setup and Use Fitness Hub.','fitness-hub' ),
            'button_label' => esc_html__( 'Read full documentation','fitness-hub' ),
            'button_link' => 'http://www.doc.acmethemes.com/fitness-hub/',
            'is_button' => false,
            'is_new_tab' => true
        ),
        'third' => array(
            'title' => esc_html__( 'Need more features?','fitness-hub' ),
            'icon' => 'dashicons dashicons-screenoptions',
            'desc' => esc_html__( 'Upgrade to PRO version for more exciting features and Priority Support.','fitness-hub' ),
            'button_label' => esc_html__( 'View Pro Version','fitness-hub' ),
            'button_link' => 'https://www.acmethemes.com/themes/fitness-hub-pro/',
            'is_button' => true,
            'is_new_tab' => true
        ),
        'fourth' => array(
            'title' => esc_html__( 'Got sales related question?','fitness-hub' ),
            'icon' => 'dashicons dashicons-cart',
            'desc' => esc_html__( "Have any query before purchase, you are more than welcome to ask.",'fitness-hub' ),
            'button_label' => esc_html__( 'Pre-sale Question?','fitness-hub' ),
            'button_link' => 'https://www.acmethemes.com/pre-sale-question/',
            'is_button' => true,
            'is_new_tab' => true
        ),
        'fifth' => array(
            'title' => esc_html__( 'Customization Request','fitness-hub' ),
            'icon' => 'dashicons dashicons-hammer',
            'desc' => esc_html__( 'Needed any customization for the theme, you can request from here.','fitness-hub' ),
            'button_label' => esc_html__( 'Customization Request','fitness-hub' ),
            'button_link' => 'https://www.acmethemes.com/customization-request/',
            'is_button' => false,
            'is_new_tab' => true
        )
    ),

    // Free vs pro array.
    'free_pro' => array(
	    array(
		    'title'=> esc_html__( 'Custom Widgets', 'fitness-hub' ),
		    'desc' => esc_html__( 'All added custom widgets are ready for page builder plugin', 'fitness-hub' ),
		    'free' => esc_html__('10+ Available','fitness-hub'),
		    'pro'  => esc_html__('17+ Available','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Widget Options', 'fitness-hub' ),
		    'desc' => esc_html__( 'Every widgets have multiple options to customize section', 'fitness-hub' ),
		    'free' => esc_html__('Limited','fitness-hub'),
		    'pro'  => esc_html__('Multiple Options','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Google Fonts', 'fitness-hub' ),
		    'desc' => esc_html__( 'Google fonts options for changing the overall site fonts ', 'fitness-hub' ),
		    'free' => 'yes',
		    'pro'  => esc_html__('Unlimited +','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Color Options', 'fitness-hub' ),
		    'desc' => esc_html__( 'Color options for changing overall site colors, Contact us', 'fitness-hub' ),
		    'free' => esc_html__('Limited','fitness-hub'),
		    'pro'  => esc_html__('Almost All parts','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Header Options', 'fitness-hub' ),
		    'desc' => esc_html__( 'Customize the header section as your need.', 'fitness-hub' ),
		    'free' => esc_html__('Limited','fitness-hub'),
		    'pro'  => esc_html__('Multiple Options','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Menu Options', 'fitness-hub' ),
		    'desc' => esc_html__( 'Lots of menu options are available to manage the menu section. Appearance > Menus', 'fitness-hub' ),
		    'free' => esc_html__('Limited','fitness-hub'),
		    'pro'  => esc_html__('Multiple Options','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Featured Selection', 'fitness-hub' ),
		    'desc' => esc_html__( 'This is slider section. Check every options there','fitness-hub' ),
		    'free' => esc_html__('Unlimited Page','fitness-hub'),
		    'pro'  => esc_html__('Unlimited Page, Custom or Third Party','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Featured Section Options', 'fitness-hub' ),
		    'desc' => esc_html__( 'Options to manage the sliders', 'fitness-hub' ),
		    'free' => esc_html__('Limited','fitness-hub'),
		    'pro'  => esc_html__('Multiple Options','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Sticky Sidebar', 'fitness-hub' ),
		    'desc' => esc_html__( 'Enable/Disable sticky sidebars', 'fitness-hub' ),
		    'free' => 'no',
		    'pro'  => 'yes',
	    ),
	    array(
		    'title'=> esc_html__( 'Blog/Archive Options', 'fitness-hub' ),
		    'desc' => esc_html__( 'Various Options are available for the blog and archive page management. Check on Appearance > Customize > Layout/Design Options.', 'fitness-hub' ),
		    'free' => esc_html__('Limited','fitness-hub'),
		    'pro'  => esc_html__('Manage Every Entity','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Footer Options', 'fitness-hub' ),
		    'desc' => esc_html__( 'Manage the every entity related to footer section.', 'fitness-hub' ),
		    'free' => esc_html__('Limited','fitness-hub'),
		    'pro'  => esc_html__('Multiple Options','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Social Options', 'fitness-hub' ),
		    'desc' => esc_html__( 'Options to manage the social icons like facebook, instagram, linkedin etc.', 'fitness-hub' ),
		    'free' => 'yes',
		    'pro'  => 'yes',
	    ),
	    array(
		    'title'=> esc_html__( 'Theme Credit Link', 'fitness-hub' ),
		    'desc' => esc_html__( 'Options to disable to Powered by text on footer.', 'fitness-hub' ),
		    'free' => esc_html__('Enable/Disable','fitness-hub'),
		    'pro'  => esc_html__('Enable/Disable/Custom','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Own Credit Link', 'fitness-hub' ),
		    'desc' => esc_html__( 'Write your own credit text.', 'fitness-hub' ),
		    'free' => 'no',
		    'pro'  => esc_html__('Enable/Disable','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Social Icons', 'fitness-hub' ),
		    'desc' => esc_html__( 'Enter the URL for social icons. ', 'fitness-hub' ),
		    'free' => esc_html__('Unlimited','fitness-hub'),
		    'pro'  => esc_html__('Unlimited with multiple Options','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Intro Loader Options', 'fitness-hub' ),
		    'desc' => esc_html__( 'It will load before the site loaded completely.', 'fitness-hub' ),
		    'free' => 'no',
		    'pro'  => esc_html__('Multiple Options','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Related Post Options', 'fitness-hub' ),
		    'desc' => esc_html__( 'Related post will appear on single post of each blog article', 'fitness-hub' ),
		    'free' => 'no',
		    'pro'  => esc_html__('Multiple Options','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Author Info Options', 'fitness-hub' ),
		    'desc' => esc_html__( 'This will appear on single post.', 'fitness-hub' ),
		    'free' => 'no',
		    'pro'  => esc_html__('Multiple Options','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Feature Image Options', 'fitness-hub' ),
		    'desc' => esc_html__( 'Featured image options for the single page and post.', 'fitness-hub' ),
		    'free' => 'no',
		    'pro'  => esc_html__('Manage As Your Need','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Navigation Options', 'fitness-hub' ),
		    'desc' => esc_html__( 'Post Navigation means, previous post and next post. Check on single post.', 'fitness-hub' ),
		    'free' => esc_html__('Default','fitness-hub'),
		    'pro'  => esc_html__('Advanced','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Post Comment Options', 'fitness-hub' ),
		    'desc' => esc_html__( 'Manage the comment section on single post.', 'fitness-hub' ),
		    'free' => 'no',
		    'pro'  => esc_html__('Multiple Options','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Show/Hide Comment', 'fitness-hub' ),
		    'desc' => esc_html__( 'Options to show hide comments on page and post.', 'fitness-hub' ),
		    'free' => 'no',
		    'pro'  => 'yes',
	    ),
	    array(
		    'title'=> esc_html__( 'Breadcrumb', 'fitness-hub' ),
		    'desc' => esc_html__( 'Advanced options for manage the Breadcrumb.', 'fitness-hub' ),
		    'free' => 'yes',
		    'pro'  => esc_html__('Advanced','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Video Post On Featured', 'fitness-hub' ),
		    'desc' => esc_html__( 'Create your video post easily from Youtube and Vimeo video.', 'fitness-hub' ),
		    'free' => 'no',
		    'pro'  => esc_html__('Add Own Video On Featured','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Single Post Options', 'fitness-hub' ),
		    'desc' => esc_html__( 'Manage every entity of single post.', 'fitness-hub' ),
		    'free' => esc_html__('Limited','fitness-hub'),
		    'pro'  => esc_html__('Multiple Options','fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Featured Image Size', 'fitness-hub' ),
		    'desc' => esc_html__( 'Select the size of the featured image on the single post.', 'fitness-hub' ),
		    'free' => 'yes',
		    'pro'  => 'yes',
	    ),
	    array(
		    'title'=> esc_html__( 'Sidebars', 'fitness-hub' ),
		    'desc' => esc_html__( 'Global and Individual sidebars are available. You can easily orveride it from single page/post.', 'fitness-hub' ),
		    'free' => esc_html__('Global Options', 'fitness-hub'),
		    'pro'  => esc_html__('Advanced Global And Individuals', 'fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Menu and Logo Location', 'fitness-hub' ),
		    'desc' => esc_html__( 'Position of menu and logo section on header.', 'fitness-hub' ),
		    'free' => esc_html__('Default', 'fitness-hub'),
		    'pro'  => esc_html__('3 Position Left, Right and Middle', 'fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Popup Widget on Header', 'fitness-hub' ),
		    'desc' => esc_html__( 'Options to popup widget via Popup Widget Area on Header Top Section.', 'fitness-hub' ),
		    'free' => esc_html__('Add your required Widget', 'fitness-hub'),
		    'pro'  => esc_html__('Add your required Widget', 'fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'Multiple Header Type', 'fitness-hub' ),
		    'desc' => esc_html__( 'Different Types of Header.', 'fitness-hub' ),
		    'free' => esc_html__('Default', 'fitness-hub'),
		    'pro'  => esc_html__('3 Options', 'fitness-hub'),
	    ),
	    array(
		    'title'=> esc_html__( 'WooCommerce', 'fitness-hub' ),
		    'desc' => esc_html__( 'Create WooCommerce shop easily with the help of WooCommerce plugin.', 'fitness-hub' ),
		    'free' => 'yes',
		    'pro'  => 'yes',
	    ),
	    array(
		    'title'=> esc_html__( 'Translation', 'fitness-hub' ),
		    'desc' => esc_html__( 'Both theme are translation ready.', 'fitness-hub' ),
		    'free' => 'yes',
		    'pro'  => 'yes',
	    ),
	    array(
		    'title'=> esc_html__( 'SEO', 'fitness-hub' ),
		    'desc' => esc_html__( 'Developed with high skilled SEO tools.', 'fitness-hub' ),
		    'free' => 'yes',
		    'pro'  => 'yes',
	    ),
	    array(
		    'title'=> esc_html__( 'Widget Area On Footer', 'fitness-hub' ),
		    'desc' => esc_html__( 'Change the number of footers on footer area as you need.', 'fitness-hub' ),
		    'free' => 'yes',
		    'pro'  => 'yes',
	    ),
	    array(
		    'title'=> esc_html__( 'Support Forum', 'fitness-hub' ),
		    'desc' => esc_html__( 'Highly dedicated support team are assigned for your help. Try this today.', 'fitness-hub' ),
		    'free' => esc_html__('Second', 'fitness-hub'),
		    'pro'  => esc_html__('Dedicated With High Priority', 'fitness-hub'),
	    )
    )
);
return new Fitness_Hub_Theme_Info( $config );