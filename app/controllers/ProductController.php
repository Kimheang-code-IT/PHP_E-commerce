<?php
class ProductController {
  public function list(){
    global $mysqli;
    $products = Product::all($mysqli);
    include __DIR__.'/../../views/site/shop.php';
  }

  public function detail(){
    global $mysqli;
    $id = (int)($_GET['id'] ?? 0);
    $product = Product::find($mysqli,$id);
    include __DIR__.'/../../views/site/product-detail.php';
  }
}
