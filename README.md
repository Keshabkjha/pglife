# PGLife | Professional PG Booking System 🛏️

A state-of-the-art, secure, containerized, and deployment-ready PG booking application built with PHP, MySQL, Apache, and Bootstrap. Designed to cater to students and working professionals.

---

## Key Features & Enhancements

### 🔒 Enterprise-Grade Security
* **SQL Injection Prevention**: Prepared statements utilized across all database operations.
* **XSS Mitigation**: Contextual escaping of all dynamic parameters using `htmlspecialchars()`.
* **CSRF Protection**: Secure cryptographically strong anti-CSRF tokens validating all forms (login, signup, profile updates) and AJAX operations (book/cancel actions).
* **Session Safety**: Regeneration of session IDs (`session_regenerate_id(true)`) upon successful authentication to block Session Fixation attacks.
* **BCrypt Hashing**: Modern, secure password storage replacing outdated hash algorithms.

### 🐳 Modern Infrastructure & Local Mail Trap
* **Dockerized Environment**: Full containerization using Docker Compose (Apache, PHP 8.2, MySQL 8.0).
* **Mailhog SMTP Trap**: Integrates a local mock SMTP server (Mailhog) to catch registration OTPs and transactional welcome emails at `http://localhost:8025` without external API dependencies.
* **Socket SMTP Mailer**: Implemented custom SMTP socket connection protocols for rapid, secure email dispatch.

### 🌟 Premium User Experience (UX/UI)
* **Real-time Finder Filters**: Instant client-side text keyword searches, double-handle rent range sliders, and multi-amenity checkboxes.
* **Instant Client-side Ordering**: Instantly toggle ordering (highest/lowest rent first) dynamically without full-page reloads.
* **Interactive Maps**: Integrates Leaflet.js interactive maps on details pages.
* **Ratings & Reviews System**: Real-time review submissions with dynamic client-side list rendering.
* **Booking Cancellation**: Easy cancellations with smooth CSS transform & fade scale animations.
* **Glassmorphism Backdrop Blurs**: Applied backdrop blur filters to modal dialog overlays for a modern feel.
* **Real-time Navigation Badges**: Interactive counts for interested and booked properties displayed in the header.

---

## Tech Stack
* **Web Server**: Apache HTTP Server (Dockerized)
* **Backend**: PHP 8.2 with `mysqli` extension
* **Database**: MySQL 8.0 (Dockerized)
* **Mail Server**: Mailhog (Dockerized mock SMTP trap)
* **Frontend**: HTML5, Vanilla CSS3, Javascript (ES6), Bootstrap 4, FontAwesome 5, Leaflet.js

---

## Getting Started

### Prerequisites
* [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running.

### Spin up the Containers
Run the following command in the root directory to spin up the server, database, and SMTP services:
```bash
docker compose up -d
```

### Access Ports
* **Web Application**: [http://localhost:8080/home.php](http://localhost:8080/home.php)
* **Mailhog Web Interface**: [http://localhost:8025](http://localhost:8025)
* **MySQL Database**: Mapped to host port `3307`

---

## Seed Accounts for Testing

All test accounts share the password: `password`

1. **Keshabkjha**
   * **Email**: `keshab@example.com`
2. **Ritesh Thakur**
   * **Email**: `ritesh@example.com`
3. **Puneet Pandey**
   * **Email**: `puneet@example.com`

---

## Author
* **@[keshabkjha](https://github.com/Keshabkjha)**
