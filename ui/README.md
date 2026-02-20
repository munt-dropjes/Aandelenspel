# 📈 Aandelen Spel - Frontend Dashboard

A real-time stock market simulation dashboard built with **Vue 3** and **Vite**. This application serves as the interface for a scouting/group game where companies compete for the highest Net Worth through completing tasks and trading stocks.

## 🚀 How it Works

This application is **API-Driven** but acts as the **Heartbeat** of the game.

1. **Authentication:** Users (Admins/Game Masters) login via JWT.
2. **The Heartbeat:** Once logged in, the frontend triggers a "Snapshot" event (`/api/history/save`) every **60 seconds**.
3. **Live Updates:**
* **Graph:** Fetches historical data points (synchronized to UTC) to visualize company performance over time.
* **Stocks:** Displays a live matrix of share ownership and bank availability.
* **Tasks:** Allows dynamic input of task results, automatically calculating ranks and financial rewards on the server.



---

## 🛠️ Installation

### 1. Prerequisites

* Node.js (v16 or higher)
* npm (Node Package Manager)
* A running instance of the **Game Economy Backend**.

### 2. Setup

Clone the repository and install dependencies:

```bash
# Go into the project directory
cd webdev2-FE

# Install dependencies
npm install

```

### 3. Configuration

The application relies on environment variables to connect to the backend.

1. Create a `.env` file by copying the example:
```bash
cp .env.example .env

```


2. Open `.env` and configure your API URL.
   **Important:** You must include the protocol (`http://` or `https://`).
```properties
# .env
VITE_API_URL=http://localhost

```



### 4. Run Development Server

Start the local development server:

```bash
npm run dev

```

The application will be available at: `http://localhost:5173`

---

## 📡 Backend API Integration

The frontend communicates with the backend via the `src/services/api.js` service. Below are the key endpoints used by the application components.

### 🔐 Authentication

| Method | Endpoint | Description | Component |
| --- | --- | --- | --- |
| `POST` | `/login` | Authenticates user & returns JWT Token. | `Login.vue` |

### ⚙️ Game Engine & History

| Method | Endpoint | Description | Component |
| --- | --- | --- | --- |
| `GET` | `/api/companies` | Fetches list of companies, cash, and current stock price. | `useGameEngine.js` |
| `POST` | `/api/history/save` | **The Heartbeat.** Triggers the backend to save the current game state (every 60s). | `useGameEngine.js` |
| `GET` | `/api/history/{dateTime}` | Fetches graph history points since a specific UTC timestamp. | `useGameEngine.js` |

### 📊 Stocks & Trading

| Method | Endpoint | Description | Component |
| --- | --- | --- | --- |
| `GET` | `/api/stocks` | Returns the ownership matrix (who owns whom). | `StockDivider.vue` |
| `GET` | `/api/stocks/bank` | Returns stocks available for purchase from the Bank. | `StockDivider.vue` |
| `POST` | `/api/stocks/trade` | Executes a trade. Supports Peer-to-Peer and Bank-to-Peer. | `TradeModal.vue` |

### 📝 Tasks & Economy

| Method | Endpoint | Description | Component |
| --- | --- | --- | --- |
| `GET` | `/api/tasks` | Fetches available tasks, rules, and categories dynamically. | `Tasks.vue` |
| `POST` | `/api/tasks/complete` | Submits a result. Backend calculates rank (1st/2nd/3rd) and reward. | `Tasks.vue` |

---

## 📂 Project Structure

* **`src/components/`**: UI Building blocks.
* `Graph.vue`: Chart.js implementation (Optimized for performance).
* `StockDivider.vue`: Matrix view of share ownership.
* `Tasks.vue`: Dynamic task administration interface.
* `TradeModal.vue`: Popup for buying/selling stocks.


* **`src/composables/`**:
* `useGameEngine.js`: Contains the central game loop, state management, and data fetching logic.


* **`src/services/`**:
* `api.js`: Centralized fetch wrapper with JWT handling and Error parsing.



---

## ❗ Troubleshooting

**Q: The application tries to connect to `localhost:5173/localhost/api/...`?**
A: You are missing the protocol in your `.env` file. Change `VITE_API_URL=localhost` to `VITE_API_URL=http://localhost`. Restart the server after changing this file.

**Q: The Graph is empty?**
A: Ensure the Backend is running and connected to a database. The graph requires at least one "Snapshot" to be saved via the heartbeat. Wait 1 minute or check the browser console for errors.
