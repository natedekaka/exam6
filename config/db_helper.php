<?php
// db_helper.php - Database Helper with Transaction & Locking Support

class DBHelper {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function beginTransaction() {
        $this->conn->begin_transaction();
    }
    
    public function commit() {
        $this->conn->commit();
    }
    
    public function rollback() {
        $this->conn->rollback();
    }
    
    public function lockTable($table, $mode = 'WRITE') {
        $this->conn->query("LOCK TABLES $table $mode");
    }
    
    public function unlockTables() {
        $this->conn->query("UNLOCK TABLES");
    }
    
    public function getLock($lockName, $timeout = 10) {
        $result = $this->conn->query("SELECT GET_LOCK('$lockName', $timeout) AS lock_result");
        $row = $result->fetch_assoc();
        return $row['lock_result'] ?? 0;
    }
    
    public function releaseLock($lockName) {
        $this->conn->query("DO RELEASE_LOCK('$lockName')");
    }
    
    public function checkDuplicateSubmit($id_ujian, $nis) {
        $stmt = $this->conn->prepare("SELECT id FROM hasil_ujian WHERE id_ujian = ? AND nis = ? LIMIT 1");
        $stmt->bind_param("is", $id_ujian, $nis);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    
    public function insertHasilUjian($id_ujian, $nis, $nama, $kelas, $total_skor, $detail_jawaban_json = null) {
        if ($detail_jawaban_json !== null) {
            $stmt = $this->conn->prepare("INSERT INTO hasil_ujian (id_ujian, nis, nama, kelas, total_skor, detail_jawaban) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssis", $id_ujian, $nis, $nama, $kelas, $total_skor, $detail_jawaban_json);
        } else {
            $stmt = $this->conn->prepare("INSERT INTO hasil_ujian (id_ujian, nis, nama, kelas, total_skor) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $id_ujian, $nis, $nama, $kelas, $total_skor);
        }
        return $stmt->execute();
    }
}