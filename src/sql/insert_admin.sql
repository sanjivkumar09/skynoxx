-- Insert default admin user
-- Login credentials:
-- Email: admin@freefire.com
-- Password: admin123

INSERT INTO users (name, email, phone, role, password) 
VALUES (
    'Admin User',
    'admin@freefire.com',
    '9999999999',
    'admin',
    '$2y$10$pZxzDz081aCkpiYFs6xfBuAXeJAZvhptcl5N317fdamLyJmHKJWKq'
);

-- Insert sample player
INSERT INTO users (name, email, phone, role, password) 
VALUES (
    'Test Player',
    'player@test.com',
    '8888888888',
    'player',
    '$2y$10$pZxzDz081aCkpiYFs6xfBuAXeJAZvhptcl5N317fdamLyJmHKJWKq'
);

-- Insert sample creator
INSERT INTO users (name, email, phone, role, password) 
VALUES (
    'Test Creator',
    'creator@test.com',
    '7777777777',
    'creator',
    '$2y$10$pZxzDz081aCkpiYFs6xfBuAXeJAZvhptcl5N317fdamLyJmHKJWKq'
);
