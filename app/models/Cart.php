<?php
class Cart {
  public function __construct(){
    if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
  }

  public function add(array $item){
    $id = $item['id'];
    if(isset($_SESSION['cart'][$id])){
      $_SESSION['cart'][$id]['qty'] += $item['qty'];
    } else {
      $_SESSION['cart'][$id] = $item;
    }
  }

  public function update(int $id, int $qty){
    if(isset($_SESSION['cart'][$id])){
      if($qty>0) $_SESSION['cart'][$id]['qty'] = $qty;
      else        unset($_SESSION['cart'][$id]);
    }
  }

  public function remove(int $id){
    unset($_SESSION['cart'][$id]);
  }

  public function items(){
    return $_SESSION['cart'];
  }

  public function total(){
    return array_reduce(
      $_SESSION['cart'], 
      fn($sum,$i)=>$sum + $i['price']*$i['qty'], 
      0
    );
  }

  public function clear(){
    $_SESSION['cart'] = [];
  }
}
