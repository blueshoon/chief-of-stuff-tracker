CREATE TABLE business_settings (
  id INTEGER PRIMARY KEY CHECK (id = 1),
  business_name TEXT NOT NULL DEFAULT 'Chief of Stuff',
  contact_name TEXT,
  email TEXT,
  phone TEXT,
  address TEXT,
  payment_instructions TEXT,
  updated_at TEXT
);

INSERT INTO business_settings (id, business_name) VALUES (1, 'Chief of Stuff');
