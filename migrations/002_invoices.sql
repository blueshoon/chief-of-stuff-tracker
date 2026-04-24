CREATE TABLE invoices (
  id INTEGER PRIMARY KEY,
  client_id INTEGER NOT NULL REFERENCES clients(id),
  invoice_number TEXT NOT NULL UNIQUE,
  period_start TEXT NOT NULL,
  period_end TEXT NOT NULL,
  issue_date TEXT NOT NULL,
  due_date TEXT,
  notes TEXT,
  status TEXT NOT NULL DEFAULT 'draft',
  hours_minutes_total INTEGER NOT NULL DEFAULT 0,
  hours_amount_cents INTEGER NOT NULL DEFAULT 0,
  expenses_amount_cents INTEGER NOT NULL DEFAULT 0,
  total_cents INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT
);

CREATE TABLE invoice_line_items (
  id INTEGER PRIMARY KEY,
  invoice_id INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
  kind TEXT NOT NULL,
  description TEXT NOT NULL,
  line_date TEXT,
  quantity REAL,
  unit_cents INTEGER,
  amount_cents INTEGER NOT NULL,
  time_entry_id INTEGER REFERENCES time_entries(id) ON DELETE SET NULL,
  billable_expense_id INTEGER REFERENCES billable_expenses(id) ON DELETE SET NULL,
  sort_order INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE invoice_payments (
  id INTEGER PRIMARY KEY,
  invoice_id INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
  amount_cents INTEGER NOT NULL,
  payment_date TEXT NOT NULL,
  method TEXT,
  reference TEXT,
  notes TEXT,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE time_entries       ADD COLUMN invoice_id INTEGER REFERENCES invoices(id) ON DELETE SET NULL;
ALTER TABLE billable_expenses  ADD COLUMN invoice_id INTEGER REFERENCES invoices(id) ON DELETE SET NULL;

CREATE INDEX idx_invoices_client       ON invoices(client_id);
CREATE INDEX idx_invoices_period       ON invoices(period_start, period_end);
CREATE INDEX idx_invoices_status       ON invoices(status);
CREATE INDEX idx_line_items_invoice    ON invoice_line_items(invoice_id);
CREATE INDEX idx_payments_invoice      ON invoice_payments(invoice_id);
CREATE INDEX idx_time_entries_invoice  ON time_entries(invoice_id);
CREATE INDEX idx_billable_invoice      ON billable_expenses(invoice_id);
