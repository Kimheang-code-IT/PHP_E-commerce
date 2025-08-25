<?php
class CartController {
  public function view(){
    $cart = new Cart();
    include __DIR__.'/../../views/site/cart.php';
  }

  public function add(){
    global $mysqli;
    $id   = (int)$_POST['product_id'];
    $qty  = max(1,(int)$_POST['quantity']);
    $p    = Product::find($mysqli,$id);
    if($p){
      $cart = new Cart();
      $cart->add([
        'id'=>$p['id'],
        'name'=>$p['name'],
        'price'=>$p['price_usd'],
        'image'=>$p['image'],
        'qty'=>$qty
      ]);
    }
    header('Location: '.$_SERVER['HTTP_REFERER']);
  }

  public function update(){
    $cart = new Cart();
    foreach($_POST['qty'] as $id=>$q){
      $cart->update((int)$id, (int)$q);
    }
    header('Location: /cart');
  }

  public function remove(){
    $id = (int)$_GET['id'];
    $cart = new Cart();
    $cart->remove($id);
    header('Location: /cart');
  }
}
