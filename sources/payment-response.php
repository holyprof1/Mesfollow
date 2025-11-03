<?php
require_once 'includes/inc.php';
require_once 'includes/payment/vendor/autoload.php';
if (!defined('INORA_METHODS_CONFIG')) {
	define('INORA_METHODS_CONFIG', realpath('includes/payment/paymentConfig.php'));
}
$payment_time = time();
use App\Components\Payment\BitPayResponse;
use App\Components\Payment\IyzicoResponse;
use App\Components\Payment\PaypalIpnResponse;
use App\Components\Payment\PaytmResponse;
use App\Components\Payment\StripeResponse;
use App\Components\Payment\MercadopagoResponse;

// Get Config Data
$configData = configItem();
// Get Request Data when payment success or failed
$requestData = $_REQUEST;

// Check payment Method is paytm
if ($requestData['paymentOption'] == 'paytm') {
	// Get Payment Response instance
	$paytmResponse = new PaytmResponse();

	// Fetch payment data using payment response instance
	$paytmData = $paytmResponse->getPaytmPaymentData($requestData);

	// Check if payment status is success
	if ($paytmData['STATUS'] == 'TXN_SUCCESS') {

		// Create payment success response data.
		$paymentResponseData = [
			'status' => true,
			'rawData' => $paytmData,
			'data' => preparePaymentData($paytmData['ORDERID'], $paytmData['TXNAMOUNT'], $paytmData['TXNID'], 'paytm'),
		];
		// Send data to payment response.
		paymentResponse($paymentResponseData);
	} else {
		// Create payment failed response data.
		$paymentResponseData = [
			'status' => false,
			'rawData' => $paytmData,
			'data' => preparePaymentData($paytmData['ORDERID'], $paytmData['TXNAMOUNT'], $paytmData['TXNID'], 'paytm'),
		];
		// Send data to payment response function
		paymentResponse($paymentResponseData);
	}
// Check payment method is instamojo
} else if ($requestData['paymentOption'] == 'iyzico') {

	// Check if payment status is success for iyzico.
	if ($_REQUEST['status'] == 'success') {
		// Get iyzico response.
		$iyzicoResponse = new IyzicoResponse();

		// fetch payment data using iyzico response instance.
		$iyzicoData = $iyzicoResponse->getIyzicoPaymentData($requestData);
		$rawResult = json_decode($iyzicoData->getRawResult(), true);

		// Check if iyzico payment data is success
		// Then create a array for success data
		if ($iyzicoData->getStatus() == 'success') {
			$paymentResponseData = [
				'status' => true,
				'rawData' => (array) $iyzicoData,
				'data' => preparePaymentData($requestData['orderId'], $rawResult['price'], $rawResult['conversationId'], 'iyzico'),
			];
			$getPamentData = mysqli_query($db, "SELECT * FROM i_user_payments WHERE order_key='" . $requestData['orderId'] . "'") or die(mysqli_error($db));
			$pData = mysqli_fetch_array($getPamentData, MYSQLI_ASSOC);
			$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
			$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
			$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
			if(!empty($userPayedPlanID)){
				$planDetails = mysqli_query($db, "SELECT * FROM i_premium_plans WHERE plan_id = '$userPayedPlanID'") or die(mysqli_error($db));
				$pAData = mysqli_fetch_array($planDetails, MYSQLI_ASSOC);
				$planAmount = $pAData['plan_amount'];
				mysqli_query($db, "UPDATE i_users SET wallet_points = wallet_points + $planAmount WHERE iuid = '$payerUserID'") or die(mysqli_error($db));
				mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'pending' WHERE order_key='" . $requestData['orderId'] . "'") or die(mysqli_error($db));
            }else if(!empty($productID)){
				$productDetailsFromID = mysqli_query($db, "SELECT * FROM i_user_product_posts WHERE pr_id = '$productID'") or die(mysqli_error($db));
				$productData = mysqli_fetch_array($productDetailsFromID, MYSQLI_ASSOC);
				$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
				$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
				$adminEarning = ($adminFee * $productPrice) / 100;
				$userEarning = $productPrice - $adminEarning;
				mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'pending' , payed_iuid_fk = '$productOwnerID', amount = '$productPrice', fee = '$adminFee', admin_earning = '$adminEarning', user_earning = '$userEarning' WHERE payer_iuid_fk = '$payerUserID' AND order_key = '" . $requestData['orderId'] . "'") or die(mysqli_error($db));
				mysqli_query($db, "UPDATE i_users SET wallet_money = wallet_money + '$userEarning' WHERE iuid = '$productOwnerID'") or die(mysqli_error($db));
			}
			// Send data to payment response
			paymentResponse($paymentResponseData);
			// If payment failed then create data for failed
		} else {
			mysqli_query($db, "DELETE FROM i_user_payments WHERE order_key='" . $requestData['orderId'] . "'") or die(mysqli_error($db));
			// Prepare failed payment data
			$paymentResponseData = [
				'status' => false,
				'rawData' => (array) $iyzicoData,
				'data' => preparePaymentData($requestData['orderId'], $rawResult['price'], $rawResult['conversationId'], 'iyzico'),
			];
			// Send data to payment response
			paymentResponse($paymentResponseData);
		}
		// Check before 3d payment process payment failed
	} else {
		// Prepare failed payment data
		$paymentResponseData = [
			'status' => false,
			'rawData' => $requestData,
			'data' => preparePaymentData($requestData['orderId'], $rawResult['price'], null, 'iyzico'),
		];
		// Send data to process response
		paymentResponse($paymentResponseData);
	}

// Check Paypal payment process
} else if ($requestData['paymentOption'] == 'paypal') {
	// Get instance of paypal
	$paypalIpnResponse = new PaypalIpnResponse();

	// fetch paypal payment data
	$paypalIpnData = $paypalIpnResponse->getPaypalPaymentData();
	$rawData = json_decode($paypalIpnData, true);
	// Note : IPN and redirects will come here
	// Check if payment status exist and it is success
	if (isset($requestData['PayerID'])) {

		// Then create a data for success paypal data
		$paymentResponseData = [
			'status' => true,
			'rawData' => (array) $paypalIpnData,
			'data' => preparePaymentData($rawData['invoice'], $rawData['payment_gross'], $rawData['txn_id'], 'paypal'),
		];
		// Send data to payment response function for further process
		paymentResponse($paymentResponseData);
		$getPamentData = mysqli_query($db, "SELECT * FROM i_user_payments WHERE payment_type IN('point','product') AND payment_status = 'pending' AND payment_option = 'paypal' AND payer_iuid_fk = '$userID'") or die(mysqli_error($db));
		$pData = mysqli_fetch_array($getPamentData, MYSQLI_ASSOC);
		$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
		$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
		$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
		if(!empty($userPayedPlanID)){
			$planDetails = mysqli_query($db, "SELECT * FROM i_premium_plans WHERE plan_id = '$userPayedPlanID'") or die(mysqli_error($db));
			$pAData = mysqli_fetch_array($planDetails, MYSQLI_ASSOC);
			$planAmount = $pAData['plan_amount'];
			mysqli_query($db, "UPDATE i_users SET wallet_points = wallet_points + $planAmount WHERE iuid = '$userID'") or die(mysqli_error($db));
			mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'pending' WHERE payer_iuid_fk = '$userID' AND payment_type = 'point' AND payment_option = 'paypal'") or die(mysqli_error($db));
		}else if(!empty($productID)){
            $productDetailsFromID = mysqli_query($db, "SELECT * FROM i_user_product_posts WHERE pr_id = '$productID'") or die(mysqli_error($db));
			$productData = mysqli_fetch_array($productDetailsFromID, MYSQLI_ASSOC);
			$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
			$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
			$adminEarning = ($adminFee * $productPrice) / 100;
            $userEarning = $productPrice - $adminEarning;
			mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'pending' , payed_iuid_fk = '$productOwnerID', amount = '$productPrice', fee = '$adminFee', admin_earning = '$adminEarning', user_earning = '$userEarning' WHERE payer_iuid_fk = '$payerUserID' AND payment_type = 'product' AND payment_status = 'pending' AND payment_option = 'paypal'") or die(mysqli_error($db));
			mysqli_query($db, "UPDATE i_users SET wallet_money = wallet_money + '$userEarning' WHERE iuid = '$productOwnerID'") or die(mysqli_error($db));
		}
		// Check if payment not successfull
	} else {
		mysqli_query($db, "DELETE FROM i_user_payments WHERE payer_iuid_fk = '$userID' AND payment_option = 'paypal' AND payment_type  IN('point','product') AND payment_status = 'pending'") or die(mysqli_error($db));
		// Prepare payment failed data
		$paymentResponseData = [
			'status' => false,
			'rawData' => [],
			'data' => preparePaymentData($rawData['invoice'], $rawData['payment_gross'], null, 'paypal'),
		];
		// Send data to payment response function for further process
		paymentResponse($paymentResponseData);
	}

// Check Paystack payment process
} else if ($requestData['paymentOption'] == 'paystack') {

    // Front-end posts JSON from Paystack to this endpoint
    $requestData = json_decode($requestData['response'], true);

    // Normalize & guard
    $psStatus    = isset($requestData['status']) ? $requestData['status'] : null;
    $psData      = isset($requestData['data']) ? $requestData['data'] : [];
    $psReference = isset($psData['reference']) ? $psData['reference'] : '';
    $psAmountKobo= isset($psData['amount']) ? (int)$psData['amount'] : 0;
    $paidAmount  = $psAmountKobo > 0 ? ($psAmountKobo / 100) : 0; // Paystack sends kobo

    // Prepare response payload for your redirect pages
    $paymentResponseData = [
        'status'  => false,
        'rawData' => $requestData,
        'data'    => preparePaymentData($psReference, $paidAmount, $psReference, 'paystack'),
    ];

    if ($psStatus === 'success') {
        // 1) Strict lookup by Paystack reference -> i_user_payments.order_key
        $refEsc = mysqli_real_escape_string($db, $psReference);
        $getPamentData = mysqli_query(
            $db,
            "SELECT * FROM i_user_payments WHERE order_key='$refEsc' LIMIT 1"
        );

        // 2) Fallback: last pending Paystack by this user (keeps your old behaviour)
        if (!$getPamentData || mysqli_num_rows($getPamentData) === 0) {
            $getPamentData = mysqli_query(
                $db,
                "SELECT * FROM i_user_payments 
                 WHERE payer_iuid_fk = '$userID'
                   AND payment_status = 'pending'
                   AND payment_option  = 'paystack'
                   AND payment_type   IN('point','product')
                 ORDER BY payment_id DESC
                 LIMIT 1"
            );
        }

        if ($getPamentData && mysqli_num_rows($getPamentData) > 0) {
            $pData          = mysqli_fetch_array($getPamentData, MYSQLI_ASSOC);
            $paymentId      = (int)$pData['payment_id'];
            $payerUserID    = $pData['payer_iuid_fk'];
            $planId         = $pData['credit_plan_id'];        // non-empty => points
            $productId      = $pData['paymet_product_id'];     // non-empty => product
            $alreadyPaid    = (isset($pData['payment_status']) && $pData['payment_status'] === 'paid');

            if (!$alreadyPaid) {
                if (!empty($planId)) {
                    // CREDIT POINTS
                    $planDetails = mysqli_query($db, "SELECT * FROM i_premium_plans WHERE plan_id='".mysqli_real_escape_string($db,$planId)."'");
                    $pAData      = mysqli_fetch_array($planDetails, MYSQLI_ASSOC);
                    $planPoints  = (int)$pAData['plan_amount'];

                    // credit once
                    mysqli_query($db, "UPDATE i_users SET wallet_points = wallet_points + $planPoints WHERE iuid = '$payerUserID'");

                    // mark payment as PAID + store amount we actually got
                    mysqli_query($db, "
                        UPDATE i_user_payments 
                           SET payment_status = 'paid',
                               amount = '".mysqli_real_escape_string($db,$paidAmount)."'
                         WHERE payment_id = $paymentId
                           AND payment_status = 'pending'
                    ");

                } elseif (!empty($productId)) {
                    // CREDIT PRODUCT owner
                    $productQ  = mysqli_query($db, "SELECT * FROM i_user_product_posts WHERE pr_id='".mysqli_real_escape_string($db,$productId)."'");
                    $product   = mysqli_fetch_array($productQ, MYSQLI_ASSOC);
                    $productPrice   = (float)$product['pr_price'];
                    $productOwnerID = $product['iuid_fk'];

                    // admin cut
                    // $adminFee is already available in this file via includes
                    $adminEarning   = ($adminFee * $productPrice) / 100.0;
                    $userEarning    = $productPrice - $adminEarning;

                    // mark paid & earnings
                    mysqli_query($db, "
                        UPDATE i_user_payments 
                           SET payment_status = 'paid',
                               payed_iuid_fk   = '$productOwnerID',
                               amount          = '".mysqli_real_escape_string($db,$productPrice)."',
                               fee             = '".mysqli_real_escape_string($db,$adminFee)."',
                               admin_earning   = '".mysqli_real_escape_string($db,$adminEarning)."',
                               user_earning    = '".mysqli_real_escape_string($db,$userEarning)."'
                         WHERE payment_id = $paymentId
                           AND payment_status = 'pending'
                    ");

                    // credit seller wallet once
                    mysqli_query($db, "UPDATE i_users SET wallet_money = wallet_money + '$userEarning' WHERE iuid = '$productOwnerID'");
                }
            }

            // success redirect payload
            $paymentResponseData['status'] = true;
        }
    }

    // Redirect to your success/failure pages
    paymentResponse($paymentResponseData);

	}

// Check Stripe payment process
} else if ($requestData['paymentOption'] == 'stripe') {

	$stripeResponse = new StripeResponse();

	$stripeData = $stripeResponse->retrieveStripePaymentData($requestData['stripe_session_id']);

	// Check if payment charge status key exist in stripe data and it success
	if (isset($stripeData['status']) and $stripeData['status'] == "succeeded") {
		// Prepare data for success
		$paymentResponseData = [
			'status' => true,
			'rawData' => $stripeData,
			'data' => preparePaymentData($stripeData->charges->data[0]['balance_transaction'], $stripeData->amount, $stripeData->charges->data[0]['balance_transaction'], 'stripe'),
		];
		$getPamentData = mysqli_query($db, "SELECT * FROM i_user_payments WHERE order_key = '" . $requestData['orderId'] . "'") or die(mysqli_error($db));
		$pData = mysqli_fetch_array($getPamentData, MYSQLI_ASSOC);
		$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
		$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
		$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
		if(!empty($userPayedPlanID)){
			$planDetails = mysqli_query($db, "SELECT * FROM i_premium_plans WHERE plan_id = '$userPayedPlanID'") or die(mysqli_error($db));
			$pAData = mysqli_fetch_array($planDetails, MYSQLI_ASSOC);
			$planAmount = $pAData['plan_amount'];
			mysqli_query($db, "UPDATE i_users SET wallet_points = wallet_points + '$planAmount' WHERE iuid = '$payerUserID'") or die(mysqli_error($db));
			mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'pending' WHERE payer_iuid_fk = '$payerUserID' AND order_key = '" . $requestData['orderId'] . "'") or die(mysqli_error($db));
		}else if(!empty($productID)){
            $productDetailsFromID = mysqli_query($db, "SELECT * FROM i_user_product_posts WHERE pr_id = '$productID'") or die(mysqli_error($db));
			$productData = mysqli_fetch_array($productDetailsFromID, MYSQLI_ASSOC);
			$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
			$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
			$adminEarning = ($adminFee * $productPrice) / 100;
            $userEarning = $productPrice - $adminEarning;
			mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'pending' , payed_iuid_fk = '$productOwnerID', amount = '$productPrice', fee = '$adminFee', admin_earning = '$adminEarning', user_earning = '$userEarning' WHERE payer_iuid_fk = '$payerUserID' AND order_key = '" . $requestData['orderId'] . "'") or die(mysqli_error($db));
			mysqli_query($db, "UPDATE i_users SET wallet_money = wallet_money + '$userEarning' WHERE iuid = '$productOwnerID'") or die(mysqli_error($db));
		}

		// Check if stripe data is failed
	} else {
		// Prepare failed payment data
		$paymentResponseData = [
			'status' => false,
			'rawData' => $stripeData,
			'data' => preparePaymentData($requestData['orderId'], $stripeData->amount, null, 'stripe'),
		];
		mysqli_query($db, "DELETE FROM i_user_payments WHERE order_key = '" . $requestData['orderId'] . "'") or die(mysqli_error($db));
	}
	// Send data to payment response for further process
	paymentResponse($paymentResponseData);

// Check Razorpay payment process
} else if ($requestData['paymentOption'] == 'razorpay') {
	$orderId = $requestData['orderId'];

	$requestData = json_decode($requestData['response'], true);

	// Check if razorpay status exist and status is success
	if (isset($requestData['status']) and $requestData['status'] == 'captured') {
		// prepare payment data
		$paymentResponseData = [
			'status' => true,
			'rawData' => $requestData,
			'data' => preparePaymentData($orderId, $requestData['amount'], $requestData['id'], 'razorpay'),
		];
		$getPamentData = mysqli_query($db, "SELECT * FROM i_user_payments WHERE order_key = '" . $orderId . "'") or die(mysqli_error($db));
		$pData = mysqli_fetch_array($getPamentData, MYSQLI_ASSOC);
		$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
		$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
		$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
		if(!empty($userPayedPlanID)){
			$planDetails = mysqli_query($db, "SELECT * FROM i_premium_plans WHERE plan_id = '$userPayedPlanID'") or die(mysqli_error($db));
			$pAData = mysqli_fetch_array($planDetails, MYSQLI_ASSOC);
			$planAmount = $pAData['plan_amount'];
			mysqli_query($db, "UPDATE i_users SET wallet_points = wallet_points + $planAmount WHERE iuid = '$payerUserID'") or die(mysqli_error($db));
			mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'pending' WHERE payer_iuid_fk = '$payerUserID' AND order_key = '" . $orderId . "'") or die(mysqli_error($db));
		}else if(!empty($productID)){
            $productDetailsFromID = mysqli_query($db, "SELECT * FROM i_user_product_posts WHERE pr_id = '$productID'") or die(mysqli_error($db));
			$productData = mysqli_fetch_array($productDetailsFromID, MYSQLI_ASSOC);
			$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
			$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
			$adminEarning = ($adminFee * $productPrice) / 100;
            $userEarning = $productPrice - $adminEarning;
			mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'pending' , payed_iuid_fk = '$productOwnerID', amount = '$productPrice', fee = '$adminFee', admin_earning = '$adminEarning', user_earning = '$userEarning' WHERE payer_iuid_fk = '$payerUserID' AND order_key = '" . $orderId . "'") or die(mysqli_error($db));
			mysqli_query($db, "UPDATE i_users SET wallet_money = wallet_money + '$userEarning' WHERE iuid = '$productOwnerID'") or die(mysqli_error($db));
		}
		// send data to payment response
		paymentResponse($paymentResponseData);
		// razorpay status is failed
	} else {
		// prepare payment data for failed payment
		$paymentResponseData = [
			'status' => false,
			'rawData' => $requestData,
			'data' => preparePaymentData($orderId, $requestData['amount'], $requestData['id'], 'razorpay'),
		];
		mysqli_query($db, "DELETE FROM i_user_payments WHERE order_key = '" . $orderId . "'") or die(mysqli_error($db));
		// send data to payment response
		paymentResponse($paymentResponseData);
	}
} else if ($requestData['paymentOption'] == 'authorize-net') {
	$orderId = $requestData['order_id'];

	$requestData = json_decode($requestData['response'], true);

	// Check if razorpay status exist and status is success
	if (isset($requestData['status']) and $requestData['status'] == 'success') {
		// prepare payment data
		$paymentResponseData = [
			'status' => true,
			'rawData' => $requestData,
			'data' => preparePaymentData($orderId, $requestData['amount'], $requestData['transaction_id'], 'authorize-net'),
		];
		$getPamentData = mysqli_query($db, "SELECT * FROM i_user_payments WHERE order_key = '" . $requestData['order_id'] . "'") or die(mysqli_error($db));
		$pData = mysqli_fetch_array($getPamentData, MYSQLI_ASSOC);
		$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
		$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
		$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
		if(!empty($userPayedPlanID)){
			$planDetails = mysqli_query($db, "SELECT * FROM i_premium_plans WHERE plan_id = '$userPayedPlanID'") or die(mysqli_error($db));
			$pAData = mysqli_fetch_array($planDetails, MYSQLI_ASSOC);
			$planAmount = $pAData['plan_amount'];
			mysqli_query($db, "UPDATE i_users SET wallet_points = wallet_points + $planAmount WHERE iuid = '$payerUserID'") or die(mysqli_error($db));
			mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'pending' WHERE order_key = '" . $requestData['order_id'] . "'") or die(mysqli_error($db));
        }else if(!empty($productID)){
            $productDetailsFromID = mysqli_query($db, "SELECT * FROM i_user_product_posts WHERE pr_id = '$productID'") or die(mysqli_error($db));
			$productData = mysqli_fetch_array($productDetailsFromID, MYSQLI_ASSOC);
			$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
			$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
			$adminEarning = ($adminFee * $productPrice) / 100;
            $userEarning = $productPrice - $adminEarning;
			mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'pending' , payed_iuid_fk = '$productOwnerID', amount = '$productPrice', fee = '$adminFee', admin_earning = '$adminEarning', user_earning = '$userEarning' WHERE payer_iuid_fk = '$payerUserID' AND order_key = '" . $requestData['order_id'] . "'") or die(mysqli_error($db));
			mysqli_query($db, "UPDATE i_users SET wallet_money = wallet_money + '$userEarning' WHERE iuid = '$productOwnerID'") or die(mysqli_error($db));
		}
		// send data to payment response
		paymentResponse($paymentResponseData);
		// razorpay status is failed
	} else {
		// prepare payment data for failed payment
		$paymentResponseData = [
			'status' => false,
			'rawData' => $requestData,
			'data' => preparePaymentData($orderId, $requestData['amount'], $requestData['transaction_id'], 'authorize-net'),
		];
		mysqli_query($db, "DELETE FROM i_user_payments WHERE order_key='" . $requestData['order_id'] . "'") or die(mysqli_error($db));
		// send data to payment response
		paymentResponse($paymentResponseData);
	}
}else if ($requestData['paymentOption'] == 'mercadopago') {
    if ($requestData['collection_status'] == 'approved') {
        $paymentResponseData = [
            'status'   => true,
            'rawData'   => $requestData,
            'data'     => preparePaymentData($requestData['order_id'], $requestData['amount'], $requestData['collection_id'], 'mercadopago')
        ];
		$getPamentData = mysqli_query($db, "SELECT * FROM i_user_payments WHERE order_key = '" . $requestData['order_id'] . "'") or die(mysqli_error($db));
		$pData = mysqli_fetch_array($getPamentData, MYSQLI_ASSOC);
		$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
		$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
		$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
		if(!empty($userPayedPlanID)){
			$planDetails = mysqli_query($db, "SELECT * FROM i_premium_plans WHERE plan_id = '$userPayedPlanID'") or die(mysqli_error($db));
			$pAData = mysqli_fetch_array($planDetails, MYSQLI_ASSOC);
			$planAmount = $pAData['plan_amount'];
			mysqli_query($db, "UPDATE i_users SET wallet_points = wallet_points + $planAmount WHERE iuid = '$payerUserID'") or die(mysqli_error($db));
			mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'pending' WHERE order_key = '" . $requestData['order_id'] . "'") or die(mysqli_error($db));
        }else if(!empty($productID)){
            $productDetailsFromID = mysqli_query($db, "SELECT * FROM i_user_product_posts WHERE pr_id = '$productID'") or die(mysqli_error($db));
			$productData = mysqli_fetch_array($productDetailsFromID, MYSQLI_ASSOC);
			$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
			$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
			$adminEarning = ($adminFee * $productPrice) / 100;
            $userEarning = $productPrice - $adminEarning;
			mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'pending' , payed_iuid_fk = '$productOwnerID', amount = '$productPrice', fee = '$adminFee', admin_earning = '$adminEarning', user_earning = '$userEarning' WHERE payer_iuid_fk = '$payerUserID' AND order_key = '" . $requestData['order_id'] . "'") or die(mysqli_error($db));
			mysqli_query($db, "UPDATE i_users SET wallet_money = wallet_money + '$userEarning' WHERE iuid = '$productOwnerID'") or die(mysqli_error($db));
		}
    } elseif ($requestData['collection_status'] == 'pending') {
        $paymentResponseData = [
            'status'   => 'pending',
            'rawData'   => $requestData,
            'data'     => preparePaymentData($requestData['order_id'], $requestData['amount'], $requestData['collection_id'], 'mercadopago')
        ];
		mysqli_query($db, "DELETE FROM i_user_payments WHERE order_key='" . $requestData['order_id'] . "'") or die(mysqli_error($db));
    } else {
        $paymentResponseData = [
            'status'   => false,
            'rawData'   => $requestData,
            'data'     => preparePaymentData($requestData['order_id'], $requestData['amount'], $requestData['collection_id'], 'mercadopago')
        ];
		mysqli_query($db, "DELETE FROM i_user_payments WHERE order_key='" . $requestData['order_id'] . "'") or die(mysqli_error($db));
    }
    paymentResponse($paymentResponseData);
} else if ($requestData['paymentOption'] == 'mercadopago-ipn') {
    $mercadopagoResponse = new MercadopagoResponse;
    $mercadopagoIpnData = $mercadopagoResponse->getMercadopagoPaymentData($requestData);

    $rawPostData = json_decode(file_get_contents('php://input'), true);

	if(isset($rawPostData["topic"])){
		if($rawPostData["topic"] == "merchant_order"){

			$call_merchant_order_id = $rawPostData["resource"];

			$query = mysqli_query($db, "SELECT * FROM  i_payment_methods WHERE payment_method_id = '1'") or die(mysqli_error($this->db));
			$token_mp = mysqli_fetch_array($query, MYSQLI_ASSOC);


			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $call_merchant_order_id);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
			$headers = array();
			$headers[] = 'Authorization: Bearer '.$token_mp["mercadopago_live_access_id"]; //
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);
			if (curl_errno($ch)) {
					echo 'Error:' . curl_error($ch);
			}
			curl_close($ch);

			$data = json_decode($result, true);


			if(isset($data["order_status"])){
				if($data["order_status"] == "paid"){
					$paymentResponseData = [
							'status'   => true,
							'rawData'   => $requestData,
							'data'     => preparePaymentData($requestData['order_id'], $requestData['amount'], $requestData['id'], 'mercadopago')
					];
					$getPamentData = mysqli_query($db, "SELECT * FROM i_user_payments WHERE order_key = '" . $requestData['order_id'] . "'") or die(mysqli_error($db));
					$pData = mysqli_fetch_array($getPamentData, MYSQLI_ASSOC);
					$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
					$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
					$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
					if(!empty($pData)){
						if(!empty($userPayedPlanID)){
							$planDetails = mysqli_query($db, "SELECT * FROM i_premium_plans WHERE plan_id = '$userPayedPlanID'") or die(mysqli_error($db));
							$pAData = mysqli_fetch_array($planDetails, MYSQLI_ASSOC);
							$planAmount = $pAData['plan_amount'];
							mysqli_query($db, "UPDATE i_users SET wallet_points = wallet_points + $planAmount WHERE iuid = '$payerUserID'") or die(mysqli_error($db));
							mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'paid' WHERE order_key = '" . $requestData['order_id'] . "'") or die(mysqli_error($db));

						}else if(!empty($productID)){

							$productDetailsFromID = mysqli_query($db, "SELECT * FROM i_user_product_posts WHERE pr_id = '$productID'") or die(mysqli_error($db));
							$productData = mysqli_fetch_array($productDetailsFromID, MYSQLI_ASSOC);
							$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
							$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
							$adminEarning = ($adminFee * $productPrice) / 100;
										$userEarning = $productPrice - $adminEarning;
							mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'paid' , payed_iuid_fk = '$productOwnerID', amount = '$productPrice', fee = '$adminFee', admin_earning = '$adminEarning', user_earning = '$userEarning' WHERE  payer_iuid_fk = '$payerUserID' AND order_key = '" . $requestData['order_id'] . "'") or die(mysqli_error($db));
							mysqli_query($db, "UPDATE i_users SET wallet_money = wallet_money + '$userEarning' WHERE iuid = '$productOwnerID'") or die(mysqli_error($db));
						}

					}

				}
				paymentResponse($paymentResponseData);
			}

		}
	}
} else if ($requestData['paymentOption'] == 'bitpay') {
	// prepare payment data
	$paymentResponseData = [
		'status' => true,
		'rawData' => $requestData,
		'data' => preparePaymentData($requestData['orderId'], $requestData['amount'], $requestData['orderId'], 'bitpay'),
	];
	$getPamentData = mysqli_query($db, "SELECT * FROM i_user_payments WHERE order_key = '" . $requestData['orderId'] . "'") or die(mysqli_error($db));
	$pData = mysqli_fetch_array($getPamentData, MYSQLI_ASSOC);
	$userPayedPlanID = isset($pData['credit_plan_id']) ? $pData['credit_plan_id'] : NULL;
	$payerUserID = isset($pData['payer_iuid_fk']) ? $pData['payer_iuid_fk'] : NULL;
	$productID = isset($pData['paymet_product_id']) ? $pData['paymet_product_id'] : NULL;
	if(!empty($userPayedPlanID)){
		$planDetails = mysqli_query($db, "SELECT * FROM i_premium_plans WHERE plan_id = '$userPayedPlanID'") or die(mysqli_error($db));
		$pAData = mysqli_fetch_array($planDetails, MYSQLI_ASSOC);
		$planAmount = $pAData['plan_amount'];
		mysqli_query($db, "UPDATE i_users SET wallet_points = wallet_points + $planAmount WHERE iuid = '$payerUserID'") or die(mysqli_error($db));
		mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'pending' WHERE order_key = '" . $requestData['orderId'] . "'") or die(mysqli_error($db));
	}else if(!empty($productID)){
		$productDetailsFromID = mysqli_query($db, "SELECT * FROM i_user_product_posts WHERE pr_id = '$productID'") or die(mysqli_error($db));
		$productData = mysqli_fetch_array($productDetailsFromID, MYSQLI_ASSOC);
		$productPrice = isset($productData['pr_price']) ? $productData['pr_price'] : NULL;
		$productOwnerID = isset($productData['iuid_fk']) ? $productData['iuid_fk'] : NULL;
		$adminEarning = ($adminFee * $productPrice) / 100;
		$userEarning = $productPrice - $adminEarning;
		mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'pending' , payed_iuid_fk = '$productOwnerID', amount = '$productPrice', fee = '$adminFee', admin_earning = '$adminEarning', user_earning = '$userEarning' WHERE payer_iuid_fk = '$payerUserID' AND order_key = '" . $requestData['orderId'] . "'") or die(mysqli_error($db));
		mysqli_query($db, "UPDATE i_users SET wallet_money = wallet_money + '$userEarning' WHERE iuid = '$productOwnerID'") or die(mysqli_error($db));
	}
	// send data to payment response
	paymentResponse($paymentResponseData);
} else if ($requestData['paymentOption'] == 'bitpay-ipn') {
	$bitpayResponse = new BitPayResponse;
	$rawPostData = file_get_contents('php://input');
	$ipnData = $bitpayResponse->getBitPayPaymentData($rawPostData);
	if ($ipnData['status'] == 'success') {
		// code here
		mysqli_query($db, "UPDATE i_user_payments SET payment_status = 'pending' WHERE order_key = '" . $requestData['orderId'] . "'") or die(mysqli_error($db));
	} else {
		// code here
		mysqli_query($db, "DELETE FROM i_user_payments WHERE order_key='" . $requestData['orderId'] . "'") or die(mysqli_error($db));
	}
}

/*
 * This payment used for get Success / Failed data for any payment method.
 *
 * @param array $paymentResponseData - contains : status and rawData
 *
 */
function paymentResponse($paymentResponseData) {
	// payment status success
	if ($paymentResponseData['status']) {
		// Show payment success page or do whatever you want, like send email, notify to user etc
		header('Location: ' . getAppUrl('payment-success.php'));
		//  var_dump($paymentResponseData);
	} else {
		// Show payment error page or do whatever you want, like send email, notify to user etc
		header('Location: ' . getAppUrl('payment-failed.php'));
	}
}

/*
 * Prepare Payment Data.
 *
 * @param array $paymentData
 *
 */
function preparePaymentData($orderId, $amount, $txnId, $paymentGateway) {
	return [
		'order_id' => $orderId,
		'amount' => $amount,
		'payment_reference_id' => $txnId,
		'payment_gatway' => $paymentGateway,
	];
}