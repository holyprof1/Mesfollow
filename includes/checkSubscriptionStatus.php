<?php
require_once('inc.php');
require_once('stripe/vendor/autoload.php');

$stripe = [
  "secret_key"      => $stripeKey,
  "publishable_key" => $stripePublicKey,
];

\Stripe\Stripe::setApiKey($stripeKey);

$query = mysqli_query($db,"SELECT * FROM i_user_subscriptions WHERE status IN('active','inactive') AND in_status IN('1','0') AND finished = '0'") or die(mysqli_error($db));
if(mysqli_num_rows($query) > 0){ 
    
  while($row = mysqli_fetch_assoc($query)){
      $stripeCustomerID = $row['customer_id'];
      $subscriptionID = $row['payment_subscription_id'];
      $subscriberUser = $row['iuid_fk'];
      $subscribedUser = $row['subscribed_iuid_fk'];
      $subscriptionPlanID = $row['plan_id'];  
 
      $customer = \Stripe\Subscription::retrieve($subscriptionID);
      $customerSubscriptionStatus = isset($customer->status) ? $customer->status : NULL; 
      if(!empty($stripeCustomerID) && !empty($customerSubscriptionStatus)){  
        if($customerSubscriptionStatus == 'active'){ 
        }else{ 
          mysqli_query($db, "UPDATE i_friends SET fr_status = 'flwr' WHERE fr_one = '$subscriberUser' AND fr_two='$subscribedUser'") or die(mysqli_error($db));
          mysqli_query($db,"UPDATE i_user_subscriptions SET status = 'declined' WHERE iuid_fk = '$subscriberUser' AND subscribed_iuid_fk = '$subscribedUser'") or die(mysqli_error($db));					       
        } 
         
      }

  }
    
}else{
    echo 'Someting Wrong';
}
?>