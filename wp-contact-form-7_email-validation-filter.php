<?php
/*
Plugin Name: Email Validation Filter for Contact Form 7
Plugin URI: https://github.com/asamaruk
Description: Provides additional functionality to Contact Form 7's email verification functionality. There are three filters: rejection filter, RFC filter, and DNS filter.
Author: asamaruk
Author URI: https://github.com/asamaruk
Text Domain: email-validation-filter-for-contact-form-7
Version: 1.0.2
License: GPLv2
Domain Path: /languages/
*/
add_action('init', 'WPCF7_EMAIL_VALIDATION_FILTER::init');
class WPCF7_EMAIL_VALIDATION_FILTER {

    const VERSION           = '1.0.1';
    const PLUGIN_ID         = 'wpcf7-email-validation-filter';
    const PLUGIN_TEXTDOMAIN = 'email-validation-filter-for-contact-form-7';
    const PLUGIN_SAVE_ID    = 'email_validation_filter';

    static function init() {
        return new self();
    }

    public function __construct() {
        $this->add_actions();
        $this->add_filters();
    }

	public function get_plugin_fields() {
        $array = [
            "reject" => array(
                "active" => false,
                "lists" =>  '',
                "error" =>  esc_html( __( 'This is an email address that cannot be registered.', self::PLUGIN_TEXTDOMAIN ) ),
            ),
            "rfc" => array(
                "active" => false,
                "lists" =>  '',
                "error" =>  esc_html( __( 'Period [.]. is used at the beginning, before the at mark [@], or consecutively.', self::PLUGIN_TEXTDOMAIN ) ),
            ),
            "dns" => array(
                "active" => false,
                "lists" =>  '',
                "error" =>  esc_html( __( 'This is an email address that cannot be sent. Please check for spelling errors.', self::PLUGIN_TEXTDOMAIN ) ),
            ),
          ];
		return $array;
	}

	private function add_actions() {
        add_action( 'admin_notices', array( $this, 'action_admin_notices' ) );
        add_action( 'plugins_loaded', array( $this, 'action_plugin_loaded' ) );
        add_action( 'wpcf7_save_contact_form', array( $this, 'action_wpcf7_save_contact_form' ), 10, 2 );
        add_action( 'wpcf7_editor_panels', array( $this, 'action_wpcf7_editor_panels') );
	}

	private function add_filters() {
        if ( version_compare( WPCF7_VERSION, '5.5.3', '>=' ) ) {
            add_filter( 'wpcf7_pre_construct_contact_form_properties', array( $this, 'filter_wpcf7_contact_form_properties' ), 10, 2 );
        } else {
            add_filter( 'wpcf7_contact_form_properties', array( $this, 'filter_wpcf7_contact_form_properties' ), 10, 2 );
        }
        add_filter( 'wpcf7_validate_email',  array( $this, 'filter_wpcf7_validate_email' ),  12, 2 );
        add_filter( 'wpcf7_validate_email*', array( $this, 'filter_wpcf7_validate_email' ),  12, 2 );
	}

	public function action_admin_notices() {
		if ( defined( 'WPCF7_VERSION' ) < 5.0 ) {
            echo '<div class="error"><p>',
            __( 'Error: Email Validation Filter for Contact Form 7 depends on Contact Form 7. Please update Contact Form 7.', self::PLUGIN_TEXTDOMAIN ),
            '</p></div>';
		} elseif (!defined('WPCF7_VERSION') ) {
            echo '<div class="error"><p>',
            __( 'Error: Email Validation Filter for Contact Form 7 depends on Contact Form 7. Please install Contact Form 7.', self::PLUGIN_TEXTDOMAIN ),
            '</p></div>';
		}
	}

