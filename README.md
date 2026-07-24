# 🚀 ClubHub - College Club Management System (CCMS)

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Vercel Deployment](https://img.shields.io/badge/Vercel-Live-success?logo=vercel&logoColor=white)](https://student-club-uit.vercel.app)
[![Bootstrap 5](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)

**ClubHub (College Club Management System - CCMS)** is a centralized, mobile-responsive, and security-hardened web platform engineered to serve as the official digital home for all campus student organizations at the **University Institute of Technology (UIT)**.

It connects students, club leaders, and college administrators seamlessly through a public discovery portal, self-service club admin panels, and a super-admin governance console with live institutional analytics.

---

## 🌐 Live Production Links

- **Live Website**: [https://student-club-uit.vercel.app](https://student-club-uit.vercel.app)
- **GitHub Repository**: [https://github.com/gkm563/Student-Club-UIT](https://github.com/gkm563/Student-Club-UIT)

---

## ✨ Key Features & Highlights

### 1. 🔍 Public Discovery Portal (Students & Visitors)
- **Instant Debounced Search**: Fast server-side and AJAX-debounced search bar matching club names, short codes, taglines, and domains.
- **Categorized Club Directory**: Split into **Technical & Coding**, **Cultural & Performing Arts**, and **Sports & Special Interest** domains with live category pills and status filters (*Recruiting Now*, *Active*).
- **Club Mini-Site Profiles**: Dedicated profiles (`/clubs/geeksforgeeks`) displaying tagline, established year, mission, vision, objectives, achievements timeline, leadership roster, and recruitment CTA.
- **Campus Events Calendar**: Global calendar featuring upcoming, ongoing, and completed events with venue details and direct registration links.
- **Activity Blog Feed**: Cross-club update feed featuring workshop recaps, announcements, and tag filters.
- **Campus Leadership Roster**: Unified accountability directory displaying Faculty Advisors, Presidents, Vice Presidents, and Secretaries across all active clubs.
- **Spam-Protected Contact & FAQ**: Contact form protected with honeypot fields, rate-limiting, and structured accordion FAQs.
- **Modern Apple-Inspired UI**: Glassmorphic headers, card hover micro-animations, statistics counter cards, and persistent Light/Dark theme toggles.

### 2. ⚡ Self-Service Club Admin Panel
- **Club Dashboard**: Scoped metrics view for assigned club leads showing event counts, activity updates, officer roster size, and profile completion.
- **Profile & Recruitment Manager**: Self-service editor to update mission, vision, objectives, social media links, recruitment open/closed toggles, and application links.
- **Event Manager**: Full CRUD interface to schedule, edit, and publish campus events.
- **Activity Blog Publisher**: Publish news and updates directly to the global student activity feed.
- **Roster & Officer Manager**: Manage leadership records, faculty advisors, and executive team members.

### 3. 🛡️ Super Admin Governance Console
- **Institutional Analytics**: High-level KPIs and **Chart.js** data visualizations displaying club distribution across categories and campus activity trends.
- **Club Onboarding & Lifecycle**: Onboard new clubs, assign domains, and soft-delete/restore existing clubs without data loss.
- **Account Provisioning**: Create Club Admin accounts, reset credentials, and assign single-club permissions.
- **Security Audit Governance**: Immutable security audit trail logging administrative logins, profile modifications, and user creation events with IP addresses and timestamps.

---

## 🛠️ Technology Stack & Architecture

- **Frontend**: HTML5, CSS3 (Vanilla Custom Tokens + Glassmorphism), JavaScript (ES6+), Bootstrap 5.3, Bootstrap Icons, Chart.js
- **Backend Architecture**: PHP 8.0+ (MVC pattern), RESTful AJAX API endpoints
- **Database Engine**: MySQL / MariaDB with PDO prepared statements & seamless SQLite fallback (`config/database.php`)
- **Security Hardening**:
  - Bcrypt password hashing (`password_hash()`)
  - Session ID regeneration & HttpOnly cookie protection
  - CSRF token generation and server-side verification
  - Output escaping (`e()`) for XSS prevention
  - Honeypot spam defense on contact forms
  - Security audit logging (`audit_logs`)

---

## 📂 Project Structure

```
c:\xampp\htdocs\UIT\
├── config/
│   └── database.php           # PDO Singleton with MySQL & SQLite fallback
├── includes/
│   ├── auth.php               # Session security, RBAC & CSRF middleware
│   ├── functions.php          # Global helpers, UUID generator & audit logger
│   ├── header.php             # HTML head & CDN dependencies
│   ├── navbar.php             # Responsive navigation bar
│   └── footer.php             # Global footer template
├── database/
│   ├── schema.sql             # MySQL schema DDL
│   └── ccms.sqlite            # SQLite database file
├── public/
│   ├── index.html             # Standalone ClubHub landing page
│   ├── index.php              # Dynamic home page
│   ├── clubs.php              # Club directory & category filters
│   ├── club-detail.php        # Club mini-site profile
│   ├── events.php             # Events calendar
│   ├── activities.php         # Activity blog feed
│   ├── leadership.php         # Campus officer directory
│   ├── gallery.php            # Media showcase
│   ├── about.php              # About platform page
│   ├── contact.php            # Contact form & FAQ
│   ├── api/                   # RESTful JSON endpoints
│   │   ├── search.php
│   │   └── filter-clubs.php
│   └── assets/
│       ├── css/style.css      # Master design system stylesheet
│       └── js/                # Main utilities & debounced search scripts
├── admin/
│   ├── login.php              # Admin portal login
│   ├── logout.php             # Session destruction
│   ├── dashboard.php          # Club Admin dashboard
│   ├── profile.php            # Club profile editor
│   ├── events.php             # Event CRUD manager
│   ├── activities.php         # Activity post publisher
│   ├── members.php            # Roster manager
│   └── super/                 # Super Admin console
│       ├── index.php          # System analytics & Chart.js
│       ├── clubs.php          # Club onboarding
│       ├── users.php          # Account management
│       └── audit-logs.php     # Audit log viewer
├── setup.php                  # Automated database initializer & seeder
├── router.php                 # PHP CLI development web server router
├── vercel.json                # Vercel production deployment config
└── README.md                  # Project documentation
```

---

## 🚀 Getting Started & Local Installation

### Prerequisites
- **PHP**: 8.0 or higher
- **Web Server**: Apache / Nginx or PHP Built-in Server
- **Database**: MySQL / MariaDB (XAMPP recommended) or SQLite
- **Git**: Installed on system

### Installation Steps

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/gkm563/Student-Club-UIT.git
   cd Student-Club-UIT
   ```

2. **Initialize Database & Seed Sample Data**:
   Ensure MySQL is running (or let SQLite auto-generate):
   ```bash
   php setup.php
   ```

3. **Start Development Web Server**:
   ```bash
   php -S localhost:8000 router.php
   ```

4. **Access the Website**:
   Open your browser and navigate to `http://localhost:8000`.

---

## 🔑 Demo Credentials

| Role | Email | Password | Access Scope |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@uit.edu` | `AdminPassword123!` | Full System Governance & Analytics |
| **Club Admin** | `geeksforgeeks@uit.edu` | `ClubPassword123!` | GeeksforGeeks Chapter Admin Scope |

---

## 👤 Author & Maintainer

**Gautam Kumar Maurya (GKM563)**  
*Lead Architect & Full-Stack Developer*  
- **GitHub**: [@gkm563](https://github.com/gkm563)  
- **Repository**: [https://github.com/gkm563/Student-Club-UIT](https://github.com/gkm563/Student-Club-UIT)  
- **Live Platform**: [https://student-club-uit.vercel.app](https://student-club-uit.vercel.app)  
- **Institution**: University Institute of Technology (UIT)

---

## 📄 License

This project is licensed under the [MIT License](LICENSE) - feel free to use, modify, and distribute for educational and institutional purposes.
