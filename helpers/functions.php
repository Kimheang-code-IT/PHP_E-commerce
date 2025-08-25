<?php
    function getStatusBadgeClass($status) {
        switch(strtolower($status)) {
            case "completed": return "success";
            case "pending": return "warning";
            case "cancelled": return "danger";
            case "paid": return "primary";
            case "shipped": return "info";
            default: return "secondary";
        }
    }
    
    function generateBarcode($orderId, $productId, $quantity) {
        return "ORD" . str_pad($orderId, 6, "0", STR_PAD_LEFT) . 
               "PRD" . str_pad($productId, 6, "0", STR_PAD_LEFT) . 
               "QTY" . str_pad($quantity, 3, "0", STR_PAD_LEFT);
    }
    