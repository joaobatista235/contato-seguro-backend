CREATE TABLE company (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    active INTEGER NOT NULL
);

CREATE TABLE admin_user (
    id INTEGER PRIMARY KEY,
    company_id INTEGER,
    email TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL
);

CREATE TABLE category (
    id INTEGER PRIMARY KEY,
    company_id INTEGER,
    title TEXT NOT NULL,
    active INTEGER NOT NULL
);

CREATE TABLE product (
    id INTEGER PRIMARY KEY,
    company_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    price REAL,
    active INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE product_category (
    id INTEGER PRIMARY KEY,
    product_id INTEGER NOT NULL,
    cat_id INTEGER NOT NULL
);

CREATE TABLE product_log (
    id INTEGER PRIMARY KEY,
    product_id INTEGER NOT NULL,
    admin_user_id INTEGER NOT NULL,
    action TEXT NOT NULL,
    timestamp TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
