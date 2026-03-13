<?php

/**
 * @link       http://fleisher.ee
 * @since      1.0.0
 *
 * @package    Montonio_Givewp
 * @subpackage Montonio_Givewp/includes
 */

use Give\Log\Log;

/**
 * @package    Montonio_Givewp
 * @subpackage Montonio_Givewp/includes
 * @author     Pavel Fleisher <pavel@fleisher.ee>
 */
class montonio_Givewp_Loader
{
    /**
     * The array of actions registered with WordPress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array $actions The actions registered with WordPress to fire when the plugin loads.
     */
    protected $actions;

    /**
     * The array of filters registered with WordPress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array $filters The filters registered with WordPress to fire when the plugin loads.
     */
    protected $filters;

    /**
     * Initialize the collections used to maintain the actions and filters.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        $this->actions = [];
        $this->filters = [];

        add_action("init", [$this, "listen"]);
    }

    /**
     * Add a new action to the collection to be registered with WordPress.
     *
     * @param string $hook The name of the WordPress action that is being registered.
     * @param object $component A reference to the instance of the object on which the action is defined.
     * @param string $callback The name of the function definition on the $component.
     * @param int $priority Optional. The priority at which the function should be fired. Default is 10.
     * @param int $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
     * @since    1.0.0
     */
    public function add_action(
        $hook,
        $component,
        $callback,
        $priority = 10,
        $accepted_args = 1
    ) {
        $this->actions = $this->add(
            $this->actions,
            $hook,
            $component,
            $callback,
            $priority,
            $accepted_args
        );
    }

    /**
     * Add a new filter to the collection to be registered with WordPress.
     *
     * @param string $hook The name of the WordPress filter that is being registered.
     * @param object $component A reference to the instance of the object on which the filter is defined.
     * @param string $callback The name of the function definition on the $component.
     * @param int $priority Optional. The priority at which the function should be fired. Default is 10.
     * @param int $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1
     * @since    1.0.0
     */
    public function add_filter(
        $hook,
        $component,
        $callback,
        $priority = 10,
        $accepted_args = 1
    ) {
        $this->filters = $this->add(
            $this->filters,
            $hook,
            $component,
            $callback,
            $priority,
            $accepted_args
        );
    }

    /**
     * A utility function that is used to register the actions and hooks into a single
     * collection.
     *
     * @param array $hooks The collection of hooks that is being registered (that is, actions or filters).
     * @param string $hook The name of the WordPress filter that is being registered.
     * @param object $component A reference to the instance of the object on which the filter is defined.
     * @param string $callback The name of the function definition on the $component.
     * @param int $priority The priority at which the function should be fired.
     * @param int $accepted_args The number of arguments that should be passed to the $callback.
     * @return   array                                  The collection of actions and filters registered with WordPress.
     * @since    1.0.0
     * @access   private
     */
    private function add(
        $hooks,
        $hook,
        $component,
        $callback,
        $priority,
        $accepted_args
    ) {
        $hooks[] = [
            "hook" => $hook,
            "component" => $component,
            "callback" => $callback,
            "priority" => $priority,
            "accepted_args" => $accepted_args,
        ];

        return $hooks;
    }

