<?php

class MyCustomSessionHandler implements SessionHandlerInterface 
{
    protected $table_sess = 'sessions';
    protected $col_sid = 'sid';
    protected $col_expiry = 'expiry';
    protected $col_data = 'data';
    protected $expiry;
    protected $db;

    public function __construct() 
    {
        $this->db = $this->dbConnect();
        $this->expiry = time() + (int) ini_get('session.gc_maxlifetime');
    }

    private function dbConnect() 
    {
        try {
            $db = new PDO('mysql:host=localhost;dbname=sess_handler', 'root', 'root');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $db;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    public function open($save_path, $name): bool
    {
        return true;
    }

    public function read($session_id): string
    {
        $sql = "SELECT $this->col_expiry, $this->col_data
                FROM $this->table_sess 
                WHERE $this->col_sid =" . $this->db->quote($session_id);
        $result = $this->db->query($sql);
        $data = $result->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            if ($data[$this->col_expiry] < time()) {
                return '';
            }
            return $data[$this->col_data];
        }
        return '';
    }

    public function write($session_id, $session_data): bool
    {
        $sql = "INSERT INTO $this->table_sess 
                SET $this->col_sid=" . $this->db->quote($session_id) .",
                $this->col_expiry=" . $this->db->quote($this->expiry) . ",
                $this->col_data=" . $this->db->quote($session_data) . " 
                ON DUPLICATE KEY UPDATE
                $this->col_data=" . $this->db->quote($session_data);
        $this->db->query($sql);

        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function gc($maxlifetime): bool
    {
        $sql = "DELETE FROM $this->table_sess 
                WHERE $this->col_expiry <" . time();
        $this->db->query($sql);
        return true;
    }

    public function destroy($session_id): bool
    {
        $sql = "DELETE FROM $this->table_sess 
                    WHERE $this->col_sid=" . $this->db->quote($session_id);
        $this->db->query($sql);
        return true;
    }
}