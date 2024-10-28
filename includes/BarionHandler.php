<?php
namespace FluentBarion\BarionHandler;
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use FluentForm\Framework\Helpers\ArrayHelper;
use FluentFormPro\Payments\PaymentMethods\BasePaymentMethod;
use FluentBarion\BarionSettings\BarionSettings;
use FluentBarion\BarionProcessor\BarionProcessor;
class BarionHandler extends BasePaymentMethod
{
    public function __construct()
    {
        parent::__construct('barion');
        
        $this->init();
    }

    public function init()
    {
        add_filter('fluentform_payment_method_settings_validation_'.$this->key, array($this, 'validateSettings'), 10, 2);

        if(!$this->isEnabled()) {
            return;
        }

        add_filter('fluentform/transaction_data_' . $this->key, array( $this, 'modifyTransaction' ), 10, 1);

        add_filter( 'fluentform/available_payment_methods', array( $this, 'pushPaymentMethodToForm' ) );

        new BarionProcessor();
    }

    public function pushPaymentMethodToForm($methods)
    {
        
        $methods[ $this->key ] = array( 
            'title' => __( 'Barion', 'integration-barion-payment-gateway-fluent-forms' ),
            'enabled' => 'yes',
            'method_value' => $this->key,
            'settings' => array( 
                'option_label' => array( 
                    'type' => 'text',
                    'template' => 'inputText',
                    'value' => __( 'Pay with Barion', 'integration-barion-payment-gateway-fluent-forms' ),
                    'label' => __( 'Barion', 'integration-barion-payment-gateway-fluent-forms' )
                 )
             )
         );

        return $methods;
    }

    public function validateSettings($errors, $settings)
    {
        if(ArrayHelper::get($settings, 'is_active') == 'no') {
            return array();
        }
        
        if( !ArrayHelper::get( $settings, 'payee' ) ) {
            $errors['payee'] = __( 'Payee is required', 'integration-barion-payment-gateway-fluent-forms' );
        }

        if( !ArrayHelper::get( $settings, 'payment_mode' ) ) {
            $errors['payment_mode'] = __( 'Please select Payment Mode', 'integration-barion-payment-gateway-fluent-forms' );
        }
        else{
            
            if( ArrayHelper::get( $settings, 'payment_mode' ) == 'sandbox' ) {
                if( !ArrayHelper::get( $settings, 'sandbox_poskey' ) ) {
                    $errors['sandbox_poskey'] = __('Barion Sandbox POSKey is required', 'integration-barion-payment-gateway-fluent-forms');
                }
            }
            else if( ArrayHelper::get( $settings, 'payment_mode' ) == 'live' ) {
                if( !ArrayHelper::get( $settings, 'live_poskey' ) ) {
                    $errors['live_poskey'] = __('Barion Live POSKey is required', 'integration-barion-payment-gateway-fluent-forms');
                }
            }
            
        }
        
        

        return $errors;
    }

    public function modifyTransaction($transaction)
    {
        if (is_array($transaction->payment_note) && $transactionUrl = ArrayHelper::get($transaction->payment_note, '_links.dashboard.href')) {
            $transaction->action_url =  $transactionUrl;
        }

        return $transaction;
    }

    public function isEnabled()
    {
        $settings = $this->getGlobalSettings();
        return $settings['is_active'] == 'yes';
    }

    public function getGlobalFields()
    {
        
        return array( 
            'label' => __( 'Barion', 'integration-barion-payment-gateway-fluent-forms' ),
            'fields' => array( 
                array( 
                    'settings_key' => 'is_active',
                    'type' => 'yes-no-checkbox',
                    'label' => __( 'Status', 'integration-barion-payment-gateway-fluent-forms' ),
                    'checkbox_label' => __( 'Enable Barion Payment Method', 'integration-barion-payment-gateway-fluent-forms' )
                ),
                array( 
                    'settings_key' => 'payment_mode',
                    'type' => 'input-radio',
                    'label' => __( 'Payment Mode', 'integration-barion-payment-gateway-fluent-forms' ),
                    'options' => array( 
                        'sandbox' => __( 'Sandbox Mode', 'integration-barion-payment-gateway-fluent-forms' ),
                        'live' => __( 'Live Mode', 'integration-barion-payment-gateway-fluent-forms' )
                    ),
                    'inline_help' => __( 'For testing purposes you should select Sandbox Mode otherwise select Live mode. The POSKey is different in the sandbox and live Barion systems.', 'integration-barion-payment-gateway-fluent-forms' ),
                    'check_status' => 'yes'
                ),
                array( 
                    'settings_key' => 'payee',
                    'type' => 'input-text',
                    'placeholder' => __( 'webshop@example.com', 'integration-barion-payment-gateway-fluent-forms' ),
                    'label' => __( 'Payee', 'integration-barion-payment-gateway-fluent-forms' ),
                    'inline_help' => __( 'That property indicates the user wallet that will receive the amount of the transaction.', 'integration-barion-payment-gateway-fluent-forms' ),
                    'check_status' => 'yes'
                ),
                array( 
                    'settings_key' => 'sandbox_poskey',
                    'type' => 'input-text',
                                        'placeholder' => __( 'Sandbox POSKey', 'integration-barion-payment-gateway-fluent-forms' ),
                    'label' => __( 'Sandbox POSKey', 'integration-barion-payment-gateway-fluent-forms' ),
                    'inline_help' => __( 'Provide your Sandbox POSKey for your sandbox payments.', 'integration-barion-payment-gateway-fluent-forms' ),
                    'check_status' => 'yes'
                ),
                array( 
                    'settings_key' => 'live_poskey',
                    'type' => 'input-text',
                                        'placeholder' => __( 'Live POSKey', 'integration-barion-payment-gateway-fluent-forms' ),
                    'label' => __( 'Live POSKey', 'integration-barion-payment-gateway-fluent-forms' ),
                    'inline_help' => __( 'Provide your Live POSKey for your live payments. The POSKey is different in the sandbox and live Barion systems.', 'integration-barion-payment-gateway-fluent-forms' ),
                    'check_status' => 'yes'
                ),
                array( 
                    'settings_key' => 'pixel_key',
                    'type' => 'input-text',
                                        'placeholder' => __( 'Barion Pixel Key', 'integration-barion-payment-gateway-fluent-forms' ),
                    'label' => __( 'Barion Pixel Key', 'integration-barion-payment-gateway-fluent-forms' ),
                    'inline_help' => sprintf( __('HELP: %s.', 'integration-barion-payment-gateway-fluent-forms'), '<a target="_blank" href="https://docs.barion.com/Getting_started_with_the_Barion_Pixel">https://docs.barion.com/Getting_started_with_the_Barion_Pixel</a>' ),
                    'check_status' => 'yes'
                ),
             )
         );
         
        
    }

    public function getGlobalSettings()
    {
        return BarionSettings::getSettings();
    }
}