    /**
     * LISTENER FOR MONTONIO
     */
    public function listen()
    {
        require_once "lib/MontonioPayments/MontonioPaymentsSDK.php";
        require_once "lib/MontonioPayments/MontonioPaymentsSDK.php";

        $give_listener = give_clean(filter_input(INPUT_GET, "give-listener"));

        // Must be a montonio listener to proceed.
        if ("montonio" !== $give_listener) {
            return;
        }

        try {
            /**
             * VALIDATING MONTONIO PAYMENT
             */

            // We send the payment_token query parameter upon successful payment
            // This is both with merchant_notification_url and merchant_return_url
            $accessKey = give_get_option("montonio_access_key");
            $token = isset($_REQUEST["payment_token"])
                ? $_REQUEST["payment_token"]
                : (isset($_REQUEST["order-token"])
                    ? $_REQUEST["order-token"]
                    : "");
            $payment_id = isset($_REQUEST["id"]) ? $_REQUEST["id"] : "";
            $form_id = isset($_REQUEST["give-form-id"])
                ? $_REQUEST["give-form-id"]
                : "";
            $secretKey = give_get_option("montonio_secret_key");

            if (empty($token) || empty($payment_id) || empty($form_id)) {
                throw new Exception(
                    "Missing required parameters: " . json_encode($_REQUEST)
                );
            }

            $decoded = MontonioPaymentsSDK::decodePaymentToken(
                $token,
                $secretKey
            );

            Log::info("Decoded token:", ["decoded" => json_encode($decoded)]);

            if (
                !isset($decoded->accessKey) ||
                !isset($decoded->merchant_reference) ||
                !isset($decoded->payment_status)
            ) {
                throw new Exception(
                    "Invalid token structure: " . json_encode($decoded)
                );
            }

            if (
                $decoded->accessKey === $accessKey &&
                $decoded->merchant_reference === $payment_id &&
                $decoded->payment_status === "PAID"
            ) {
                give_update_payment_status($payment_id, "publish");
                Log::info("Payment successful", ["payment_id" => $payment_id]);
                wp_redirect(give_get_success_page_uri());
            } else {
                give_update_payment_status($payment_id, "abandoned");
                Log::info("Payment abandoned", [
                    "payment_id" => $payment_id,
                    "decoded" => json_encode($decoded),
                    "accessKey_match" => $decoded->accessKey === $accessKey,
                    "merchant_reference_match" =>
                        $decoded->merchant_reference === $payment_id,
                    "payment_status" => $decoded->payment_status,
                ]);
                wp_redirect(give_get_failed_transaction_uri());
            }
        } catch (Exception $exception) {
            Log::warning("Montonio - Webhook Received", [
                "Error" => $exception->getMessage(),
            ]);

            status_header(400);
            exit();
        }

        status_header(200);
    }

    /**
     * Register the filters and actions with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        foreach ($this->filters as $hook) {
            add_filter(
                $hook["hook"],
                [$hook["component"], $hook["callback"]],
                $hook["priority"],
                $hook["accepted_args"]
            );
        }

        foreach ($this->actions as $hook) {
            add_action(
                $hook["hook"],
                [$hook["component"], $hook["callback"]],
                $hook["priority"],
                $hook["accepted_args"]
            );
        }

        // GIVEWP CODE STARTS HERE
        /**
         * Registering Montonio payment method.
         *
         * @param array $gateways List of registered gateways.
         *
         * @return array
         * @since 1.0.0
         *
         */

        function montonio_for_give_register_payment_method($gateways)
        {
            $gateways["montonio"] = [
                "admin_label" => __("Montonio", "montonio-for-give"),
                "checkout_label" => __("Montonio", "montonio-for-give"),
            ];

            return $gateways;
        }

        add_filter(
            "give_payment_gateways",
            "montonio_for_give_register_payment_method"
        );

        /**
         * Registering Donationbox payment method.
         */
        function donationbox_for_give_register_payment_method($gateways)
        {
            $gateways["donationbox"] = [
                "admin_label" => __("Donationbox", "montonio-for-give"),
                "checkout_label" => __("Donationbox", "montonio-for-give"),
            ];

            return $gateways;
        }

        add_filter(
            "give_payment_gateways",
            "donationbox_for_give_register_payment_method"
        );

        /**
         * Montonio Gateway form output
         *
         * @return bool
         **/
        function give_montonio_form_output($form_id)
        {
            printf(
                '
                    <fieldset class="no-fields">
                        <p style="text-align: center;"><b>%1$s</b></p>
                        <p style="text-align: center;">
                            <b>%2$s</b> %3$s
                        </p>
                    </fieldset>
                ',
                esc_html__("Donate with Montonio", "give"),
                esc_html__("How it works:", "give"),
                esc_html__(
                    "You will be redirected to Montonio to complete your donation. It supports payments from all Baltic and Finnish banks",
                    "give"
                )
            );
            return true;
        }

        add_action("give_montonio_cc_form", "give_montonio_form_output");

