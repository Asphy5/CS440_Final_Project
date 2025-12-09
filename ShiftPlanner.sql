create database ShiftPlanner;
use ShiftPlanner;

create table Shifts (
    Sid        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Shift_Type        varchar(20),
    Start_Time        time,
    End_Time        time,
    Shift_Duration        decimal(4,2)
);

create table Positions (
    Pid        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Pname        varchar(20)
);

create table Employees (
    Eid        char(8) PRIMARY KEY,
    First_Name        varchar(20) NOT NULL,
    Last_Name        varchar(20) NOT NULL,
    Phone        varchar(20),
    Pay        decimal(4,2),
    Hours        decimal(5,2),
    Pid        INT UNSIGNED,
    FOREIGN KEY (Pid) REFERENCES Positions(Pid) ON DELETE SET NULL -- employee still valid if deleted
);

create table Schedule (
    Eid        char(8),
    Sid        INT UNSIGNED,
    Shift_Date        date,
    FOREIGN KEY (Eid) REFERENCES Employees(Eid) ON DELETE CASCADE, -- Remove all rows with this
    FOREIGN KEY (Sid) REFERENCES Shifts(Sid) ON DELETE CASCADE -- Remove all rows with this
);

create table TimeOffRequests (
    Rid        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Eid        char(8),
    Start_Date        date,
    End_Date        date,
    Reason        varchar(100),
    Status        varchar(20),
    FOREIGN KEY (Eid) REFERENCES Employees(Eid) ON DELETE CASCADE -- Remove all rows with deleted Eid
);


-- Employee ID Generator
DELIMITER $$

CREATE TRIGGER generate_employee_id
AFTER INSERT ON Employees
FOR EACH ROW
BEGIN
	DECLARE next_num INT;
    DECLARE prefix_char CHAR(1) DEFAULT 'E';
    
    -- Find the current highest ID
    SELECT IFNULL(MAX(CAST(SUBSTRING(Eid, 2) AS UNSIGNED)), 0) + 1
    INTO next_num FROM Employees WHERE Eid LIKE
    CONCAT(prefix_char, '%');
    
    -- Creates the ID formatted with 7 leading 0s
    SET NEW.Eid = CONCAT(prefix_char, LPAD(next_num, 7, '0'));
END;
$$

DELIMITER ;


-- Set the duration of a shift automatically
DELIMITER $$

CREATE TRIGGER calculate_shift_duration
BEFORE INSERT ON Shifts
FOR EACH ROW
BEGIN
	-- Set the duration to the difference between the start and end
	SET NEW.Shift_Duration = CAST(TIME_TO_SEC(timediff(
    CASE WHEN NEW.End_Time < NEW.Start_Time -- If the end is smaller than the start
    THEN ADDTIME(NEW.End_Time, '24:00:00') -- Add 24 hours to account for midnight
    ELSE NEW.End_Time END, NEW.Start_Time)) / 3600 
    AS DECIMAL(4, 2));
END$$

DELIMITER ;


-- Update shift duration when updating a shift
DELIMITER $$

CREATE TRIGGER update_shift_duration
BEFORE UPDATE ON Shifts
FOR EACH ROW
BEGIN
	SET NEW.Shift_Duration = CAST(TIME_TO_SEC(timediff(
    CASE WHEN NEW.End_Time < NEW.Start_Time -- If the end is smaller than the start
    THEN ADDTIME(NEW.End_Time, '24:00:00') -- Add 24 hours to account for midnight
    ELSE NEW.End_Time END, NEW.Start_Time)) / 3600 
    AS DECIMAL(4, 2));
END$$

DELIMITER ;


-- Calculate an employee's total scheduled hours
DELIMITER $$

CREATE TRIGGER calculate_hours
AFTER INSERT ON Schedule
FOR EACH ROW
BEGIN
    UPDATE Employees
    SET Hours = (
        SELECT COALESCE(SUM(s.Shift_Duration), 0) -- Get the sum of all their shift's durations
        FROM Shifts s
        JOIN Schedule c ON s.Sid = c.Sid
        WHERE c.Eid = NEW.Eid
    )
    WHERE Employees.Eid = NEW.Eid;
