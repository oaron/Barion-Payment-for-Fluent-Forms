<?php
namespace FluentBarion\BarionSettings;
use FluentForm\Framework\Helpers\ArrayHelper;
use FluentFormPro\Payments\PaymentHelper;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BarionSettings
{
    public static function getSettings()
    {
        $defaults = array(
            'is_active' => 'no',
            'payment_mode' => 'sandbox',
            'payee' => '',
            'sandbox_poskey' => '',
            'live_poskey' => '',
            'pixel_key' => ''
        );

        return wp_parse_args(get_option('fluentform_payment_settings_barion', array()), $defaults);
    }

    public static function isLive($formId = false)
    {
        $settings = self::getSettings();
        
        if( $settings['payment_mode'] == 'live' ){
            return true;
        }
        else{
            return false;
        }
        
    }
    
    public static function getPosKey()
    {
        $settings = self::getSettings();
        
        if( $settings['payment_mode'] == 'live' ){
            return $settings['live_poskey'];
        }
        else{
            return $settings['sandbox_poskey'];
        }

    }
    
    public static function getPayee()
    {
        $settings = self::getSettings();
        
        return $settings['payee'];

    }
    
    public static function getApiVersion()
    {
        return 2;

    }
    
    public static function getEnvironment( )
    {
        
        $settings = self::getSettings();
        
        if( $settings['payment_mode'] == 'live' ){
            return \BarionEnvironment::Prod;
        }
        else{
            return \BarionEnvironment::Test;
        }
        
    }
    
    public static function getLocale() 
    {
        
        switch ( get_locale() ) {
            case "hu_HU":
                return \UILocale::HU;
            case "de_DE":
                return \UILocale::DE;
            case "sl_SI":
                return \UILocale::SL;
            case "sk_SK":
                return \UILocale::SK;
            case "fr_FR":
                return \UILocale::FR;
            case "cs_CZ":
                return \UILocale::CZ;
            case "el_GR":
                return \UILocale::GR;
            default:
                return \UILocale::EN;
        }
        
    }
    
    public static function getPixelKey()
    {
        $settings = self::getSettings();
        
        return $settings['pixel_key'];

    }
}