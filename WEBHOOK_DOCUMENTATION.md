# Website Form Webhook Endpoint

A secure, reliable webhook endpoint for capturing leads from website forms and landing pages.

## Overview

This implementation provides a hardened webhook endpoint that:
- Accepts form submissions via server-to-server POST requests
- Validates and normalizes incoming data
- Creates leads in the CRM with proper attribution
- Handles duplicates intelligently
- Provides admin visibility and monitoring

## API Endpoint

**URL:** `POST /api/webhooks/lead-form`

### Authentication

Include the webhook token in the request header:
```
X-Starfolk-Webhook-Token: your-secret-token
```

Configure the token in your `.env` file:
```env
WEBHOOK_LEAD_TOKEN=your-secret-webhook-token-here
```

### Request Format

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "company": "Acme Corp",
  "job_title": "Product Manager",
  "message": "Interested in your CRM solution",
  "utm_source": "google",
  "utm_medium": "cpc",
  "utm_campaign": "summer-sale",
  "utm_term": "crm software",
  "utm_content": "ad-variant-a",
  "idempotency_key": "unique-submission-id",
  "consent": true
}
```

### Field Requirements

- **At least one of:** `email`, `phone`, or `name` is required
- All other fields are optional
- String length limits are enforced (see validation rules)

### Response Codes

| Code | Description |
|------|-------------|
| `202` | Accepted - webhook queued for processing |
| `401` | Unauthorized - invalid or missing token |
| `422` | Validation Error - invalid payload |
| `429` | Too Many Requests - rate limit exceeded |

### Example Success Response

```json
{
  "message": "Webhook received and queued for processing",
  "receipt_id": 12345
}
```

## Features

### 🔒 Security
- **Token Authentication** - Shared secret validation
- **Rate Limiting** - 30 requests/minute per IP (configurable)
- **HTTPS Enforcement** - Rejects non-TLS in production
- **Input Validation** - Comprehensive payload sanitization

### 🔄 Reliability
- **Idempotency** - Duplicate prevention using `idempotency_key`
- **Async Processing** - Non-blocking webhook acceptance
- **Retry Logic** - Exponential backoff (1m, 5m, 15m, 60m)
- **Error Tracking** - Failed webhooks logged for debugging

### 📊 Contact Management
- **Smart Duplicate Detection** - Matches by email address
- **Existing Contact Updates** - Appends new data without overwriting
- **Lead Attribution** - Captures UTM parameters and source
- **Activity Logging** - Audit trail for all lead interactions

### 👥 Admin Interface
- **Webhook Events** - Real-time monitoring dashboard
- **Payload Inspection** - JSON viewer for debugging
- **Status Tracking** - Pending, processing, processed, failed
- **Error Details** - Full error messages and stack traces

## Configuration

### Environment Variables

```env
# Required
WEBHOOK_LEAD_TOKEN=your-webhook-secret-token

# Optional
WEBHOOK_RATE_LIMIT=30
DEFAULT_LEAD_OWNER_ID=1
```

### Queue Configuration

The webhook uses Laravel's queue system. Ensure your queue worker is running:

```bash
php artisan queue:work
```

For production, use a process manager like Supervisor.

## Monitoring

### Admin Dashboard

Navigate to **Webhook Events** in the admin panel to:
- View all webhook submissions
- Monitor processing status
- Debug failed webhooks
- Inspect raw payloads

### Logs

Check Laravel logs for detailed processing information:
```bash
tail -f storage/logs/laravel.log
```

## Testing

Run the comprehensive test suite:

```bash
# All webhook tests
php artisan test --filter=Webhook

# Specific test classes
php artisan test --filter=WebhookLeadFormTest
php artisan test --filter=ProcessInboundWebhookTest
php artisan test --filter=WebhookIntegrationTest
```

### Manual Testing

Use the included demo script:

```bash
php demo_webhook.php
```

Or test with curl:

```bash
curl -X POST "https://your-app.test/api/webhooks/lead-form" \
  -H "Content-Type: application/json" \
  -H "X-Starfolk-Webhook-Token: your-secret-token" \
  -d '{
    "first_name": "Test",
    "last_name": "User",
    "email": "test@example.com",
    "idempotency_key": "test-123"
  }'
```

## Integration Examples

### JavaScript (Website Form)

```javascript
async function submitWebhookForm(formData) {
  try {
    const response = await fetch('/api/webhooks/lead-form', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Starfolk-Webhook-Token': 'your-webhook-token'
      },
      body: JSON.stringify({
        ...formData,
        idempotency_key: generateUniqueId(),
        utm_source: getUTMSource(),
        utm_campaign: getUTMCampaign()
      })
    });
    
    if (response.status === 202) {
      showSuccessMessage();
    } else {
      handleError(await response.json());
    }
  } catch (error) {
    handleNetworkError(error);
  }
}
```

### PHP (External Service)

```php
$webhook = new WebhookSubmitter([
    'url' => 'https://your-crm.app/api/webhooks/lead-form',
    'token' => 'your-webhook-token',
    'timeout' => 30,
    'retry_attempts' => 3
]);

$result = $webhook->submit([
    'email' => $leadEmail,
    'name' => $leadName,
    'source' => 'partner_website',
    'idempotency_key' => $uniqueSubmissionId
]);
```

## Troubleshooting

### Common Issues

**401 Unauthorized**
- Check `WEBHOOK_LEAD_TOKEN` in .env
- Verify `X-Starfolk-Webhook-Token` header

**422 Validation Error**
- Ensure at least one of email/phone/name is provided
- Check field length limits
- Validate email format

**429 Rate Limited**
- Reduce request frequency
- Adjust `WEBHOOK_RATE_LIMIT` if needed

**500 Server Error**
- Check Laravel logs
- Verify database connectivity
- Ensure queue worker is running

### Debug Mode

Enable verbose logging in development:

```env
LOG_LEVEL=debug
```

This will log all webhook processing steps for detailed debugging.

## Security Considerations

- **Token Security** - Use strong, unique tokens and rotate regularly
- **HTTPS Only** - Never accept webhooks over unencrypted connections
- **Rate Limiting** - Protect against abuse and DoS attempts
- **Input Validation** - All inputs are sanitized and validated
- **PII Handling** - Personal data is stored securely and logs are sanitized

## Performance

- **Async Processing** - Webhooks return immediately, processing happens in background
- **Database Indexing** - Optimized queries for duplicate detection
- **Efficient Queues** - Uses Laravel's optimized job system
- **Rate Limiting** - Prevents resource exhaustion

The endpoint can handle high-volume submissions with proper queue worker scaling.