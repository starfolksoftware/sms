#!/usr/bin/env php
<?php

/**
 * Demo script to test the Website Form Webhook Endpoint
 * 
 * This script simulates a website form submission by sending a POST request
 * to the webhook endpoint with sample lead data.
 */

$webhookUrl = 'http://localhost:8000/api/webhooks/lead-form';
$webhookToken = 'test-secret-token-12345'; // Should match WEBHOOK_LEAD_TOKEN in .env

$samplePayload = [
    'first_name' => 'Sarah',
    'last_name' => 'Williams',
    'email' => 'sarah.williams@example.com',
    'phone' => '+1-555-DEMO-123',
    'company' => 'Demo Company Inc',
    'job_title' => 'Marketing Director',
    'message' => 'Hi! I saw your CRM demo and I\'m interested in learning more about pricing and features for our team of 25 people.',
    'utm_source' => 'google',
    'utm_medium' => 'cpc',
    'utm_campaign' => 'fall-demo-2025',
    'utm_term' => 'customer relationship management software',
    'utm_content' => 'demo-cta-button',
    'idempotency_key' => 'demo-' . uniqid(),
    'consent' => true,
];

echo "🚀 Testing Website Form Webhook Endpoint\n";
echo "📍 URL: {$webhookUrl}\n";
echo "📝 Payload: " . json_encode($samplePayload, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $webhookUrl,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Starfolk-Webhook-Token: ' . $webhookToken,
    ],
    CURLOPT_POSTFIELDS => json_encode($samplePayload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_VERBOSE => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📡 Response:\n";
echo "Status Code: {$httpCode}\n";

if ($error) {
    echo "❌ cURL Error: {$error}\n";
} else {
    echo "Body: {$response}\n";
    
    if ($httpCode === 202) {
        echo "\n✅ Success! Webhook accepted and queued for processing.\n";
        $responseData = json_decode($response, true);
        if (isset($responseData['receipt_id'])) {
            echo "📄 Receipt ID: {$responseData['receipt_id']}\n";
            echo "\n💡 Check the admin panel under 'Webhook Events' to monitor processing status.\n";
        }
    } else {
        echo "\n❌ Webhook failed. Check the error response above.\n";
    }
}

echo "\n🔍 To verify the lead was created, check:\n";
echo "   • Admin Panel > Contacts (for the new lead)\n";
echo "   • Admin Panel > Webhook Events (for processing status)\n";
echo "   • Activity Log (for the creation event)\n";