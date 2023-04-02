<?php

class Auth
{
    public static function authenticateApiKey($api_key)
    {
        // Check the API key against the database
        $stmt = DB::query('SELECT * FROM api_keys WHERE api_key = ?', array($api_key));
        if ($stmt->rowCount() == 1) {
            return true;
        } else {
            return false;
        }
    }
    public static function lastRequest($api_key)
    {
        // Check the API key against the database
        $stmt = DB::query('SELECT * FROM api_keys WHERE api_key = ?', array($api_key));
        if ($stmt->rowCount() == 1) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }
}