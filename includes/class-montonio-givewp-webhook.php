<?php
/**
 * Give - Stripe Core | Process Webhooks
 *
 * @package    Give
 * @since 2.5.0
 *
 * @subpackage Stripe Core
 * @copyright  Copyright (c) 2019, GiveWP
 * @license    https://opensource.org/licenses/gpl-license GNU Public License
 */

use Give\Log\Log;
use Give\PaymentGateways\Gateways\Stripe\Webhooks\Listeners\ChargeRefunded;
use Give\PaymentGateways\Gateways\Stripe\Webhooks\Listeners\CheckoutSessionCompleted;
use Give\PaymentGateways\Gateways\Stripe\Webhooks\Listeners\PaymentIntentPaymentFailed;
use Give\PaymentGateways\Gateways\Stripe\Webhooks\Listeners\PaymentIntentSucceeded;
use Give\PaymentGateways\Gateways\Stripe\Webhooks\StripeEventListener;
use Stripe\Event;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Give_Montonio_Webhooks')) {
    /**
     * Class Give_Stripe_Webhooks
     *
     * @since 2.5.0
     */
    class Give_Montonio_Webhooks
    {


    }
}

new Give_Stripe_Webhooks();
