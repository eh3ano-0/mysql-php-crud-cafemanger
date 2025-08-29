CREATE DEFINER=`root`@`localhost` PROCEDURE `add_employep`(
    IN emp_id VARCHAR(45),
    IN emp_pos VARCHAR(45),
    IN emp_salary DECIMAL(10, 2),
    IN emp_date VARCHAR(45),
    IN emp_accnumber INT
)
BEGIN
    INSERT INTO employe (empID, position, salary, datehir, accnumber)
    VALUES (emp_id, emp_pos, emp_salary, emp_date, emp_accnumber);
END