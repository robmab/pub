<?php
/*adding theme options panel*/
$wp_customize->add_panel( 'fitness-hub-design-panel', array(
    'priority'       => 80,
    'capability'     => 'edit_theme_options',
    'title'          => esc_html__( 'Layout/Design Option', 'fitness-hub' )
) );


/*
* file for front page hiding content
*/
require fitness_hub_file_directory('acmethemes/customizer/design-options/front-page-content.php');

/*
* file for animation options
*/
require fitness_hub_file_directory('acmethemes/customizer/design-options/animation.php');

/*
* file for sidebar layout
*/
require fitness_hub_file_directory('acmethemes/customizer/design-options/sidebar-layout.php');

/*
* file for front page sidebar layout options
*/
require fitness_hub_file_directory('acmethemes/customizer/design-options/front-page-sidebar-layout.php');

/*
* file for front archive sidebar layout options
*/
require fitness_hub_file_directory('acmethemes/customizer/design-options/archive-sidebar-layout.php');

/*
* file for blog layout
*/
require fitness_hub_file_directory('acmethemes/customizer/design-options/blog-layout.php');

/*
* file for color options
*/
require fitness_hub_file_directory('acmethemes/customizer/design-options/colors-options.php');