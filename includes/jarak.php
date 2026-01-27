<?php
// includes/jarak.php
// Helper tunggal untuk menghitung jarak (Haversine) antar koordinat
function hitungJarak($lat1, $lon1, $lat2, $lon2)
{
    // Validasi sederhana
    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 0;
    $earth = 6371; // radius bumi km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earth * $c, 1); // hasil dalam km, 1 desimal
}
