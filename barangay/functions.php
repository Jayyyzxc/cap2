<?php
function calculateAge($birthdate) {
    if (empty($birthdate)) {  // Added missing closing parenthesis
        return 'N/A';
    }
    
    try {
        $birthDate = new DateTime($birthdate);
        $today = new DateTime();
        $age = $today->diff($birthDate);
        return $age->y;
    } catch (Exception $e) {
        return 'N/A';
    }
}
?>