<?php
session_start();
require __DIR__.'/../config/db.php';
require __DIR__.'/../app/models/Product.php';
require __DIR__.'/../app/models/Cart.php';
require __DIR__.'/../app/models/Order.php';
require __DIR__.'/../app/controllers/ProductController.php';
require __DIR__.'/../app/controllers/CartController.php';
require __DIR__.'/../app/controllers/CheckoutController.php';

$path = trim($_SERVER['REQUEST_URI'], '/');
switch(true) {
  case $path === '' || $path === 'index.php':
  case preg_match('#^shop#', $path):
    (new ProductController)->list();
    break;

  case preg_match('#^product-detail#', $path):
    (new ProductController)->detail();
    break;

  case preg_match('#^cart#', $path):
    (new CartController)->view();
    break;

  case preg_match('#^add-to-cart#', $path):
    (new CartController)->add();
    break;

  case preg_match('#^update-cart#', $path):
    (new CartController)->update();
    break;

  case preg_match('#^remove-from-cart#', $path):
    (new CartController)->remove();
    break;

  case preg_match('#^checkout#', $path):
    (new CheckoutController)->handle();
    break;

  default:
    header("HTTP/1.0 404 Not Found");
    echo "Page not found";
}