        /**
         * Donationbox Gateway form output
         */
        function give_donationbox_form_output($form_id)
        {
            printf(
                '
                    <fieldset class="no-fields">
                        <p style="text-align: center;"><b>%1$s</b></p>
                        <p style="text-align: center;">
                            <b>%2$s</b> %3$s
                        </p>
                    </fieldset>
                ',
                esc_html__("Donate with Donationbox", "montonio-for-give"),
                esc_html__("How it works:", "montonio-for-give"),
                esc_html__(
                    "After submitting, a Donationbox payment window will open where you can complete your donation via bank transfer or other available payment methods.",
                    "montonio-for-give"
                )
            );
            return true;
        }

        add_action("give_donationbox_cc_form", "give_donationbox_form_output");

        /**
         * Register Section for Payment Gateway Settings.
         *
         * @param array $sections List of payment gateway sections.
         *
         * @return array
         * @since 1.0.0
         *
         */

        function montonio_for_give_register_payment_gateway_sections($sections)
        {
            // `montonio-settings` is the name/slug of the payment gateway section.
            $sections["montonio-settings"] = __(
                "Montonio",
                "montonio-for-give"
            );

            return $sections;
        }

        add_filter(
            "give_get_sections_gateways",
            "montonio_for_give_register_payment_gateway_sections"
        );

        function donationbox_for_give_register_payment_gateway_sections($sections)
        {
            $sections["donationbox-settings"] = __(
                "Donationbox",
                "montonio-for-give"
            );

            return $sections;
        }

        add_filter(
            "give_get_sections_gateways",
            "donationbox_for_give_register_payment_gateway_sections"
        );

        /**
         * Register Admin Settings.
         *
         * @param array $settings List of admin settings.
         *
         * @return array
         * @since 1.0.0
         *
         */

