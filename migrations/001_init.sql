CREATE TABLE users (
  id INTEGER PRIMARY KEY,
  username TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clients (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL,
  hourly_rate_cents INTEGER NOT NULL,
  color TEXT NOT NULL DEFAULT '#864322',
  archived INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE time_entries (
  id INTEGER PRIMARY KEY,
  client_id INTEGER NOT NULL REFERENCES clients(id),
  started_at TEXT,
  ended_at TEXT,
  minutes INTEGER NOT NULL,
  entry_date TEXT NOT NULL,
  notes TEXT,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE billable_expenses (
  id INTEGER PRIMARY KEY,
  client_id INTEGER NOT NULL REFERENCES clients(id),
  amount_cents INTEGER NOT NULL,
  description TEXT NOT NULL,
  expense_date TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE business_expenses (
  id INTEGER PRIMARY KEY,
  amount_cents INTEGER NOT NULL,
  description TEXT NOT NULL,
  category TEXT,
  expense_date TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE active_timer (
  id INTEGER PRIMARY KEY CHECK (id = 1),
  client_id INTEGER NOT NULL REFERENCES clients(id),
  started_at TEXT NOT NULL,
  notes TEXT
);

CREATE INDEX idx_time_entries_client_date ON time_entries(client_id, entry_date);
CREATE INDEX idx_billable_client_date ON billable_expenses(client_id, expense_date);
CREATE INDEX idx_business_date ON business_expenses(expense_date);
