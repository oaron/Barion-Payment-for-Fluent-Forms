<?php
namespace FluentBarion\BarionProcessor;
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use FluentForm\App\Helpers\Helper;
use FluentForm\Framework\Helpers\ArrayHelper;
use FluentFormPro\Payments\PaymentHelper;
use FluentFormPro\Payments\PaymentMethods\BaseProcessor;
use FluentBarion\BarionSettings\BarionSettings;
use FluentBarion\BarionWrapper\BarionLoader;
use Barion\BarionClient;
class BarionProcessor extends BaseProcessor
{
    public $method = 'barion';

    protected $form;
    
    public function __construct()
    {
        $this->init();

    }

    public function init()
    {
        add_action('fluentform/process_payment_' . $this->method, array($this, 'handlePaymentAction'), 10, 6);
        add_action('fluentform/payment_frameless_' . $this->method, array($this, 'handleSessionRedirectBack'));
        add_action('fluentform/ipn_endpoint_' . $this->method, array($this, 'handleIpn'));
    }
    
    public function handleIpn()
    {
        if (
    empty($_GET) || 
    empty($_GET['paymentId']) || 
    empty($_GET['submission_id']) || 
    empty($_GET['transaction_hash'])
) {
    return;
}
$paymentId = sanitize_text_field(wp_unslash($_GET['paymentId']));
$submissionId = intval(sanitize_text_field(wp_unslash($_GET['submission_id'])));  // Szanitálás hozzáadva
$this->setSubmissionId($submissionId);

$submission = $this->getSubmission();

$transactionHash = sanitize_text_field(wp_unslash($_GET['transaction_hash']));
$transaction = $this->getTransaction($transactionHash, 'transaction_hash');

if (!$transaction || !$submission || $transaction->payment_method != $this->method) {
    return;
}

if ($this->getMetaData('is_form_action_fired') == 'yes') {
    return;
}

        $barion_client = new \BarionClient( BarionSettings::getPosKey(), BarionSettings::getApiVersion(), BarionSettings::getEnvironment() );
$payment = $barion_client->GetPaymentState( $paymentId );
                if( $payment->Status == \PaymentStatus::Succeeded) {
            
            $status = 'paid';

        }
        else if( $payment->Status == \PaymentStatus::Canceled) {
            
            $status = 'cancelled';
            
        }
        else {

            $status = 'failed';
            
        }
        
        $updateData = array(
            'payment_note'     => maybe_serialize( $payment ),
            'charge_id'        => sanitize_text_field(wp_unslash($payment->PaymentId)),
        );

        $this->updateTransaction($transaction->id, $updateData);
        $this->changeSubmissionPaymentStatus($status);
        $this->changeTransactionStatus($transaction->id, $status);
        $this->recalculatePaidTotal();
        $this->completePaymentSubmission(false);
        $this->setMetaData('is_form_action_fired', 'yes');
        
    }

    public function handlePaymentAction($submissionId, $submissionData, $form, $methodSettings, $hasSubscriptions, $totalPayable)
    {
        $this->setSubmissionId($submissionId);
        $this->form = $form;
        $submission = $this->getSubmission();

        $transactionId = $this->insertTransaction( array(
            'transaction_type' => 'onetime',
            'payment_total'    => $this->getAmountTotal(),
            'status'           => 'pending',
            'currency'         => PaymentHelper::getFormCurrency($form->id),
            'payment_mode'     => $this->getPaymentMode()
        ));

        $transaction = $this->getTransaction($transactionId);

        $this->handleRedirect($transaction, $submission, $form, $methodSettings);
    }

    public function handleRedirect($transaction, $submission, $form, $methodSettings)
    {

        $barion_payment = $this->prepare_payment( $transaction, $submission );
               
        Helper::setSubmissionMeta( $submission->id, '_barion_payment_id', $barion_payment->PaymentId );
        
        if ( $barion_payment->RequestSuccessful === true ) {

          do_action('ff_log_data', array(
                'parent_source_id' => $submission->form_id,
                'source_type'      => 'submission_item',
                'source_id'        => $submission->id,
                'component'        => 'Payment',
                'status'           => 'info',
                'title'            => __( 'Redirect to Barion.com', 'integration-barion-payment-gateway-fluent-forms'),
                'description'      => __( 'User redirect to Barion.com for completing the payment', 'integration-barion-payment-gateway-fluent-forms')
            ));
            
            wp_send_json_success( array(
                'nextAction'   => 'payment',
                'actionName'   => 'normalRedirect',
                'redirect_url' => $barion_payment->PaymentRedirectUrl,
                'message'      => __('You are redirecting to Barion.com to complete the purchase. Please wait while you are redirecting....', 'integration-barion-payment-gateway-fluent-forms'),
                'result'       => array(
                    'insert_id' => $submission->id
                )
            ), 1000);
        
        }
        else{
            
            $error_string = '';
            
            if( $barion_payment->Errors ){
                
                foreach( $barion_payment->Errors as $error ){
                    
                    $error_string .= implode( ' - ', (array)$error );
                    
                }
                
            }
            
            do_action('ff_log_data', array(
                'parent_source_id' => $submission->form_id,
                'source_type'      => 'submission_item',
                'source_id'        => $submission->id,
                'component'        => 'Payment',
                'status'           => 'error',
                'title'            => __( 'Barion error', 'integration-barion-payment-gateway-fluent-forms'),
                'description'      => $error_string
            ));

            wp_send_json( array(
                'errors' => $error_string,
            ), 423);                        
            
        }

        
        
        
    }
    
