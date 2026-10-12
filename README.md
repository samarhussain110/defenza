<div align="center">

# 🛡️ DEFENZA
### Web-Based Women's Emergency Response & Safety System

![PHP](https://img.shields.io/badge/PHP-7.4%20%7C%208.x-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6%2B-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

<p align="center">
  <b>Defenza</b> is an automated emergency alert web application engineered to bridge the critical time gap in personal safety situations. With a single action, Defenza captures real-time GPS coordinates and dispatches automated distress notifications to designated emergency contacts.
</p>

</div>

---

## 📌 Key Features

- 🚨 **One-Tap SOS Emergency Trigger:** Instantly activates emergency protocol with minimal user interaction.
- 📍 **Real-Time GPS Geolocation:** Automatically captures accurate latitude and longitude using HTML5 Geolocation API.
- 📩 **Automated Alert Dispatch:** Sends immediate emergency notification emails/alerts containing exact location details.
- 👥 **Emergency Contacts Management:** Users can register, edit, and prioritize emergency contact lists.
- 📊 **Admin Monitoring Dashboard:** Dedicated real-time dashboard for authorities/admins to track active distress events.
- 🔐 **Secure Authentication:** User data protection through password hashing and secure session management.
- 📱 **Responsive Glassmorphism UI:** Clean, fast-loading, mobile-friendly interface designed for rapid emergency use.

---

## 🛠️ Technology Stack

- **Backend:** PHP (Session handling, RESTful logic, notification engine)
- **Database:** MySQL / MariaDB (Relational data structure for users, logs, and contacts)
- **Frontend:** HTML5, CSS3, JavaScript (ES6+), AJAX
- **APIs & Libraries:** HTML5 Geolocation API, PHPMailer / Mail Service API

---

## 🏗️ System Architecture

```text
+-------------------+        1. Trigger SOS        +-----------------------+
|                   | ---------------------------> |                       |
|   User Browser    |                              |   JavaScript / AJAX   |
| (Client Interface)| <--------------------------- |                       |
+-------------------+      4. UI Response Confirmation +-----------------------+
                                                               |
                                                       2. Pass GPS & User ID
                                                               v
+-------------------+      3b. Send Mail/Alerts    +-----------------------+
|  Emergency Mail/  | <--------------------------- |                       |
|   Notification    |                              |  PHP Backend Engine   |
+-------------------+                              |                       |
                                                   +-----------------------+
                                                               |
                                                       3a. Insert Alert Log
                                                               v
                                                   +-----------------------+
                                                   |    MySQL Database     |
                                                   +-----------------------+