        function montonio_for_give_register_payment_gateway_setting_fields(
            $settings
        ) {
            switch (give_get_current_setting_section()) {
                case "montonio-settings":
                    $settings = [
                        [
                            "id" => "give_title_montonio",
                            "type" => "title",
                        ],
                    ];

                    // Existing settings
                    $settings[] = [
                        "name" => __("Enable Test Mode", "montonio-for-give"),
                        "desc" => __("", "montonio-for-give"),
                        "id" => "montonio_env",
                        "type" => "checkbox",
                    ];

                    $settings[] = [
                        "name" => __("Access key", "montonio-for-give"),
                        "desc" => __("", "montonio-for-give"),
                        "id" => "montonio_access_key",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __("Secret key", "montonio-for-give"),
                        "desc" => __("", "montonio-for-give"),
                        "id" => "montonio_secret_key",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __(
                            'Bank transfer detail (e.g. "Donation")',
                            "montonio-for-give"
                        ),
                        "desc" => __("", "montonio-for-give"),
                        "id" => "montonio_merchant_name",
                        "type" => "text",
                    ];

                    // New settings for payment description customization
                    $settings[] = [
                        "name" => __(
                            "Payment Description Components",
                            "montonio-for-give"
                        ),
                        "desc" => __(
                            "Select which components to include in the payment description",
                            "montonio-for-give"
                        ),
                        "id" => "montonio_description_header",
                        "type" => "title",
                    ];

                    $settings[] = [
                        "name" => __(
                            "Include Donation ID",
                            "montonio-for-give"
                        ),
                        "desc" => __(
                            "Add donation ID to payment description",
                            "montonio-for-give"
                        ),
                        "id" => "montonio_include_donation_id",
                        "type" => "checkbox",
                        "default" => "on",
                    ];

                    $settings[] = [
                        "name" => __(
                            "Include Campaign Name",
                            "montonio-for-give"
                        ),
                        "desc" => __(
                            "Add form/campaign name to payment description",
                            "montonio-for-give"
                        ),
                        "id" => "montonio_include_campaign_name",
                        "type" => "checkbox",
                        "default" => "off",
                    ];

                    $settings[] = [
                        "name" => __(
                            "Include Personal Code",
                            "montonio-for-give"
                        ),
                        "desc" => __(
                            "Add Estonian personal code to payment description (if provided)",
                            "montonio-for-give"
                        ),
                        "id" => "montonio_include_personal_code",
                        "type" => "checkbox",
                        "default" => "on",
                    ];

                    $settings[] = [
                        "name" => __(
                            "Description Components Separator",
                            "montonio-for-give"
                        ),
                        "desc" => __(
                            'Character(s) to separate description components (e.g., " / " or " - ")',
                            "montonio-for-give"
                        ),
                        "id" => "montonio_description_separator",
                        "type" => "text",
                        "default" => " / ",
                    ];

                    $settings[] = [
                        "id" => "give_title_montonio",
                        "type" => "sectionend",
                    ];

                    break;

                case "donationbox-settings":
                    $settings = [
                        [
                            "id" => "give_title_donationbox",
                            "type" => "title",
                        ],
                    ];

                    $settings[] = [
                        "name" => __("Base URL", "montonio-for-give"),
                        "desc" => __(
                            "Donationbox instance URL (e.g. https://donationbox.ee)",
                            "montonio-for-give"
                        ),
                        "id" => "donationbox_base_url",
                        "type" => "text",
                        "default" => "https://donationbox.ee",
                    ];

                    $settings[] = [
                        "name" => __("Campaign Title", "montonio-for-give"),
                        "desc" => __(
                            "Name of the donation campaign (campaign_title parameter)",
                            "montonio-for-give"
                        ),
                        "id" => "donationbox_campaign_title",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __("Payment Detail", "montonio-for-give"),
                        "desc" => __(
                            "Bank transfer description (detail parameter)",
                            "montonio-for-give"
                        ),
                        "id" => "donationbox_detail",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __("Payee Name", "montonio-for-give"),
                        "desc" => __(
                            "Recipient organization name (payee parameter)",
                            "montonio-for-give"
                        ),
                        "id" => "donationbox_payee",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __("IBAN", "montonio-for-give"),
                        "desc" => __(
                            "Bank account number for receiving donations (iban parameter)",
                            "montonio-for-give"
                        ),
                        "id" => "donationbox_iban",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __("Country", "montonio-for-give"),
                        "desc" => __(
                            "Determines available bank payment methods",
                            "montonio-for-give"
                        ),
                        "id" => "donationbox_country",
                        "type" => "select",
                        "options" => [
                            "EE" => __("Estonia", "montonio-for-give"),
                            "LV" => __("Latvia", "montonio-for-give"),
                            "LT" => __("Lithuania", "montonio-for-give"),
                        ],
                        "default" => "EE",
                    ];

                    $settings[] = [
                        "name" => __("Language", "montonio-for-give"),
                        "desc" => __(
                            "Display language for Donationbox (locale parameter)",
                            "montonio-for-give"
                        ),
                        "id" => "donationbox_locale",
                        "type" => "select",
                        "options" => [
                            "en" => __("English", "montonio-for-give"),
                            "et" => __("Estonian", "montonio-for-give"),
                            "ru" => __("Russian", "montonio-for-give"),
                            "lv" => __("Latvian", "montonio-for-give"),
                            "lt" => __("Lithuanian", "montonio-for-give"),
                        ],
                        "default" => "en",
                    ];

                    $settings[] = [
                        "name" => __(
                            "Payment Providers",
                            "montonio-for-give"
                        ),
                        "desc" => __(
                            "Configure optional payment provider identifiers. Leave empty to disable.",
                            "montonio-for-give"
                        ),
                        "id" => "donationbox_providers_header",
                        "type" => "title",
                    ];

                    $settings[] = [
                        "name" => __("PayPal.me ID", "montonio-for-give"),
                        "desc" => __(
                            "PayPal.me username (pp parameter)",
                            "montonio-for-give"
                        ),
                        "id" => "donationbox_pp",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __("SEB UID", "montonio-for-give"),
                        "desc" => __(
                            "SEB bank payment UID (sebuid parameter)",
                            "montonio-for-give"
                        ),
                        "id" => "donationbox_sebuid",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __("Revolut ID", "montonio-for-give"),
                        "desc" => __(
                            "Revolut.me username (rev parameter)",
                            "montonio-for-give"
                        ),
                        "id" => "donationbox_rev",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __("Stripe Link ID", "montonio-for-give"),
                        "desc" => __(
                            "Stripe payment link identifier (strp parameter)",
                            "montonio-for-give"
                        ),
                        "id" => "donationbox_strp",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __(
                            "Enable Tax Deduction",
                            "montonio-for-give"
                        ),
                        "desc" => __(
                            "Show tax deduction option in Donationbox (tax parameter)",
                            "montonio-for-give"
                        ),
                        "id" => "donationbox_tax",
                        "type" => "checkbox",
                        "default" => "off",
                    ];

                    $settings[] = [
                        "name" => __(
                            "Additional URL Parameters",
                            "montonio-for-give"
                        ),
                        "desc" => __(
                            'Extra URL parameters appended as-is (e.g. "pphb=XXXX&db=my-campaign&local_only=1")',
                            "montonio-for-give"
                        ),
                        "id" => "donationbox_extra_params",
                        "type" => "textarea",
                    ];

                    $settings[] = [
                        "id" => "give_title_donationbox",
                        "type" => "sectionend",
                    ];

                    break;
            }
            return $settings;
        }

