<?php

/**
 * We use php-jwt for JWT creation
 */
require_once "jwt/JWT.php";

/**
 * SDK for Montonio Payments.
 * This class contains methods for starting and validating payments.
 */
class MontonioPaymentsSDK
{
    /**
     * Payment Data for Montonio Payment Token generation
     * @see https://payments-docs.montonio.com/#generating-the-payment-token
     *
     * @var array
     */
    protected $_paymentData;

    /**
     * Montonio Access Key
     *
     * @var string
     */
    protected $_accessKey;

    /**
     * Montonio Secret Key
     *
     * @var string
     */
    protected $_secretKey;

    /**
     * Montonio Environment (Use sandbox for testing purposes)
     *
     * @var string 'production' or 'sandbox'
     */
    protected $_environment;

    /**
     * Root URL for the Montonio Payments Sandbox API
     */
    const MONTONIO_PAYMENTS_SANDBOX_API_URL = "https://sandbox-stargate.montonio.com";

    /**
     * Root URL for the Montonio Payments API
     */
    const MONTONIO_PAYMENTS_API_URL = "https://stargate.montonio.com";

    public function __construct($accessKey, $secretKey, $environment)
    {
        $this->_accessKey = $accessKey;
        $this->_secretKey = $secretKey;
        $this->_environment = $environment;
    }

    /**
     * Get the URL string where to redirect the customer to
     *
     * @return string
     */
    public function getPaymentUrl()
    {
        $token = $this->_generatePaymentToken();
        $apiUrl =
            $this->_environment === "sandbox"
                ? self::MONTONIO_PAYMENTS_SANDBOX_API_URL
                : self::MONTONIO_PAYMENTS_API_URL;

        $response = $this->_sendTokenToApi($apiUrl . "/api/orders", $token);

        if (isset($response["paymentUrl"])) {
            return $response["paymentUrl"];
        } else {
            throw new Exception("Failed to get payment URL from Montonio API");
        }
    }

