# MADWAR Burger & More — VS Code Project

## Project files
- `index.html` — MADWAR customer website and demo dashboards.
- `madwar_database.sql` — MySQL database schema and sample menu items.

## Open the website
1. Extract this project folder.
2. Open the folder in Visual Studio Code.
3. Open `index.html`.
4. Use the VS Code Live Server extension, or double-click `index.html` to open it in a browser.

## Demo login accounts
- Admin: `admin` / `admin123`
- Staff: `staff` / `staff123`
- Customer: create an account using the website's Log in window.

## Important prototype notes
The current `index.html` is a front-end prototype. It stores demo data in the browser's local storage. Different browsers/devices do not share data, and the demo passwords are not secure for real deployment.

The SQL file defines a MySQL database, but the HTML prototype does not connect to it yet. To make a real shared online system, add a PHP backend that connects to MySQL, hashes passwords, checks roles on the server, and validates customer ownership of orders.

## Import the database with XAMPP
1. Start MySQL in XAMPP.
2. Open `http://localhost/phpmyadmin`.
3. Select **Import**.
4. Choose `madwar_database.sql`.
5. Click **Import** / **Go**.

Database name: `madwar_db`

## Roles planned
- Customer: register/login, browse menu, cart, place orders, choose pickup time, track status.
- Staff: add/edit/delete/hide menu items, manage orders and pickup times, view daily completed sales.
- Admin: create/delete staff accounts and view inventory.

For a production system, enforce these permissions in PHP/server-side code. Hiding dashboard links in the browser alone is not security.