        add_filter(
            "give_get_settings_gateways",
            "montonio_for_give_register_payment_gateway_setting_fields"
        );

        /**
         * Process Square checkout submission.
         *
         * @param array $posted_data List of posted data.
         *
         * @return void
         * @since  1.0.0
         * @access public
         *
         */

        function montonio_for_give_process_montonio_donation($posted_data)
        {
            // Make sure we don't have any left over errors present.
            give_clear_errors();

            // Any errors?
            $errors = give_get_errors();

            // No errors, proceed.
            if (!$errors) {
                $form_id = intval($posted_data["post_data"]["give-form-id"]);
                $price_id = !empty($posted_data["post_data"]["give-price-id"])
                    ? $posted_data["post_data"]["give-price-id"]
                    : 0;
                $donation_amount = !empty($posted_data["price"])
                    ? $posted_data["price"]
                    : 0;
                $purchase_key = $posted_data["purchase_key"];

                // Setup the payment details.
                $donation_data = [
                    "price" => $donation_amount,
                    "give_form_title" =>
                        $posted_data["post_data"]["give-form-title"],
                    "give_form_id" => $form_id,
                    "give_price_id" => $price_id,
                    "date" => $posted_data["date"],
                    "user_email" => $posted_data["user_email"],
                    "purchase_key" => $posted_data["purchase_key"],
                    "currency" => give_get_currency($form_id),
                    "user_info" => $posted_data["user_info"],
                    "status" => "pending",
                    "gateway" => "montonio",
                ];

                // Record the pending donation.
                $payment_id = give_insert_payment($donation_data);

                // Assign required data to array of donation data for future reference.
                $donation_data["purchase_key"] = $payment_id;
                $checkout_email = $donation_data["user_email"];
                $checkout_first_name =
                    $donation_data["user_info"]["first_name"];
                $checkout_last_name = $donation_data["user_info"]["last_name"];

                if (!$payment_id) {
                    // Record Gateway Error as Pending Donation in Give is not created.
                    give_record_gateway_error(
                        __("montonio Error", "montonio-for-give"),
                        sprintf(
                            /* translators: %s Exception error message. */
                            __(
                                "Unable to create a pending donation with Give.",
                                "montonio-for-give"
                            )
                        )
                    );

                    // Send user back to checkout.
                    give_send_back_to_checkout("?payment-mode=montonio");
                    return;
                }

                // Do the actual payment processing using the custom payment gateway API. To access the GiveWP settings, use give_get_option()
                // as a reference, this pulls the API key entered above: give_get_option('montonio_for_give_montonio_api_key')

                /**
                 * MONTONIO STARTS HERE
                 */
                require_once "lib/MontonioPayments/MontonioPaymentsSDK.php";
                require_once "lib/MontonioPayments/MontonioPaymentsSDK.php";

                // Get values from Montonio payment method settings page
                $accessKey = give_get_option("montonio_access_key");
                $secretKey = give_get_option("montonio_secret_key");
                $merchantName = give_get_option("montonio_merchant_name");

                $env = null;
                switch (give_get_option("montonio_env")) {
                    case true:
                        $env = "sandbox";
                        break;
                    case false:
                        $env = "production";
                        break;
                }

                $sdk = new MontonioPaymentsSDK($accessKey, $secretKey, $env);

                $merchant_notification_url =
                    get_site_url() .
                    "/?give-listener=montonio&id=" .
                    $payment_id .
                    "&give-form-id=" .
                    $form_id .
                    "&payment-mode=montonio";
                $merchant_return_url =
                    get_site_url() .
                    "/?give-listener=montonio&id=" .
                    $payment_id .
                    "&give-form-id=" .
                    $form_id .
                    "&payment-mode=montonio";

                // Checking current WP website locale (Polylang plugin only) to set the language for Montonio payment page
                if (function_exists("pll_the_languages")) {
                    switch (pll_current_language()) {
                        case "et":
                            $current_locale = "et";
                            break;
                        case "lv":
                            $current_locale = "lv";
                            break;
                        case "lt":
                            $current_locale = "lt";
                            break;
                        case "pl_PL":
                            $current_locale = "pl";
                            break;
                        case "fi":
                            $current_locale = "fi";
                            break;
                        case "ru_RU" or "ru_UA":
                            $current_locale = "ru";
                            break;
                        default:
                            $current_locale = "en_US";
                            break;
                    }
                } else {
                    $current_locale = "en_US";
                }

                // Get form/campaign name if needed
                $campaign_name = "";
                if (
                    give_get_option("montonio_include_campaign_name") === "on"
                ) {
                    $campaign_name = get_the_title($form_id);
                }

                // Get personal code if enabled and provided
                $personal_code = "";
                if (
                    give_get_option("montonio_include_personal_code") ===
                        "on" &&
                    isset($_POST["give_estonia_personal_code"]) &&
                    !empty($_POST["give_estonia_personal_code"])
                ) {
                    $personal_code = sanitize_text_field(
                        $_POST["give_estonia_personal_code"]
                    );
                    give_update_payment_meta(
                        $payment_id,
                        "give_estonia_personal_code",
                        $personal_code
                    );
                }

                $paymentData = [
                    "amount" => $donation_amount,
                    "currency" => "EUR",
                    "merchant_reference" => $payment_id,
                    "merchant_name" => give_get_option(
                        "montonio_merchant_name"
                    ),
                    "merchant_notification_url" => $merchant_notification_url,
                    "merchant_return_url" => $merchant_return_url,
                    "checkout_email" => $checkout_email,
                    "checkout_first_name" => $checkout_first_name,
                    "checkout_last_name" => $checkout_last_name,
                    "donation_id" => $payment_id,
                    "campaign_name" => $campaign_name,
                    "personal_code" => $personal_code,
                    "description_settings" => [
                        "include_donation_id" => give_get_option(
                            "montonio_include_donation_id",
                            "on"
                        ),
                        "include_campaign_name" => give_get_option(
                            "montonio_include_campaign_name",
                            "off"
                        ),
                        "include_personal_code" => give_get_option(
                            "montonio_include_personal_code",
                            "on"
                        ),
                        "separator" => give_get_option(
                            "montonio_description_separator",
                            " / "
                        ),
                    ],
                ];

                $sdk->setPaymentData($paymentData);
                $paymentUrl = $sdk->getPaymentUrl();

                // The payment URL customer should be redirected to
                echo $paymentUrl;
                wp_redirect($paymentUrl);

                /**
                 * MONTONIO ENDS HERE
                 */
            } else {
                // Send user back to checkout.
                give_send_back_to_checkout("?payment-mode=montonio");
            } // End if().
        }

