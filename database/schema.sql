-- Schema de base pour MyWeeklyAllowance

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(10) NOT NULL CHECK (role IN ('PARENT', 'CHILD')),
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    parent_id INT NULL,
    CONSTRAINT fk_user_parent FOREIGN KEY (parent_id) REFERENCES users(id),
    CONSTRAINT chk_child_parent CHECK ((role = 'CHILD' AND parent_id IS NOT NULL) OR (role = 'PARENT' AND parent_id IS NULL))
);

CREATE TABLE weeks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    child_id INT NOT NULL,
    budget DECIMAL(10,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    CONSTRAINT fk_week_child FOREIGN KEY (child_id) REFERENCES users(id),
    CONSTRAINT uq_week_child_start UNIQUE (child_id, start_date)
);

CREATE INDEX idx_weeks_child ON weeks(child_id);

CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    week_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    date DATE NOT NULL,
    description TEXT NULL,
    CONSTRAINT fk_expense_week FOREIGN KEY (week_id) REFERENCES weeks(id)
);

CREATE INDEX idx_expenses_week ON expenses(week_id);
CREATE INDEX idx_expenses_date ON expenses(date);
