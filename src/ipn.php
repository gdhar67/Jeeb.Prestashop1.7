<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/jeeb.php');

$jeeb = new jeeb();
$postdata = file_get_contents("php://input");
$json = json_decode($postdata, true);
// fclose($handle);
if($json['signature']==Configuration::get('jeeb_APIKEY')){
  if($json['orderNo']){
    error_log("hey".$json['orderNo']);

    $orderNo = $json['orderNo'];

    $db = Db::getInstance();
    $result = array();
    $result = $db->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'order_bitcoin_jeeb` WHERE `id_order` = "' . $json['orderNo'] . '";');
    error_log("Db Result = ".print_r($result[0],true));
    error_log("Key =".$result[0]['key']);

    $cart_id = (int)$result[0]['cart_id'];
    $order_id = Order::getOrderByCartId($cart_id);
    $order = new Order($order_id);


    // Call Jeeb
    if (Configuration::get('jeeb_TESTMODE') == "1")
    {
        $network_uri = "https://test.jeeb.io/";
    }
    else
    {
        $network_uri = "https://jeeb.io/";
    }


    error_log("Entered Jeeb-Notification");
    if ( $json['stateId']== 2 ) {
      error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
      error_log('Object : '.print_r($json, true));

      $order_status = Configuration::get('JEEB_PENDING');

      error_log("Data : ".$order_status." cart ".$cart_id." amount ".$json['requestAmount']." key ".(string)$result[0]['key'] );

      $jeeb->validateOrder($cart_id, $order_status, $json['requestAmount'], "Jeeb", null, array(), null, false, (string)$result[0]['key']);

      $new_history = new OrderHistory();
      $new_history->id_order = (int)$json['orderNo'];
      $new_history->changeIdOrderState((int)$order_status, $order, true);
      $new_history->addWithemail(true);

    }
    else if ( $json['stateId']== 3 ) {
      error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
      error_log('Object : '.print_r($json, true));

      $order_status = Configuration::get('JEEB_CONFIRMING');

      if (empty(Context::getContext()->link))
      {
          Context::getContext()->link = new Link(); // workaround a prestashop bug so email is sent
      }

      $new_history = new OrderHistory();
      $new_history->id_order = (int)$json['orderNo'];
      $new_history->changeIdOrderState((int)$order_status, $order, true);
      $new_history->addWithemail(true);

    }
    else if ( $json['stateId']== 4 ) {
      error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
      $data = array(
        "token" => $json["token"]
      );

      $data_string = json_encode($data);
      $api_key = Configuration::get('jeeb_APIKEY');
      $url = $network_uri.'api/bitcoin/confirm/'.$api_key;
      error_log("Signature:".$api_key." Base-Url:".$network_uri." Url:".$url);

      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'Content-Length: ' . strlen($data_string))
      );

      $result = curl_exec($ch);
      $data = json_decode( $result , true);
      error_log("data = ".var_export($data, TRUE));


      if($data['result']['isConfirmed']){
        error_log('Payment confirmed by jeeb');

        $db = Db::getInstance();
        $result = array();
        $result = $db->ExecuteS('SELECT * FROM `' . _DB_PREFIX_ . 'order_bitcoin_jeeb` WHERE `id_order` = "' . $json['orderNo'] . '";');
        error_log("Db Result = ".print_r($result[0],true));
        error_log("Key =".$result[0]['key']);

        $cart_id = (int)$result[0]['cart_id'];
        $order_status = Configuration::get('PS_OS_PAYMENT');

        $new_history = new OrderHistory();
        $new_history->id_order = (int)$json['orderNo'];
        $new_history->changeIdOrderState((int)$order_status, $order, true);
        $new_history->addWithemail(true);


      }
      else {
        error_log('Payment confirmation rejected by jeeb');
      }
    }
    else if ( $json['stateId']== 5 ) {
      error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);

      $order_status = Configuration::get('JEEB_EXPIRED');

      $new_history = new OrderHistory();
      $new_history->id_order = (int)$json['orderNo'];
      $new_history->changeIdOrderState((int)$order_status, $order, true);
      $new_history->addWithemail(true);

    }
    else if ( $json['stateId']== 6 ) {
      error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);

      $order_status = Configuration::get('PS_OS_CANCELED');

      $new_history = new OrderHistory();
      $new_history->id_order = (int)$json['orderNo'];
      $new_history->changeIdOrderState((int)$order_status, $order, true);
      $new_history->addWithemail(true);

    }
    else if ( $json['stateId']== 7 ) {
      error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);

      $order_status = Configuration::get('PS_OS_CANCELED');

      $new_history = new OrderHistory();
      $new_history->id_order = (int)$json['orderNo'];
      $new_history->changeIdOrderState((int)$order_status, $order, true);
      $new_history->addWithemail(true);

    }
    else{
      error_log('Cannot read state id sent by Jeeb');
    }
}
}
