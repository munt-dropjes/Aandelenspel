# 📈 Aandelen Spel - Full Stack Game Economy

A real-time stock market and economy simulation built for a scouting/group game. This application features a **Vue 3** frontend dashboard powered by a **Vanilla PHP** and **MySQL** backend.

Teams (Companies) compete for the highest Net Worth by completing high-stakes tasks, trading shares, and negotiating offers in a dynamic, living economy.

---

## 🌟 Game Mechanics & Features

* **Companies:** (Teams/Groups) have **Cash** and own **Stocks**.
    * **Privacy:** A company's Cash balance is **private**. Only Admins and the company owner can see it. Other players only see Net Worth.
* **Stock Valuation:**
    * **Stocks** are shares of companies. The price of a stock is dynamic, calculated based on the target company's **Net Worth**.
    * **Net Worth** = `Cash + (Portfolio Value)`.
    * **Stock Price** = `(Net Worth / 100)`.
    * *Buying stocks increases your Net Worth, but decreases your Cash (and therefore your own Stock Price), creating a strategic trade-off.*
* **One-Shot Task System:**
    * Companies complete tasks to earn cash.
    * **High Stakes:** A company has **only one attempt** per task.
    * **Success:** Earns the tiered reward (1st place gets P1, 2nd gets P2, etc.).
    * **Failure:** The company pays a **Penalty** (deducted from cash) and is **blocked** from retrying.
    * **Fairness:** Failed attempts do *not* consume a Rank slot. If Company A fails, Company B can still claim the 1st Place reward.
* **History:** The system records minute-by-minute snapshots of company values for historical graphing.
* **Authentication:** Users (Admins/Game Masters) login via JWT.
* **The Heartbeat:** Once logged in, the frontend triggers a "Snapshot" event (`/api/history/save`) every **60 seconds**.
* **Live Updates:**
  * **Graph:** Fetches historical data points (synchronized to UTC) to visualize company performance over time.
  * **Stocks:** Displays a live matrix of share ownership and bank availability.
  * **Tasks:** Allows dynamic input of task results, automatically calculating ranks and financial rewards on the server.

---

## 🛠 Tech Stack

**Frontend (UI)**
* Vue 3 & Vite
* Chart.js (for performance-optimized live graphing)
* Bootstrap 5

**Backend (API)**
* PHP 8.2 (Vanilla, No Framework)
* MySQL 8.0 / MariaDB
* `bramus/router` (Routing)
* `firebase/php-jwt` (Authentication)

**Infrastructure**
* Docker & Docker Compose (Nginx, PHP-FPM, Node, MariaDB)

---

## 🚀 Quick Start & Installation

Because this project is a monorepo, both the frontend and backend spin up simultaneously using a single Docker command.

### 1. Setup
Copy the example environment file (if you haven't already):
`cp .env.example .env`

### 2. Start the Application
Run the following command to build the containers, install dependencies, and start the application:
`docker-compose up -d --build`

### 3. Access the Game
* **Frontend Dashboard:** `http://localhost:5173`
* **Backend API:** `http://localhost/api`
* **Database Management (PhpMyAdmin):** `http://localhost:8081`

**Default Logins:**
On the first run, the database automatically initializes with default data:
* **Admin User:** `StockMaster` (Password: `password123`)
* **Default Companies:** Haviken, Spechten, Sperwers, Zwaluwen, Valken

---

## 📂 Monorepo Structure

* `/api` - The Vanilla PHP backend application.
* `/ui` - The Vue 3 frontend application.
* `/sql` - Database initialization scripts (`setup.sql`).
* `docker-compose.yml` - Master infrastructure file.

---

## 📚 API Endpoints

### 🔐 Auth & System

| Method | Endpoint       | Description                         |
|--------|----------------|-------------------------------------|
| `POST` | `/login`       | Authenticate and receive JWT token. |
| `GET`  | `/ping`        | Public health check.                |
| `GET`  | `/diagnostics` | System diagnostics.                 |

### 🏢 Companies & History

| Method | Endpoint              | Description                                                                                           |
|--------|-----------------------|-------------------------------------------------------------------------------------------------------|
| `GET`  | `/api/companies`      | List all companies with live Net Worth & Stock Price. Cash is hidden for non-owners. Prices are live. |
| `GET`  | `/api/companies/{id}` | Get details for a specific company.                                                                   |
| `POST` | `/api/history/save`   | Trigger a valuation snapshot (Heartbeat).                                                             |
| `GET`  | `/api/history/{date}` | Get valuation history since `{date}`. Date must be URL Encoded Y-m-d H:i:s.                           |

### ✅ Tasks

| Method | Endpoint              | Description                                                                                                                      |
|--------|-----------------------|----------------------------------------------------------------------------------------------------------------------------------|
| `GET`  | `/api/tasks`          | List all tasks with their category, rewards, and completion status. Response includes finished_by (winners) and failed (losers). |
| `POST` | `/api/tasks/complete` | Submit a task attempt. Irreversible. Success automatically assigns rank and reward.; Failure yields penalty + block.             |

Payload (Success):
```JSON
{
"company_id": 1,
"task_id": 5,
"success": true
}
```
Payload (Failure/Penalty):
```JSON
{
"company_id": 1,
"task_id": 5,
"success": false
}
```

### 📈 Stocks & Trading

| Method | Endpoint                   | Description                                                |
|--------|----------------------------|------------------------------------------------------------|
| `GET`  | `/api/stocks`              | View all active shares owned by companies.                 |
| `GET`  | `/api/stocks/bank`         | View shares owned by the Bank.                             |
| `GET`  | `/api/stocks/company/{id}` | View a specific company's portfolio.                       |
| `POST` | `/api/stocks/trade`        | Buy/Sell stocks. Checks for sufficient Cash & Stock funds. |

**Payload:**
```JSON
{
  "buyer_id": 1,
  "seller_id": null,
  "stock_company_id": 2,
  "amount": 10
}
```
*(Use `seller_id: null` to buy from the Bank)*

### 💸 Transactions

| Method | Endpoint                     | Description                                                    |
|--------|------------------------------|----------------------------------------------------------------|
| `GET`  | `/api/transactions`          | View transaction history. Admins see all; Users see their own. |
| `POST` | `/api/transactions`          | Create a manual transaction (Admin only).                      |
| `POST` | `/api/transactions/transfer` | Transfer money from one company to another company.            |

**Payload:**
```JSON
{
  "sender_id": 1,
  "receiver_id": 2,
  "amount": 1000,
  "description": "Secret deal transfer"
}
```

### 🤝 Trade Offers (Negotiations)

| Method | Endpoint                   | Description                                                                                   |
|--------|----------------------------|-----------------------------------------------------------------------------------------------|
| `GET`  | `/api/offers/pending`      | View all incoming pending offers for the authenticated company.                               |
| `POST` | `/api/offers`              | Propose a trade to another company. Funds and shares are not locked until accepted.           |
| `POST` | `/api/offers/{id}/accept`  | Accept a pending offer. Triggers Just-In-Time (JIT) validation and an atomic cash/share swap. |
| `POST` | `/api/offers/{id}/decline` | Decline a pending offer.                                                                      |

**Payload for Proposing an Offer:**
```JSON
{
  "seller_id": 2,
  "target_company_id": 2,
  "amount": 5,
  "total_price": 15000
}
```
*(In this example, the authenticated user is offering Company #2 $f$ 15,000 to buy 5 shares of Company #2's own stock).*
