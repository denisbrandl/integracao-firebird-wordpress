<?php
/*
Plugin Name: Woocommerce X Firebird
Description: Plugin que permite a integração de informações entre o Woocommerce e uma base Firebird externa
Author:      Denis Brandl - denisbr@gmai..com
Author URI:  http://minorsolucoes.com/
Version: 1.0
License: GPL
*/

class IntegracaoWoocommerceFirebird
{

    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
       
        add_options_page(
            'Integração Firebird', 
            'Integração Firebird', 
            'manage_options', 
            'integracao-firebird-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
       
        $this->options = get_option( 'firebird_name' );
        ?>
        <div class="wrap">
            <h1>Integração Firebird</h1>
            <form method="post" action="options.php">
            <?php
               
                settings_fields( 'firebird_grupo' );
                do_settings_sections( 'integracao-firebird-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'firebird_grupo',
            'firebird_name',
            array( $this, 'sanitize' )
        );

        add_settings_section(
            'setting_section_id',
            'Configurações',
            array( $this, 'print_section_info' ),
            'integracao-firebird-admin'
        );  

        add_settings_field(
            'chave_token_api',
            'Chave a ser utilizada no acesso a API de Importação',
            array( $this, 'chave_token_api_callback' ),
            'integracao-firebird-admin',
            'setting_section_id'
        );
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print '<p> Configurações para a consulta de dados a partir do sistema <strong>TSD</strong>. </p>';
        print '<p> Atenção: Sempre que a chave for modificada, é necessário, modificar o arquivo .env  com a nova chave. </p>';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function chave_token_api_callback()
    {

        printf(
            '<textarea id="chave_token_api" name="firebird_name[chave_token_api]" style="width:50%%;heigth:20%%;">%s</textarea>',
            isset( $this->options['chave_token_api'] ) ? esc_attr( $this->options['chave_token_api']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function app_secret_callback()
    {
        printf(
            '<input type="text" id="app_secret" name="firebird_name[app_secret]" value="%s" />',
            isset( $this->options['app_secret'] ) ? esc_attr( $this->options['app_secret']) : ''
        );
    }
}

if( is_admin() ) {
    $integracao_woocommerce_firebird = new IntegracaoWoocommerceFirebird();
}