END$$

DELIMITER ;


-- Calculate each employee's hours after a shift is updated
DELIMITER $$

CREATE TRIGGER shift_update_calculate_hours
AFTER UPDATE ON Shifts
FOR EACH ROW
BEGIN
    UPDATE Employees e
    SET e.Hours = (
        SELECT COALESCE(SUM(s.Shift_Duration), 0)
        FROM Schedule c
        JOIN Shifts s ON c.Sid = s.Sid
        WHERE c.Eid = e.Eid
    )
    WHERE e.Eid IN (
        SELECT c.Eid FROM Schedule c WHERE c.Sid = NEW.Sid
    );
END$$

DELIMITER ;


-- View an individual's shifts
CREATE VIEW view_shifts AS
SELECT Shift_Type, Start_Time, End_Time, Shift_Date
FROM Shifts s JOIN Schedule c ON s.Sid = c.Sid
WHERE c.Eid = (SELECT SUBSTRING_INDEX(USER(), '@', 1)) ORDER BY Shift_Date Asc;


-- View everyone's shifts
CREATE VIEW view_all_shifts AS
SELECT First_Name, Last_Name, Shift_Type, Start_Time, End_Time, Shift_Date
FROM Shifts s JOIN Schedule c ON s.Sid = c.Sid
JOIN Employees e ON c.Eid = e.Eid ORDER BY Shift_Date Asc;


-- View an individual's time off requests
CREATE VIEW view_time_off AS
SELECT First_Name, Last_Name, Start_Date, End_Date, Reason, Status
FROM TimeOffRequests t LEFT JOIN Employees e ON t.Eid = e.Eid
WHERE t.Eid = (SELECT SUBSTRING_INDEX(USER(), '@', 1)) ORDER BY Start_Date Asc;

-- View everyone's time off requests
CREATE VIEW manage_time_off AS
SELECT Rid, First_Name, Last_Name, Start_Date, End_Date, Reason, Status
FROM TimeOffRequests t LEFT JOIN Employees e ON t.Eid = e.Eid
ORDER BY Start_Date Asc;


-- Default Positions
INSERT INTO Positions(Pname)
VALUES
('Manager'),
('Cook'),
('Cashier');

-- Default Employee Entries
INSERT INTO Employees(Eid, First_Name, Last_Name, Phone, Pay, Hours, Pid)
VALUES
('E0000001', 'Mann', 'Ageir', '555-6789', 22.50, 0, 1),
('E0000002', 'John', 'Smith', '444-6789', 13.50, 0, 2),
('E0000003', 'Steve', 'Rogers', '333-6789', 13.50, 0, 2),
('E0000004', 'Matt', 'Hughes', '222-6789', 13.50, 0, 2),
('E0000005', 'Cash', 'Year', '111-6789', 13.00, 0, 3),
('E0000006', 'Alice', 'Chain', '123-6789', 13.00, 0, 3),
('E0000007', 'Jane', 'Jill', '321-6789', 13.00, 0, 3);

-- Default Shifts
INSERT INTO Shifts(Shift_Type, Start_Time, End_Time)
VALUES
('Morning', '07:30:00', '14:00:00'),
('Afternoon', '11:00:00', '17:00:00'),
('Evening', '17:00:00', '23:00:00'),
('Closing', '17:00:00', '1:00:00');

-- Default Schedule
INSERT INTO Schedule(Eid, Sid, Shift_Date)
VALUES
('E0000001', 1, '2025-12-10'),
('E0000002', 1, '2025-12-10'),
('E0000003', 2, '2025-12-10'),
('E0000005', 2, '2025-12-10'),
('E0000006', 2, '2025-12-10'),
('E0000004', 4, '2025-12-10'),
('E0000006', 3, '2025-12-10'),
('E0000007', 4, '2025-12-10');

-- Default Time Off Requests
INSERT INTO TimeOffRequests(Eid, Start_Date, End_Date, Reason, Status)
VALUES
('E0000005', '2025-12-15', '2025-12-18', 'Family Trip', 'Pending'),
('E0000002', '2025-12-24', '2025-12-25', 'Christmas', 'Pending');

