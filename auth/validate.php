<?php
// C:\xampp\htdocs\Barcode\auth\validate.php

require_once __DIR__ . '/../config/auth.php';

// Protect page, optionally specify allowed roles
protect(['pos_user', 'admin']); // Adjust roles as needed
?>