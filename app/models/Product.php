<?php
class Product {
  public static function all($mysqli){
    return $mysqli
      ->query("SELECT id,name,price_usd,image,category_id FROM products WHERE is_active=1")
      ->fetch_all(MYSQLI_ASSOC);
  }

  public static function find($mysqli, $id){
    $stmt = $mysqli->prepare(
      "SELECT * FROM products WHERE id=? AND is_active=1"
    );
    $stmt->bind_param('i',$id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }
}