-- Default database users
-- Manager
CREATE USER 'E0000001'@'localhost' IDENTIFIED BY 'Password123!';
GRANT SELECT, INSERT, UPDATE, DELETE ON ShiftPlanner.* TO 'E0000001'@'localhost' WITH GRANT OPTION;
GRANT CREATE USER ON *.* TO 'E0000001'@'localhost' WITH GRANT OPTION;
GRANT RELOAD ON *.* TO 'E0000001'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;

-- Employees
CREATE USER 'E0000002'@'localhost' IDENTIFIED BY 'Password123!';
GRANT SELECT ON ShiftPlanner.view_shifts TO 'E0000002'@'localhost';
GRANT SELECT ON ShiftPlanner.Employees TO 'E0000002'@'localhost';
GRANT SELECT ON ShiftPlanner.Positions TO 'E0000002'@'localhost';
GRANT SELECT, INSERT ON ShiftPlanner.TimeOffRequests TO 'E0000002'@'localhost';
GRANT SELECT ON ShiftPlanner.view_time_off TO 'E0000002'@'localhost';

CREATE USER 'E0000003'@'localhost' IDENTIFIED BY 'Password123!';
GRANT SELECT ON ShiftPlanner.view_shifts TO 'E0000003'@'localhost';
GRANT SELECT ON ShiftPlanner.Employees TO 'E0000003'@'localhost';
GRANT SELECT ON ShiftPlanner.Positions TO 'E0000003'@'localhost';
GRANT SELECT, INSERT ON ShiftPlanner.TimeOffRequests TO 'E0000003'@'localhost';
GRANT SELECT ON ShiftPlanner.view_time_off TO 'E0000003'@'localhost';

CREATE USER 'E0000004'@'localhost' IDENTIFIED BY 'Password123!';
GRANT SELECT ON ShiftPlanner.view_shifts TO 'E0000004'@'localhost';
GRANT SELECT ON ShiftPlanner.Employees TO 'E0000004'@'localhost';
GRANT SELECT ON ShiftPlanner.Positions TO 'E0000004'@'localhost';
GRANT SELECT, INSERT ON ShiftPlanner.TimeOffRequests TO 'E0000004'@'localhost';
GRANT SELECT ON ShiftPlanner.view_time_off TO 'E0000004'@'localhost';

CREATE USER 'E0000005'@'localhost' IDENTIFIED BY 'Password123!';
GRANT SELECT ON ShiftPlanner.view_shifts TO 'E0000005'@'localhost';
GRANT SELECT ON ShiftPlanner.Employees TO 'E0000005'@'localhost';
GRANT SELECT ON ShiftPlanner.Positions TO 'E0000005'@'localhost';
GRANT SELECT, INSERT ON ShiftPlanner.TimeOffRequests TO 'E0000005'@'localhost';
GRANT SELECT ON ShiftPlanner.view_time_off TO 'E0000005'@'localhost';

CREATE USER 'E0000006'@'localhost' IDENTIFIED BY 'Password123!';
GRANT SELECT ON ShiftPlanner.view_shifts TO 'E0000006'@'localhost';
GRANT SELECT ON ShiftPlanner.Employees TO 'E0000006'@'localhost';
GRANT SELECT ON ShiftPlanner.Positions TO 'E0000006'@'localhost';
GRANT SELECT, INSERT ON ShiftPlanner.TimeOffRequests TO 'E0000006'@'localhost';
GRANT SELECT ON ShiftPlanner.view_time_off TO 'E0000006'@'localhost';

CREATE USER 'E0000007'@'localhost' IDENTIFIED BY 'Password123!';
GRANT SELECT ON ShiftPlanner.view_shifts TO 'E0000007'@'localhost';
GRANT SELECT ON ShiftPlanner.Employees TO 'E0000007'@'localhost';
GRANT SELECT ON ShiftPlanner.Positions TO 'E0000007'@'localhost';
GRANT SELECT, INSERT ON ShiftPlanner.TimeOffRequests TO 'E0000007'@'localhost';
GRANT SELECT ON ShiftPlanner.view_time_off TO 'E0000007'@'localhost';