        add_action(
            "give_gateway_montonio",
            "montonio_for_give_process_montonio_donation"
        );

        /**
         * Process Donationbox donation submission.
         *
         * Creates a pending donation and redirects to the success page
         * with the Donationbox embed URL to open in a lightbox.
         * Payments remain pending for manual admin review.
         */
        function donationbox_for_give_process_donation($posted_data)
        {
            // Make sure we don't have any left over errors present.
            give_clear_errors();

            // Any errors?
            $errors = give_get_errors();

            // No errors, proceed.
            if (!$errors) {
                $form_id = intval($posted_data["post_data"]["give-form-id"]);
                $price_id = !empty($posted_data["post_data"]["give-price-id"])
                    ? $posted_data["post_data"]["give-price-id"]
                    : 0;
                $donation_amount = !empty($posted_data["price"])
                    ? $posted_data["price"]
                    : 0;

                // Setup the payment details.
                $donation_data = [
                    "price" => $donation_amount,
                    "give_form_title" =>
                        $posted_data["post_data"]["give-form-title"],
                    "give_form_id" => $form_id,
                    "give_price_id" => $price_id,
                    "date" => $posted_data["date"],
                    "user_email" => $posted_data["user_email"],
                    "purchase_key" => $posted_data["purchase_key"],
                    "currency" => give_get_currency($form_id),
                    "user_info" => $posted_data["user_info"],
                    "status" => "pending",
                    "gateway" => "donationbox",
                ];

                // Record the pending donation.
                $payment_id = give_insert_payment($donation_data);

                if (!$payment_id) {
                    give_record_gateway_error(
                        __("Donationbox Error", "montonio-for-give"),
                        __(
                            "Unable to create a pending donation with Give.",
                            "montonio-for-give"
                        )
                    );
                    give_send_back_to_checkout("?payment-mode=donationbox");
                    return;
                }

                // Build the Donationbox embed URL
                $base_url = give_get_option(
                    "donationbox_base_url",
                    "https://donationbox.ee"
                );
                $base_url = rtrim($base_url, "/");

                $params = [];

                // Required params
                $campaign_title = give_get_option("donationbox_campaign_title");
                if (!empty($campaign_title)) {
                    $params["campaign_title"] = $campaign_title;
                }

                $detail = give_get_option("donationbox_detail");
                if (!empty($detail)) {
                    $params["detail"] = $detail;
                }

                $payee = give_get_option("donationbox_payee");
                if (!empty($payee)) {
                    $params["payee"] = $payee;
                }

                $iban = give_get_option("donationbox_iban");
                if (!empty($iban)) {
                    $params["iban"] = $iban;
                }

                // Prepend the donation amount as fixed amount
                $params["s0"] = $donation_amount;

                // Country
                $country = give_get_option("donationbox_country", "EE");
                if (!empty($country)) {
                    $params["country"] = $country;
                }

                // Locale
                $locale = give_get_option("donationbox_locale", "en");
                if (!empty($locale)) {
                    $params["locale"] = $locale;
                }

                // Optional payment providers
                $pp = give_get_option("donationbox_pp");
                if (!empty($pp)) {
                    $params["pp"] = $pp;
                }

                $sebuid = give_get_option("donationbox_sebuid");
                if (!empty($sebuid)) {
                    $params["sebuid"] = $sebuid;
                }

                $rev = give_get_option("donationbox_rev");
                if (!empty($rev)) {
                    $params["rev"] = $rev;
                }

                $strp = give_get_option("donationbox_strp");
                if (!empty($strp)) {
                    $params["strp"] = $strp;
                }

                // Tax deduction
                if (give_get_option("donationbox_tax") === "on") {
                    $params["tax"] = "1";
                }

                // Build the embed URL
                $embed_url =
                    $base_url . "/embed?" . http_build_query($params);

                // Append extra params if set
                $extra_params = give_get_option("donationbox_extra_params");
                if (!empty($extra_params)) {
                    $embed_url .= "&" . $extra_params;
                }

                // Store URL in payment meta for reference
                give_update_payment_meta(
                    $payment_id,
                    "donationbox_url",
                    $embed_url
                );

                // Add payment note
                give_insert_payment_note(
                    $payment_id,
                    __(
                        "Donationbox payment initiated. Pending manual verification.",
                        "montonio-for-give"
                    )
                );

                // Redirect to success page with the donationbox URL to trigger lightbox
                $redirect_url =
                    give_get_success_page_uri() .
                    "?donationbox_url=" .
                    rawurlencode($embed_url) .
                    "&payment_id=" .
                    $payment_id;

                wp_redirect($redirect_url);
                exit();
            } else {
                give_send_back_to_checkout("?payment-mode=donationbox");
            }
        }

        add_action(
            "give_gateway_donationbox",
            "donationbox_for_give_process_donation"
        );
    }
}
