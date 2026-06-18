# PGLife — Find Your Perfect PG 🏠

> **Industry-ready PG (Paying Guest) accommodation finder for India, built with PHP, MySQL, Leaflet Maps, and Bootstrap.**

[![PHP](https://img.shields.io/badge/PHP-8.x-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange.svg)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-4.x-purple.svg)](https://getbootstrap.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED.svg)](https://docker.com)

---

## ✨ Features

### For PG Seekers
- 🔍 **Smart Search** — Search PGs by city name across **11 major Indian cities**
- 🏠 **Advanced Filters** — Filter by gender preference, max rent slider, and **13 amenities** (Wifi, AC, Power Backup, CCTV, Geyser, Parking & more)
- 📊 **Sort** — Sort listings by highest / lowest rent
- ❤️ **Interested** — Save properties you're interested in
- 📅 **Book Now** — Book a PG directly from the detail page
- ⭐ **Reviews** — Read and write user reviews with star ratings
- 🗺️ **Live Map** — OpenStreetMap/Nominatim geocoding shows exact PG location
- 👤 **Dashboard** — View booked and interested properties

### For PG Owners
- 🏢 **List PG** — Create full property listing with address, amenities, images, rent, and gender preference
- 📈 **Analytics Dashboard** — Track Views, Bookings, and Average Ratings per property
- 👥 **Tenant Management** — View all booking requests with seeker name, email & phone
- 💬 **Review Monitoring** — Read all reviews submitted for your properties

### Platform Features
- 📧 **OTP Email Verification** — Secure signup with email OTP
- 🎉 **Welcome Email** — Warm welcome mail on account creation
- 🔒 **CSRF Protection** — All API endpoints protected with CSRF tokens
- 🔄 **Smooth Page Transitions** — Fade-in/out animations on every navigation
- 🍞 **Toast Notifications** — No more browser alert() popups — polished toast system
- 📱 **Fully Responsive** — Works seamlessly on mobile, tablet and desktop
- 🌐 **Role-based Auth** — Separate Seeker & Owner profiles

---

## 🌆 Cities Covered

Delhi • Mumbai • Bengaluru • Hyderabad • Kolkata • Chennai • Pune • Ahmedabad • Jaipur • Noida • Gurgaon

---

## 🚀 Quick Start (Docker)

```bash
# Clone the repository
git clone https://github.com/Keshabkjha/pglife.git
cd pglife

# Start all services (PHP + MySQL + Mailhog)
docker-compose up -d

# Open in browser
http://localhost:8080
```

> **Mail testing:** Access Mailhog at `http://localhost:8025` to see OTP emails during development.

---

## 🛠️ Manual Setup

### Requirements
- PHP 8.x with `mysqli` extension enabled
- MySQL 8.0+
- Apache / Nginx web server

### Steps
1. Copy all files to your web server's root (e.g. `htdocs/pglife`)
2. Create a MySQL database named `pglife`
3. Import `database/pglife.sql`
4. Update `includes/database_connect.php` with your DB credentials
5. Open `http://localhost/pglife/home.php`

---

## 📁 Project Structure

```
pglife/
├── api/                  # AJAX API endpoints (signup, login, booking, review, etc.)
├── css/                  # Stylesheets (common, home, property_list, property_detail, dashboard)
├── database/             # MySQL schema + seed data
├── img/                  # City images, property images, amenity icons
├── includes/             # PHP includes (header, footer, modals, database connect)
├── js/                   # JavaScript files (common, home, property_list, property_detail, dashboard)
├── docker-compose.yml    # Docker setup
├── Dockerfile            # PHP + Apache image
├── home.php              # Home page
├── property_list.php     # City PG listing page
├── property_detail.php   # Single property detail + map + reviews
└── dashboard.php         # User/Owner dashboard
```

---

## 🔐 Security

- Prepared statements (PDO/MySQLi) — SQL injection protected
- Password hashing with `password_hash()` (bcrypt)
- CSRF token on all POST/state-changing actions
- Email OTP verification on signup
- Session-based authentication
- Input validation and output escaping (`htmlspecialchars`)

---

## 👤 Author

**[@Keshabkjha](https://github.com/Keshabkjha)**

---

## 📄 License

© 2026 Keshabkjha. All rights reserved.
