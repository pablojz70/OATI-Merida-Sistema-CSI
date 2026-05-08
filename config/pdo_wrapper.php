<?php
// config/pdo_wrapper.php - Convierte MySQLi a PDO
require_once 'database.php'; // Incluye tu conexión MySQLi actual

class PDO_Wrapper {
    private $conn;
    
    public function __construct($mysqli_conn) {
        $this->conn = $mysqli_conn;
    }
    
    public function prepare($sql) {
        return new PDO_Statement($this->conn, $sql);
    }
    
    public function query($sql) {
        $result = $this->conn->query($sql);
        if (!$result) {
            throw new Exception("Query error: " . $this->conn->error);
        }
        return new PDO_ResultSet($result);
    }
    
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    public function errorInfo() {
        return [$this->conn->errno, $this->conn->error];
    }
}

class PDO_Statement {
    private $stmt;
    private $conn;
    private $sql;
    
    public function __construct($conn, $sql) {
        $this->conn = $conn;
        $this->sql = $sql;
        $this->stmt = $conn->prepare($sql);
    }
    
    public function execute($params = []) {
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $this->stmt->bind_param($types, ...$params);
        }
        return $this->stmt->execute();
    }
    
    public function fetchAll($fetch_style = MYSQLI_ASSOC) {
        $result = $this->stmt->get_result();
        return $result->fetch_all($fetch_style);
    }
    
    public function fetch($fetch_style = MYSQLI_ASSOC) {
        $result = $this->stmt->get_result();
        return $result->fetch_assoc();
    }
    
    public function fetchColumn($column_number = 0) {
        $result = $this->stmt->get_result();
        $row = $result->fetch_array(MYSQLI_NUM);
        return $row ? $row[$column_number] : false;
    }
    
    public function rowCount() {
        return $this->stmt->affected_rows;
    }
}

class PDO_ResultSet {
    private $result;
    
    public function __construct($result) {
        $this->result = $result;
    }
    
    public function fetchAll($fetch_style = MYSQLI_ASSOC) {
        return $this->result->fetch_all($fetch_style);
    }
    
    public function fetch($fetch_style = MYSQLI_ASSOC) {
        return $this->result->fetch_assoc();
    }
    
    public function fetchColumn($column_number = 0) {
        $row = $this->result->fetch_array(MYSQLI_NUM);
        return $row ? $row[$column_number] : false;
    }
    
    public function rowCount() {
        return $this->result->num_rows;
    }
}

// Crear instancia global compatible con PDO
$pdo = new PDO_Wrapper($conn);
?>