    /**
     * Send the JWT token to Montonio API
     *
     * @param string $url
     * @param string $token
     * @return array
     */
    protected function _sendTokenToApi($url, $token)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["data" => $token]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
        ]);

        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status >= 400) {
            throw new Exception(
                "API request failed with status " . $status . ": " . $result
            );
        }

        return json_decode($result, true);
    }

    /**
     * Generate JWT from Payment Data
     *
     * @return string
     */
    protected function _generatePaymentToken()
    {
        $paymentData = [
            "accessKey" => $this->_accessKey,
            "merchantReference" => substr(
                (string) $this->_paymentData["merchant_reference"],
                0,
                255
            ),
            "returnUrl" => $this->_paymentData["merchant_return_url"],
            "notificationUrl" =>
                $this->_paymentData["merchant_notification_url"] ?? "",
            "currency" => $this->_paymentData["currency"],
            "grandTotal" => (float) $this->_paymentData["amount"],
            "locale" => "en",
            "billingAddress" => [
                "firstName" => $this->_paymentData["checkout_first_name"],
                "lastName" => $this->_paymentData["checkout_last_name"],
                "email" => $this->_paymentData["checkout_email"],
                "addressLine1" => $this->_paymentData["checkout_address"] ?? "",
                "locality" => $this->_paymentData["checkout_city"] ?? "",
                "country" => $this->_paymentData["checkout_country"] ?? "",
                "postalCode" => $this->_paymentData["checkout_postcode"] ?? "",
            ],
            "lineItems" => [
                [
                    "name" => "Donation",
                    "quantity" => 1,
                    "finalPrice" => (float) $this->_paymentData["amount"],
                ],
            ],
            "payment" => [
                "method" => "paymentInitiation",
                "methodDisplay" => "Pay with your bank",
                "amount" => (float) $this->_paymentData["amount"],
                "currency" => $this->_paymentData["currency"],
                "methodOptions" => [
                    "paymentDescription" => $this->_generatePaymentDescription(),
                ],
            ],
            "exp" => time() + 10 * 60, // Token expires after 10 minutes
        ];

        if (isset($this->_paymentData["preselected_aspsp"])) {
            $paymentData["payment"]["methodOptions"]["preferredProvider"] =
                (string) $this->_paymentData["preselected_aspsp"];
        }

        if (isset($this->_paymentData["preselected_country"])) {
            $paymentData["payment"]["methodOptions"]["preferredCountry"] =
                (string) $this->_paymentData["preselected_country"];
        }

        foreach ($paymentData as $key => $value) {
            if (empty($value)) {
                unset($paymentData[$key]);
            }
        }

        return Firebase\JWT\JWT::encode($paymentData, $this->_secretKey);
    }

    /**
     * Set payment data
     *
     * @param array $paymentData
     * @return MontonioPaymentsSDK
     */
    public function setPaymentData($paymentData)
    {
        $this->_paymentData = $paymentData;
        return $this;
    }

    /**
     * Decode the Payment Token
     * This is used to validate the integrity of a callback when a payment was made via Montonio
     * @see https://payments-docs.montonio.com/#validating-the-returned-payment-token
     *
     * @param string $token - The Payment Token
     * @param string Your Secret Key for the environment
     * @return object The decoded Payment token
     */
    public static function decodePaymentToken($token, $secretKey)
    {
        Firebase\JWT\JWT::$leeway = 60 * 5; // 5 minutes
        return Firebase\JWT\JWT::decode($token, $secretKey, ["HS256"]);
    }

    /**
     * Get the Bearer auth token for requests to Montonio
     *
     * @param string $accessKey - Your Access Key
     * @param string $secretKey - Your Secret Key
     * @return string
     */
    static function getBearerToken($accessKey, $secretKey)
    {
        $data = [
            "access_key" => $accessKey,
        ];

        return Firebase\JWT\JWT::encode($data, $secretKey);
    }

    /**
     * Function for making API calls with file_get_contents
     *
     * @param string URL
     * @param array Context Options
     * @return array Array containing status and json_decoded response
     */
    protected function _apiRequest($url, $options)
    {
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            return [
                "status" => "ERROR",
                "data" => $result,
            ];
        } else {
            return [
                "status" => "SUCCESS",
                "data" => json_decode($result),
            ];
        }
    }

    /**
     * Fetch info about banks and card processors that
     * can be shown to the customer at checkout.
     *
     * Banks have different identifiers for separate regions,
     * but the identifier for card payments is uppercase CARD
     * in all regions.
     * @see MontonioPaymentsCheckout::$bankList
     *
     * @return array Array containing the status of the request and the banklist
     */
    public function fetchBankList()
    {
        $url =
            $this->_environment === "sandbox"
                ? "https://api.sandbox-payments.montonio.com/pis/v2/merchants/aspsps"
                : "https://api.payments.montonio.com/pis/v2/merchants/aspsps";

        $options = [
            "http" => [
                "header" =>
                    "Content-Type: application/json\r\n" .
                    "Authorization: Bearer " .
                    MontonioPaymentsSDK::getBearerToken(
                        $this->_accessKey,
                        $this->_secretKey
                    ) .
                    "\r\n",
                "method" => "GET",
            ],
        ];
        return $this->_apiRequest($url, $options);
    }

    /**
     * Validate Payment Token
     *
     * @param string $token
     * @return array
     */
    public function validatePaymentToken($token)
    {
        try {
            $decoded = JWT::decode(
                $token,
                new \Firebase\JWT\Key($this->_secretKey, "HS256")
            );
            $data = (array) $decoded;

            if (
                $data["paymentStatus"] === "PAID" &&
                $data["accessKey"] === $this->_accessKey
            ) {
                return $data;
            } else {
                throw new Exception("Invalid payment status or access key");
            }
        } catch (Exception $e) {
            throw new Exception("Invalid token: " . $e->getMessage());
        }
    }

    protected function _generatePaymentDescription()
    {
        $description_parts = [];

        // Add base description (merchant name)
        $description_parts[] = $this->_paymentData["merchant_name"];

        // Add campaign name if enabled and available
        if (
            !empty($this->_paymentData["campaign_name"]) &&
            $this->_paymentData["description_settings"][
                "include_campaign_name"
            ] === "on"
        ) {
            $description_parts[] = $this->_paymentData["campaign_name"];
        }

        // Add donation ID if enabled
        if (
            $this->_paymentData["description_settings"][
                "include_donation_id"
            ] === "on"
        ) {
            $description_parts[] = "#" . $this->_paymentData["donation_id"];
        }

        // Add personal code if enabled and available
        if (
            !empty($this->_paymentData["personal_code"]) &&
            $this->_paymentData["description_settings"][
                "include_personal_code"
            ] === "on"
        ) {
            $description_parts[] = $this->_paymentData["personal_code"];
        }

        // Get separator from settings or use default
        $separator = !empty(
            $this->_paymentData["description_settings"]["separator"]
        )
            ? $this->_paymentData["description_settings"]["separator"]
            : " / ";

        return implode($separator, $description_parts);
    }
}
