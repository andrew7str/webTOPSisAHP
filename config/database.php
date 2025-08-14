<?php
class Database {
    private $host = "localhost";
    private $db_name = "topsis_ahp_shop";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Verifikasi struktur tabel
            $this->verifyDatabaseStructure();
            
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
        
        return $this->conn;
    }
    
    private function verifyDatabaseStructure() {
        try {
            // Cek apakah kolom created_at ada di tabel products
            $stmt = $this->conn->query("
                SELECT COUNT(*) 
                FROM information_schema.columns 
                WHERE table_name = 'products' 
                AND column_name = 'created_at'
            ");
            
            if ($stmt->fetchColumn() == 0) {
                error_log("Warning: created_at column missing in products table");
            }
            
        } catch (PDOException $e) {
            error_log("Structure verification failed: " . $e->getMessage());
        }
    }
}
?>