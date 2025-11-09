<?php

// Simple script to check Redis state
$redis = new Redis();
$redis->connect('redis', 6379);

echo "🔍 Redis State Debug\n";
echo "===================\n\n";

// Check specific phone
$phone = '554288872501';
$key = "convo:{$phone}:state";
$state = $redis->get($key);

if ($state) {
    echo "✅ State found for {$phone}:\n";
    echo "Key: {$key}\n";
    echo "Value: {$state}\n";
    echo "Decoded: ";
    print_r(json_decode($state, true));
    
    $ttl = $redis->ttl($key);
    echo "TTL: {$ttl} seconds\n";
} else {
    echo "❌ No state found for {$phone}\n";
}

echo "\n🔍 All conversation states:\n";
$keys = $redis->keys('convo:*');
foreach ($keys as $key) {
    $value = $redis->get($key);
    $ttl = $redis->ttl($key);
    echo "Key: {$key}\n";
    echo "Value: {$value}\n";
    echo "TTL: {$ttl}s\n";
    echo "---\n";
}
