<?php

/**
 * @link       http://fleisher.ee
 * @since      1.0.0
 *
 * @package    donationbox_Givewp
 * @subpackage donationbox_Givewp/includes
 */

/**
 * @package    donationbox_Givewp
 * @subpackage donationbox_Givewp/includes
 * @author     Pavel Fleisher <pavel@fleisher.ee>
 */
class donationbox_Givewp_Loader
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
         * Registering Donationbox payment method.
         *
         * @param array $gateways List of registered gateways.
         *
         * @return array
         * @since 1.0.0
         */
        function donationbox_for_give_register_payment_method($gateways)
        {
            $gateways["donationbox"] = [
                "admin_label" => __("Donationbox", "donationbox-for-give"),
                "checkout_label" => __("Donationbox", "donationbox-for-give"),
            ];

            return $gateways;
        }

        add_filter(
            "give_payment_gateways",
            "donationbox_for_give_register_payment_method"
        );

        /**
         * Donationbox Gateway form output
         *
         * @return bool
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
                esc_html__("Donate with Donationbox", "donationbox-for-give"),
                esc_html__("How it works:", "donationbox-for-give"),
                esc_html__(
                    "After submitting, a Donationbox payment window will open where you can complete your donation via bank transfer or other available payment methods.",
                    "donationbox-for-give"
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
         */
        function donationbox_for_give_register_payment_gateway_sections($sections)
        {
            $sections["donationbox-settings"] = __(
                "Donationbox",
                "donationbox-for-give"
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
         */
        function donationbox_for_give_register_payment_gateway_setting_fields(
            $settings
        ) {
            switch (give_get_current_setting_section()) {
                case "donationbox-settings":
                    $settings = [
                        [
                            "id" => "give_title_donationbox",
                            "type" => "title",
                        ],
                    ];

                    $settings[] = [
                        "name" => __("Base URL", "donationbox-for-give"),
                        "desc" => __(
                            "Donationbox instance URL (e.g. https://donationbox.ee)",
                            "donationbox-for-give"
                        ),
                        "id" => "donationbox_base_url",
                        "type" => "text",
                        "default" => "https://donationbox.ee",
                    ];

                    $settings[] = [
                        "name" => __("Campaign Title", "donationbox-for-give"),
                        "desc" => __(
                            "Name of the donation campaign (campaign_title parameter)",
                            "donationbox-for-give"
                        ),
                        "id" => "donationbox_campaign_title",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __("Payment Detail", "donationbox-for-give"),
                        "desc" => __(
                            "Bank transfer description (detail parameter)",
                            "donationbox-for-give"
                        ),
                        "id" => "donationbox_detail",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __("Payee Name", "donationbox-for-give"),
                        "desc" => __(
                            "Recipient organization name (payee parameter)",
                            "donationbox-for-give"
                        ),
                        "id" => "donationbox_payee",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __("IBAN", "donationbox-for-give"),
                        "desc" => __(
                            "Bank account number for receiving donations (iban parameter)",
                            "donationbox-for-give"
                        ),
                        "id" => "donationbox_iban",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __("Country", "donationbox-for-give"),
                        "desc" => __(
                            "Determines available bank payment methods",
                            "donationbox-for-give"
                        ),
                        "id" => "donationbox_country",
                        "type" => "select",
                        "options" => [
                            "EE" => __("Estonia", "donationbox-for-give"),
                            "LV" => __("Latvia", "donationbox-for-give"),
                            "LT" => __("Lithuania", "donationbox-for-give"),
                        ],
                        "default" => "EE",
                    ];

                    $settings[] = [
                        "name" => __("Language", "donationbox-for-give"),
                        "desc" => __(
                            "Display language for Donationbox (locale parameter)",
                            "donationbox-for-give"
                        ),
                        "id" => "donationbox_locale",
                        "type" => "select",
                        "options" => [
                            "en" => __("English", "donationbox-for-give"),
                            "et" => __("Estonian", "donationbox-for-give"),
                            "ru" => __("Russian", "donationbox-for-give"),
                            "lv" => __("Latvian", "donationbox-for-give"),
                            "lt" => __("Lithuanian", "donationbox-for-give"),
                        ],
                        "default" => "en",
                    ];

                    $settings[] = [
                        "name" => __(
                            "Payment Providers",
                            "donationbox-for-give"
                        ),
                        "desc" => __(
                            "Configure optional payment provider identifiers. Leave empty to disable.",
                            "donationbox-for-give"
                        ),
                        "id" => "donationbox_providers_header",
                        "type" => "title",
                    ];

                    $settings[] = [
                        "name" => __("PayPal.me ID", "donationbox-for-give"),
                        "desc" => __(
                            "PayPal.me username (pp parameter)",
                            "donationbox-for-give"
                        ),
                        "id" => "donationbox_pp",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __("SEB UID", "donationbox-for-give"),
                        "desc" => __(
                            "SEB bank payment UID (sebuid parameter)",
                            "donationbox-for-give"
                        ),
                        "id" => "donationbox_sebuid",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __("Revolut ID", "donationbox-for-give"),
                        "desc" => __(
                            "Revolut.me username (rev parameter)",
                            "donationbox-for-give"
                        ),
                        "id" => "donationbox_rev",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __("Stripe Link ID", "donationbox-for-give"),
                        "desc" => __(
                            "Stripe payment link identifier (strp parameter)",
                            "donationbox-for-give"
                        ),
                        "id" => "donationbox_strp",
                        "type" => "text",
                    ];

                    $settings[] = [
                        "name" => __(
                            "Enable Tax Deduction",
                            "donationbox-for-give"
                        ),
                        "desc" => __(
                            "Show tax deduction option in Donationbox (tax parameter)",
                            "donationbox-for-give"
                        ),
                        "id" => "donationbox_tax",
                        "type" => "checkbox",
                        "default" => "off",
                    ];

                    $settings[] = [
                        "name" => __(
                            "Additional URL Parameters",
                            "donationbox-for-give"
                        ),
                        "desc" => __(
                            'Extra URL parameters appended as-is (e.g. "pphb=XXXX&db=my-campaign&local_only=1")',
                            "donationbox-for-give"
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
            "donationbox_for_give_register_payment_gateway_setting_fields"
        );

        /**
         * Process Donationbox donation submission.
         *
         * Creates a pending donation and redirects to the success page
         * with the Donationbox embed URL to open in a lightbox.
         * Payments remain pending for manual admin review.
         *
         * @param array $posted_data List of posted data.
         *
         * @return void
         * @since  1.0.0
         * @access public
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
                        __("Donationbox Error", "donationbox-for-give"),
                        __(
                            "Unable to create a pending donation with Give.",
                            "donationbox-for-give"
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
                        "donationbox-for-give"
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
