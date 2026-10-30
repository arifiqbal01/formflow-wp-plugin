# FormFlow

## Version 1.2: mandatory payments

FormFlow now supports mandatory EUR payments through Pronamic Pay with Mollie. Enable payment per event, set its server-side price, and use `[formflow_payment_summary]` in the CF7 form. Paid registration is confirmed only after successful payment. Active checkout reservations count against capacity. Paid events do not use the automatic unpaid waiting-list workflow described below.

See [mandatory payment setup, behaviour and tests](docs/mandatory-payments.md) before enabling this on a website. Existing events and confirmed attendees are preserved during the upgrade.

FormFlow is a WordPress plugin that extends Contact Form 7 with event registration management. It allows administrators to create events, manage registration batches, automatically handle waiting lists, and monitor registrations from the WordPress dashboard.

The plugin is designed for organizations that run recurring events where registrations are limited by capacity.

---

## Features

### Event Management

- Create and manage events
- Connect an event to a Contact Form 7 form
- Enable or disable waiting lists
- Prevent duplicate Contact Form assignments

---

### Batch-Based Registration

Each event can contain multiple batches.

Each batch supports:

- Capacity limits
- Open
- Closed
- Completed
- Cancelled

Only one batch can be open at a time.

---

### Automatic Waiting List

If an event has:

- no active batch
- or the active batch is full

registrations are automatically placed on the waiting list instead of being rejected.

When a new batch is opened:

- waiting registrations are promoted automatically
- oldest registrations are promoted first
- promotion continues until capacity is reached

---

### Contact Form 7 Integration

FormFlow integrates directly with Contact Form 7.

Supported features:

- Uses native CF7 validation
- Uses native success message for confirmed registrations
- Displays a custom success message for waiting list registrations
- Stores the complete submitted form data

---

### Email Notifications

Automatic emails include:

- Waiting List confirmation
- Registration promotion confirmation

Email recipients are detected automatically from the Contact Form.

---

### Registration Management

Admin interface includes:

- Registration list
- Event
- Batch
- Status
- Contact information
- Submission date
- Expandable submission details

---

### Dashboard

Dashboard includes:

- Total events
- Registered attendees
- Waiting list count
- Active batches
- Available seats
- Quick actions

---

## Architecture

```
Contact Form 7
        │
        ▼
Registration Service
        │
        ▼
Event
        │
        ▼
Batch
        │
        ▼
Registration
```

Business logic is separated into services.

```
Database
    ↓

Services
    ↓

Admin Controllers
    ↓

Views
```

---

## Project Structure

```
formflow/

includes/

    admin/
    database/
    dto/
    forms/
    integrations/
    services/
    views/

assets/

languages/
```

---

## Core Services

### Event Service

Responsible for:

- creating events
- updating events
- validation
- deleting events

---

### Registration Service

Responsible for:

- new registrations
- waiting list logic
- promotion
- cancellation

---

### Batch Service

Responsible for:

- creating batches
- opening batches
- closing batches
- completing batches
- updating capacity

---

### Capacity Service

Responsible for:

- registered count
- remaining seats
- waiting count
- capacity calculations

---

### Email Service

Responsible for:

- waiting list emails
- promotion emails

---

## Data Model

### Events

Stores event configuration.

Fields include:

- title
- Contact Form ID
- waiting list enabled

---

### Event Batches

Each event contains one or more batches.

Fields include:

- event_id
- batch_number
- capacity
- status

---

### Registrations

Each registration stores:

- event
- batch (nullable)
- status
- complete submitted form data

Waiting registrations have:

```
batch_id = NULL
status = waiting
```

---

## Registration Workflow

### Registration Available

```
Submit Form

↓

Open Batch

↓

Seats Available

↓

Registered
```

---

### Batch Full

```
Submit Form

↓

Batch Full

↓

Waiting List Enabled

↓

Waiting
```

---

### No Active Batch

```
Submit Form

↓

No Open Batch

↓

Waiting List Enabled

↓

Waiting
```

---

### New Batch Opened

```
Admin Opens Batch

↓

Waiting Registrations

↓

Oldest First

↓

Assigned to Batch

↓

Registered
```

---

## Contact Form Support

FormFlow stores every field submitted through Contact Form 7.

Supported field types include:

- Text
- Email
- Telephone
- Textarea
- Select
- Radio
- Checkbox
- Acceptance
- Date
- Number
- URL
- File Upload

Submission data is preserved as JSON for future exports and reporting.

---

## Requirements

- WordPress 6.x+
- PHP 7.4+
- Contact Form 7

---

## Roadmap

### Completed

- Event management
- Batch architecture
- Automatic waiting lists
- Batch promotion
- Capacity management
- Waiting list emails
- Promotion emails
- Dashboard
- Registration viewer

### Planned

- Registration filters
- CSV export
- Search registrations
- Batch analytics
- Email templates
- Promotion email toggle
- Registration notes
- Event reporting
- REST API
- Gutenberg blocks

---

## License

GPL v2 or later.