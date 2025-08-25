<?php
class Order {
  public static function create($mysqli, $userId, $cart, $paymentMethod){
    // 1) Insert into orders
    $stmt = $mysqli->prepare(
      "INSERT INTO orders (user_id,status,total_usd,shipping_country)
       VALUES (?,?,?,?)"
    );
    $total = $cart->total();
    $status = $paymentMethod==='cash' ? 'pending' : 'paid';
    $country = $_POST['country'] ?? '';
    $stmt->bind_param('isds', $userId,$status,$total,$country);
    $stmt->execute();
    $orderId = $mysqli->insert_id;

    // 2) Line items
    $stmt2 = $mysqli->prepare(
      "INSERT INTO order_items (order_id,product_id,quantity,unit_price)
       VALUES (?,?,?,?)"
    );
    foreach($cart->items() as $i){
      $stmt2->bind_param(
        'iiid',
        $orderId,
        $i['id'],
        $i['qty'],
        $i['price']
      );
      $stmt2->execute();
    }
    return $orderId;
  }
}