    public function prepare_payment( $transaction, $submission ) {
        
        $barion_client = new \BarionClient( BarionSettings::getPosKey(), BarionSettings::getApiVersion(), BarionSettings::getEnvironment() );
        
        $payment_transaction = new \PaymentTransactionModel();
        $payment_transaction->POSTransactionId = $submission->id; //order id
        $payment_transaction->Payee = BarionSettings::getPayee();
        $payment_transaction->Total = number_format((float) $transaction->payment_total / 100, 2, '.', '');
        $payment_transaction->Currency = $transaction->currency;
        $payment_transaction->Comment = $transaction->payment_note;

        $order_items = $this->getOrderItems();

        if ( $order_items ) {
            
            foreach ( $order_items as $order_item ) {

                $item_model = new \ItemModel();
                $item_model->Name = $order_item->item_name;
                $item_model->Description = $order_item->item_name;
                $item_model->Quantity = $order_item->quantity;
                $item_model->Unit = "piece";
                $item_model->UnitPrice = number_format( (float) $order_item->item_price / 100, 2, '.', '' );
                $item_model->ItemTotal = number_format( (float) $order_item->line_total / 100, 2, '.', '' );

                $payment_transaction->AddItem( $item_model );

            }
            
        }
        
                $RedirectUrl = add_query_arg(array(
            'fluentform_payment' => $submission->id,
            'payment_method'     => $this->method,
            'transaction_hash'   => $transaction->transaction_hash,
            'type'               => 'success'
        ), home_url('/'));

        $ipnDomain = home_url();
        if(defined('FLUENTFORM_PAY_IPN_DOMAIN') && FLUENTFORM_PAY_IPN_DOMAIN) {
            $ipnDomain = FLUENTFORM_PAY_IPN_DOMAIN;
        }

        $CallbackUrl = add_query_arg(array(
            'fluentform_payment_api_notify' => 1,
            'payment_method'                => $this->method,
            'submission_id'                 => $submission->id,
            'transaction_hash'              => $transaction->transaction_hash,
        ), $ipnDomain);
        
        
        $payment_request = new \PreparePaymentRequestModel();
        $payment_request->GuestCheckout = true;
        $payment_request->PaymentType = \PaymentType::Immediate;
        $payment_request->FundingSources = array( \FundingSourceType::All );
        $payment_request->PaymentRequestId = $submission->id; //order id
        $payment_request->PayerHint = $transaction->payer_email;
        $payment_request->Locale = BarionSettings::getLocale();
        $payment_request->OrderNumber = $submission->id; //order id
        $payment_request->Currency = $transaction->currency;
        $payment_request->RedirectUrl = $RedirectUrl;
        $payment_request->CallbackUrl = $CallbackUrl;
        $payment_request->AddTransaction( $payment_transaction );
        
        $barion_payment = $barion_client->PreparePayment( $payment_request );
        
        return $barion_payment;
        
    }

    protected function getPaymentMode($formId = false)
    {
        $isLive = BarionSettings::isLive($formId);
        if($isLive) {
            return 'live';
        }
        return 'sandbox';
    }
    
    
    public function handleSessionRedirectBack($data)
    {
        $submissionId = intval($data['fluentform_payment']);
        $this->setSubmissionId($submissionId);

        $submission = $this->getSubmission();

        $transactionHash = sanitize_text_field(wp_unslash($data['transaction_hash']));
        $transaction = $this->getTransaction($transactionHash, 'transaction_hash');

        if (!$transaction || !$submission) {
            return;
        }
        
        $barion_client = new \BarionClient( BarionSettings::getPosKey(), BarionSettings::getApiVersion(), BarionSettings::getEnvironment() );
        
        $payment = $barion_client->GetPaymentState( $data['paymentId'] );
        
        $isNew = false;
        
        if( $payment->Status == \PaymentStatus::Succeeded) {

            $returnData = array(
                'insert_id' => $submission->id,
                'title'     => __('Payment succeeded.', 'integration-barion-payment-gateway-fluent-forms'),
                'result'    => false,
                'error'     => __('Payment succeeded.', 'integration-barion-payment-gateway-fluent-forms'),
                'type' => 'success'
            );
            
            $isNew = $this->getMetaData('is_form_action_fired') != 'yes';
            
            $returnData = $this->getReturnData();
            
            $returnData['type'] = 'success';
            

        }
        else if( $payment->Status == \PaymentStatus::Canceled) {
            
            $returnData = array(
                'insert_id' => $submission->id,
                'title'     => __('Payment canceled.', 'integration-barion-payment-gateway-fluent-forms'),
                'result'    => false,
                'error'     => __('Payment canceled.', 'integration-barion-payment-gateway-fluent-forms'),
                'type' => 'error'
            );

        }
        else if( $payment->Status == \PaymentStatus::Expired) {

            $returnData = array(
                'insert_id' => $submission->id,
                'title'     => __('Payment is expired.', 'integration-barion-payment-gateway-fluent-forms'),
                'result'    => false,
                'error'     => __('Payment is expired.', 'integration-barion-payment-gateway-fluent-forms'),
                'type' => 'error'
            );

        }


        $returnData['is_new'] = $isNew;

        $this->showPaymentView($returnData);
    }

}