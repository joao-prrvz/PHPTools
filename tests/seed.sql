-- schema + seed data for User / Pet tests

CREATE TABLE IF NOT EXISTS User (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    email      TEXT    NOT NULL UNIQUE,
    name       TEXT    NOT NULL,
    created_at TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS Pet (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    userId INTEGER NOT NULL REFERENCES User(id)
);

-- Users
INSERT INTO User (email, name) VALUES
    ('alice@example.com', 'Alice'),
    ('bob@example.com',   'Bob'),
    ('carol@example.com', 'Carol'),
    ('mark@example.com', 'Mark');

-- Pets (Alice=3, Bob=2, Carol=1, Mark=0)
INSERT INTO Pet (name, userId) VALUES
    ('Buddy',   1),
    ('Whiskers', 1),
    ('Rex',     1),
    ('Max',     2),
    ('Luna',    2),
    ('Coco',    3);