    public function action_plugin_loaded() {
        load_plugin_textdomain( self::PLUGIN_TEXTDOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages' ); 
    }

    public function filter_wpcf7_contact_form_properties ( $contact_form ) { 
        $add_defaults = array();
        $add_defaults[self::PLUGIN_SAVE_ID] = $this->get_plugin_fields();

        return wp_parse_args( $contact_form, $add_defaults );
    }

    public function action_wpcf7_save_contact_form( $contact_form, $args) {
        $post = ( $args[self::PLUGIN_ID] )? $args[self::PLUGIN_ID] : null ;
        if ( empty( $post ) ) return false;

        $properties = array();
        $properties[self::PLUGIN_SAVE_ID] = $post;
        $contact_form->set_properties( $properties );
    }

    public function action_wpcf7_editor_panels ( $contact_form_panels ) {
        $contact_form_panels[self::PLUGIN_ID .'-panel'] = array(
            'title' => __( 'Email Validation Filter', self::PLUGIN_TEXTDOMAIN ),
			'callback' => array( $this, 'callback_wpcf7_editor_panel' ),
        );

        return $contact_form_panels;
    }

    public function callback_wpcf7_editor_panel( $contact_form ){
        $field_args = wp_parse_args( $contact_form->prop( self::PLUGIN_SAVE_ID ), $this->get_plugin_fields() );
        ob_start();
        echo '<style>#contact-form-editor #', self::PLUGIN_ID, '-panel .form-table th{ width:200px; }</style>';
        $this->plugin_edit_reject_filter( $field_args );
        echo '<br class="clear">';
        $this->plugin_edit_rfc_filter( $field_args );
        echo '<br class="clear">';
        $this->plugin_edit_dns_filter( $field_args );
        echo ob_get_clean();
    }

    public function plugin_edit_reject_filter ( $args ){
        $template = array(
            'id' => self::PLUGIN_ID,
            'template_id' => 'reject',
            'title' => esc_html( __( 'Reject Filter Settings', self::PLUGIN_TEXTDOMAIN ) ),
            'description' =>  esc_html(__( 'Restrict submissions from the form by specifying the email address or domain of the user you wish to reject.', self::PLUGIN_TEXTDOMAIN ) ),
            'active' => array(
                'title' => esc_html( __( 'Activate Rejection Filter', self::PLUGIN_TEXTDOMAIN ) ),
                'label' => esc_html( __( 'Allow additions to Contact Form 7', self::PLUGIN_TEXTDOMAIN ) ),
            ),
            'lists' => array(
                'title' => esc_html( __( 'Email address to be rejected', self::PLUGIN_TEXTDOMAIN ) ),
                'description' => esc_html( __( 'Enter your email address or domain name separated by a new line. If you are entering a domain, add [@] to the beginning and type something like "@example.com".', self::PLUGIN_TEXTDOMAIN ) ),    
            ),
            'error' => array(
                'title' => esc_html( __( 'Error message', self::PLUGIN_TEXTDOMAIN ) ),
            ),
        );
        $plugin_fields = $this->get_plugin_fields();
        $data = wp_parse_args( $args, $plugin_fields );
        $data = array_replace_recursive( $plugin_fields, array_intersect_key( $data, $plugin_fields ) );

        echo '<h2>', esc_html( $template['title'] ), '</h2>',
        '<p>', esc_html( $template['description'] ), '</p>',
        '<table class="form-table">',
        '<tbody><tr>',
        '<th scope="row">', esc_html( $template['active']['title'] ), '</th>',
        '<td><fieldset><legend class="screen-reader-text"><span>', esc_html( $template['active']['title'] ), '</span></legend>',
        '<label for="', esc_html( $template["id"] ) . "-" . esc_html( $template["template_id"] ) . "-active", '">',
        '<input name="', esc_html( $template["id"] ) . "[" . esc_html( $template["template_id"] ) . "][active]", '" type="checkbox" id="', esc_html( $template["id"] ) . "-" . esc_html( $template["template_id"] ) . "-active", '" value="1"', checked( '1', esc_html( $data[$template["template_id"]]['active'] ) ), '>', esc_html( $template['active']['label'] ), '</label>',
        '</fieldset></td>',
        '</tr>',
        '<tr>',
        '<th scope="row">', esc_html( $template['lists']['title'] ), ' </th>',
        '<td><fieldset><legend class="screen-reader-text"><span>', esc_html( $template['lists']['title'] ), '</span></legend>',
        '<p><label for="', esc_html( $template["id"] ) . "-lists", '">', esc_html( $template['lists']['description'] ), '</label></p>',
        '<p>',
        '<textarea name="', esc_html( $template["id"] ) . "[" . esc_html( $template["template_id"] ) . "][lists]", '" cols="100" rows="5" id="', esc_html( $template["id"] ) . "-lists", '" class="large-text code">', esc_textarea( $data[$template["template_id"]]["lists"] ), '</textarea>',
        '</p>',
        '</fieldset></td>',
        '</tr>',
        '<tr>',
        '<th scope="row">', esc_html( $template['error']['title'] ), '</th>',
        '<td><fieldset><legend class="screen-reader-text"><span>', esc_html( $template['error']['title'] ), '</span></legend>',
        '<input name="', esc_html( $template["id"] ) . "[" . esc_html( $template["template_id"] ) ."][error]", '" type="text" id="', esc_html( $template["id"] ) . "-" . esc_html( $template["template_id"] ) . "-error", '" class="large-text" value="',  esc_attr( $data[$template["template_id"]]["error"] ),'">',
        '</fieldset></td>',
        '</tr>',
        '</tbody></table>';
    }

    public function plugin_edit_rfc_filter ( $args ){
        $template = array(
            'id' => self::PLUGIN_ID,
            'template_id' => 'rfc',
            'title' => esc_html( __( 'RFC Filter Settings', self::PLUGIN_TEXTDOMAIN ) ),
            'description' =>  esc_html(__( 'Reject email addresses that do not conform to the specifications and requirements set forth in the RFC.', self::PLUGIN_TEXTDOMAIN ) ),
            'active' => array(
                'title' => esc_html( __( 'Activate RFC Filter', self::PLUGIN_TEXTDOMAIN ) ),
                'label' => esc_html( __( 'Allow additions to Contact Form 7', self::PLUGIN_TEXTDOMAIN ) ),
            ),
            // 'lists' => array(
            //     'title' => esc_html( __( 'Domains to exclude', self::PLUGIN_TEXTDOMAIN ) ),
            // ),
            'error' => array(
                'title' => esc_html( __( 'Error message', self::PLUGIN_TEXTDOMAIN ) ),
            ),
        );
        $plugin_fields = $this->get_plugin_fields();
        $data = wp_parse_args( $args, $plugin_fields );
        $data = array_replace_recursive( $plugin_fields, array_intersect_key( $data, $plugin_fields ) );

        echo '<h2>', esc_html( $template['title'] ), '</h2>',
        '<p>', esc_html( $template['description'] ), '</p>',
        '<table class="form-table">',
        '<tbody><tr>',
        '<th scope="row">', esc_html( $template['active']['title'] ), '</th>',
        '<td><fieldset><legend class="screen-reader-text"><span>', esc_html( $template['active']['title'] ), '</span></legend>',
        '<label for="', esc_html( $template["id"] ) . "-" . esc_html( $template["template_id"] ) . "-active", '">',
        '<input name="', esc_html( $template["id"] ) . "[" . esc_html( $template["template_id"] ) . "][active]", '" type="checkbox" id="', esc_html( $template["id"] ) . "-" . esc_html( $template["template_id"] ) . "-active", '" value="1"', checked( '1', esc_html( $data[$template["template_id"]]['active'] ) ), '>', esc_html( $template['active']['label'] ), '</label>',
        '</fieldset></td>',
        '</tr>',
        '<tr>',
        '<th scope="row">', esc_html( $template['error']['title'] ), '</th>',
        '<td><fieldset><legend class="screen-reader-text"><span>', esc_html( $template['error']['title'] ), '</span></legend>',
        '<input name="', esc_html( $template["id"] ) . "[" . esc_html( $template["template_id"] ) ."][error]", '" type="text" id="', esc_html( $template["id"] ) . "-" . esc_html( $template["template_id"] ) . "-error", '" class="large-text" value="',  esc_attr( $data[$template["template_id"]]["error"] ),'">',
        '</fieldset></td>',
        '</tr>',
        '</tbody></table>';
    }

    public function plugin_edit_dns_filter ( $args ){
        $template = array(
            'id' => self::PLUGIN_ID,
            'template_id' => 'dns',
            'title' => esc_html( __( 'DNS Filter Settings', self::PLUGIN_TEXTDOMAIN ) ),
            'description' =>  esc_html(__( 'The domain of the email address entered is checked to see if it is registered in the DNS, and the email address is checked to see if it can be sent.', self::PLUGIN_TEXTDOMAIN ) ),
            'active' => array(
                'title' => esc_html( __( 'Activate DNS Filter', self::PLUGIN_TEXTDOMAIN ) ),
                'label' => esc_html( __( 'Allow additions to Contact Form 7', self::PLUGIN_TEXTDOMAIN ) ),
            ),
            'error' => array(
                'title' => esc_html( __( 'Error message', self::PLUGIN_TEXTDOMAIN ) ),
            ),
        );
        $plugin_fields = $this->get_plugin_fields();
        $data = wp_parse_args( $args, $plugin_fields );
        $data = array_replace_recursive( $plugin_fields, array_intersect_key( $data, $plugin_fields ) );

        echo '<h2>', esc_html( $template['title'] ), '</h2>',
        '<p>', esc_html( $template['description'] ), '</p>',
        '<table class="form-table">',
        '<tbody><tr>',
        '<th scope="row">', esc_html( $template['active']['title'] ), '</th>',
        '<td><fieldset><legend class="screen-reader-text"><span>', esc_html( $template['active']['title'] ), '</span></legend>',
        '<label for="', esc_html( $template["id"] ) . "-" . esc_html( $template["template_id"] ) . "-active", '">',
        '<input name="', esc_html( $template["id"] ) . "[" . esc_html( $template["template_id"] ) . "][active]", '" type="checkbox" id="', esc_html( $template["id"] ) . "-" . esc_html( $template["template_id"] ) . "-active", '" value="1"', checked( '1', esc_html( $data[$template["template_id"]]['active'] ) ), '>', esc_html( $template['active']['label'] ), '</label>',
        '</fieldset></td>',
        '</tr>',
        '<tr>',
        '<th scope="row">', esc_html( $template['error']['title'] ), '</th>',
        '<td><fieldset><legend class="screen-reader-text"><span>', esc_html( $template['error']['title'] ), '</span></legend>',
        '<input name="', esc_html( $template["id"] ) . "[" . esc_html( $template["template_id"] ) ."][error]", '" type="text" id="', esc_html( $template["id"] ) . "-" . esc_html( $template["template_id"] ) . "-error", '" class="large-text" value="',  esc_attr( $data[$template["template_id"]]["error"] ),'">',
        '</fieldset></td>',
        '</tr>',
        '</tbody></table>';
    }

    public function filter_wpcf7_validate_email( $result, $tag ){
        $form_id = sanitize_text_field( $_POST['_wpcf7'] );
        $form_id = !empty( $form_id )? $form_id : null;
        if( empty( $form_id ) ) return $result;

        $tag = new WPCF7_FormTag( $tag );
        $post_email = isset ( $_POST[$tag->name] ) === true ? trim( sanitize_text_field($_POST[$tag->name]) ) : '';
        if( empty( $post_email ) ) return $result;

        $contact_form = wpcf7_contact_form( $form_id );
        $array = $contact_form->prop( self::PLUGIN_SAVE_ID );
        $this->plugin_reject_filter( $result, $tag, $array, $post_email);
        $this->plugin_rfc_filter( $result, $tag, $array, $post_email);
        $this->plugin_dns_filter( $result, $tag, $array, $post_email);

        return $result;
    }

    public function plugin_reject_filter( $result, $tag, $array, $post_email ){
        if ( $request = $array['reject'] and isset( $request['active'] ) !== true and isset( $request['lists'] ) !== true ) return $result;
        preg_match( '/@([^@\[]++)\z/', $post_email, $post_email_domains );
        $post_email_domain = $post_email_domains[0];

        $replace_newline_list = str_replace( "\n", ",", $request['lists'] );
        $replace_unicode_list = preg_replace('/[\p{Cc}\p{Cf}\p{Z}]++|[\p{Cc}\p{Cf}\p{Z}]++/u', '', preg_replace('/(,)+/us','$1', $replace_newline_list));
        $mails = explode(",", $replace_unicode_list);

        $array_block_domain = array_filter( $mails, function( $mails ) {
            if ( strpos( $mails, '@' ) !== false and strncmp( $mails , '@', 1 ) === 0 ) return $mails;
        });

        $flip_array_block_domain = array_flip( $array_block_domain );
        if ( isset( $flip_array_block_domain[$post_email_domain] ) ){
            $result->invalidate ( $tag, $request['error'] ); 
        }

        $array_block_email = array_filter( $mails, function( $mails ) {
            if ( strpos( $mails, '@' ) !== false and strncmp( $mails , '@', 1 ) !== 0 ) return $mails;
        });

        $flip_array_block_email = array_flip( $array_block_email );
        if ( isset( $flip_array_block_email[$post_email] ) ){
            $result->invalidate ( $tag, $request['error'] ); 
        }

        return $result;
    }
    
    public function plugin_rfc_filter( $result, $tag, $array, $post_email ){
        if ( $request = $array['rfc'] and isset( $request['active'] ) !== true ) return $result;

        $ver = (float) phpversion();
        if ($ver >= 7.1) {
            $email_validate = filter_var($post_email, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE);
        } else {
            $email_validate = filter_var($post_email, FILTER_VALIDATE_EMAIL);
        }

        if ( $email_validate === false ){
            $result->invalidate ( $tag, $request['error'] ); 
        }

        return $result;
    }
    
    public function plugin_dns_filter( $result, $tag, $array, $post_email ){
        if ( $request = $array['dns'] and isset( $request['active'] ) !== true ) return $result;

        preg_match( '/@([^@\[]++)\z/', $post_email, $post_email_domains );
        $post_email_domain = $post_email_domains[1];

        switch ( true ) {
            case checkdnsrr( $post_email_domain, 'A' ):
            case checkdnsrr( $post_email_domain, 'MX' ):
            case checkdnsrr( $post_email_domain, 'AAAA' ):
                return $result;
            default:
                return $result->invalidate ( $tag, $request['error'] );
        }
    }
}