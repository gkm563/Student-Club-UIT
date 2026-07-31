# 🚀 USC UIT — United Student Club Official Platform

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap 5](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)

**USC UIT (United Student Club — UIT)** is the central governing body and digital ecosystem for all student activities, chapters, technical hackathons, cultural fests, and co-curricular leadership at the **United Institute of Technology (UIT Prayagraj)**.

Under one unified identity, USC UIT governs all campus student organizations across two primary parent wings:
1. **Developers Club UIT** — Official Technical Umbrella Council (GDG Cloud, GFG SC, Gemini Builders, FOSS, CodeCrush, etc.)
2. **Cultural Club UIT** — Official Cultural Umbrella Council (Nritya Dance, Harmony Music, Toastmasters, Theatre, Media, LitSoc, etc.)

---

## 🌐 Production & Repository Links

- **GitHub Repository**: [https://github.com/gkm563/Student-Club-UIT](https://github.com/gkm563/Student-Club-UIT)
- **Institution**: United Institute of Technology (UIT Prayagraj)

---

## ✨ Core Platform Features

### 1. 🏛️ Dual Parent Wing Architecture
- **Developers Club UIT (`/developers-club.html`)**: Dedicated portal for technical chapters, coding bootcamps, AI study jams, and hackathons.
- **Cultural Club UIT (`/cultural-club.html`)**: Dedicated portal for performing arts, musical bands, theatrical drama, literary debates, and public speaking arenas.

### 2. 🔍 Public Student Discovery Portal
- **Instant Live Search**: Debounced search matching student chapters, GDG, GFG, hackathons, and cultural fests.
- **Campus Events Directory**: Central calendar featuring ongoing, upcoming, and past campus events with outcomes, venue details, and registration links.
- **Executive Governance & Leadership**: Roster displaying the Advisory Board, Principal, Dean Student Welfare, Faculty Coordinators, and Student Council Leads.
- **Glassmorphic UI Design System**: Apple-inspired glassmorphism, dynamic color accents, micro-animations, and responsive layouts.

### 3. ⚡ Club Governance & Administration
- **Scoped Management Panels**: Secure portals for chapter leads to manage event schedules, recruitment status, and member rosters.
- **Super-Admin Governance Console**: System analytics, institutional KPIs, chapter onboarding, and security audit trail logging.

---

## 🛠️ Technology Stack

- **Frontend**: HTML5, Vanilla CSS3 (Custom Design Tokens + Glassmorphism), JavaScript (ES6+), Bootstrap 5.3, Bootstrap Icons
- **Backend**: PHP 8.0+ (RESTful JSON API endpoints & MVC routing)
- **Database Architecture**: MySQL / MariaDB with PDO prepared statements & SQLite fallback (`config/database.php`)
- **Security**: Bcrypt password hashing, session regeneration, CSRF protection, output escaping, and audit logging.

---

## 📂 Project Structure

```
Student-Club-UIT/
├── developers-club.html       # Developers Club UIT (Technical Umbrella Council)
├── cultural-club.html         # Cultural Club UIT (Cultural Umbrella Council)
├── index.html                 # Main USC UIT Home Portal
├── clubs.html                 # Chapter Directory & Two-Wing Filter
├── club-detail.html           # Individual Sub-Chapter Profile Page
├── events.html                # Campus Events & Activities Directory
├── tech-events.html           # Technical Events Filtered Page
├── cultural-events.html       # Cultural Events Filtered Page
├── about.html                 # Institutional Governance & Advisory Board
├── contact.html               # Secretariat Contact & Helpdesk
├── config/
│   └── database.php           # PDO Singleton with MySQL & SQLite fallback
├── api/                       # RESTful JSON endpoints (clubs, events, committee)
│   ├── clubs.php
│   ├── events.php
│   └── committee.php
├── assets/
│   ├── css/
│   │   ├── style.css          # Core Design Tokens & UI Utilities
│   │   ├── home.css           # Landing Page & Glassmorphism Styling
│   │   └── wing-pages.css     # Developers & Cultural Club Theme Styles
│   ├── js/                    # Search, rendering, and layout loaders
│   └── img/
│       ├── campus/            # High-res UIT Campus Photography
│       └── committee/         # Official Executive Leadership Roster Images
├── admin/                     # Club & Super-Admin Management Portals
├── includes/                  # Header, Footer, and Navigation Components
└── database/                  # Schema DDL and Seeding Scripts
```

---

## 🚀 Local Installation & Setup

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/gkm563/Student-Club-UIT.git
   cd Student-Club-UIT
   ```

2. **Initialize Database**:
   Run the automated database initializer (uses MySQL if available, or auto-generates SQLite):
   ```bash
   php setup.php
   ```

3. **Start Development Server**:
   ```bash
   php -S localhost:8000
   ```
   Navigate to `http://localhost:8000` in your web browser.

---

## 👤 Author & Lead Architect

**Gautam Kumar Maurya (GKM563)**  
*Full-Stack Lead Architect*  
- **GitHub**: [@gkm563](https://github.com/gkm563)  
- **Repository**: [https://github.com/gkm563/Student-Club-UIT](https://github.com/gkm563/Student-Club-UIT)  
- **Institution**: United Institute of Technology (UIT Prayagraj)

---

## 📄 License

This project is licensed under the [MIT License](LICENSE) - open for educational and institutional deployment.
