<?php
// paytm.php

// Paytm payment integration script

// Include database connection
require_once '../db.php';

// Paytm configuration
$paytmMerchantKey = 'YOUR_MERCHANT_KEY';
$paytmMerchantId = 'YOUR_MERCHANT_ID';
$paytmWebsite = 'YOUR_WEBSITE';
$paytmIndustryType = 'YOUR_INDUSTRY_TYPE';
$paytmChannelId = 'YOUR_CHANNEL_ID';

// Function to initiate Paytm payment
function initiatePaytmPayment($orderId, $amount, $callbackUrl) {
    global $paytmMerchantKey, $paytmMerchantId, $paytmWebsite, $paytmIndustryType, $paytmChannelId;

    $paytmParams = array(
        "MID" => $paytmMerchantId,
        "ORDER_ID" => $orderId,
        "CUST_ID" => $_SESSION['user_id'],
        "CHANNEL_ID" => $paytmChannelId,
        "TXN_AMOUNT" => $amount,
        "WEBSITE" => $paytmWebsite,
        "INDUSTRY_TYPE" => $paytmIndustryType,
        "CALLBACK_URL" => $callbackUrl,
    );

    // Generate checksum
    $paytmChecksum = getChecksumFromArray($paytmParams, $paytmMerchantKey);
    $paytmParams["CHECKSUMHASH"] = $paytmChecksum;

    // Prepare the request for Paytm
    $paytmUrl = "https://secure.paytm.in/order/process";
    $formHtml = '<form method="post" action="' . $paytmUrl . '" name="paytmForm">';
    foreach ($paytmParams as $key => $value) {
        $formHtml .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
    }
    $formHtml .= '<button type="submit">Pay with Paytm</button>';
    $formHtml .= '</form>';

    return $formHtml;
}

// Function to verify Paytm payment response
function verifyPaytmResponse($response) {
    global $paytmMerchantKey;

    $paytmChecksum = $response['CHECKSUMHASH'];
    unset($response['CHECKSUMHASH']);
    $isValidChecksum = verifyChecksum($response, $paytmChecksum, $paytmMerchantKey);

    return $isValidChecksum;
}

// Include checksum functions
require_once 'paytm_checksum.php';
?>