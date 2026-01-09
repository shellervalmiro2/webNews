<?php
// test.php
error_log("Test script started");
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Проверяем базовые вещи
echo "PHP version: " . phpversion() . "<br>";
echo "Memory limit: " . ini_get('memory_limit') . "<br>";
echo "Max execution time: " . ini_get('max_execution_time') . "<br>";

// Пробуем потенциально опасную операцию
try {
    $result = 1 / 0; // Division by zero
} catch (Throwable $e) {
    error_log("Exception caught: " . $e->getMessage());
    echo "Exception: " . $e->getMessage();
}

error_log("Test script completed");
?>