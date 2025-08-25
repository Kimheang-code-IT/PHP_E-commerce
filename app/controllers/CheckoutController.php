<?php
class CheckoutController {
  public function handle(){
    global $mysqli;
    if($_SERVER['REQUEST_METHOD']==='POST'){
      // assume user is logged in & has $userId
      $userId = $_SESSION['user_id'] ?? 'guest';
      $cart   = new Cart();
      $orderId = Order::create(
        $mysqli,
        $userId,
        $cart,
        $_POST['payment_method'] ?? 'cash'
      );
      $cart->clear();
      header("Location: /checkout?success=$orderId");
      exit;
    }
    include __DIR__.'/../../views/site/checkout.php';
  }
}
