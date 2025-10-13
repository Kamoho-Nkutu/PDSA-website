<?php
class Appointment {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function create($userId, $petId, $serviceId, $date, $time, $notes = null) {
        // Validate date and time
        $appointmentDateTime = $date . ' ' . $time;
        $timestamp = strtotime($appointmentDateTime);
        
        if (!$timestamp || $timestamp < time()) {
            throw new Exception("Invalid appointment date/time");
        }

        // Check availability
        if (!$this->isSlotAvailable($date, $time)) {
            throw new Exception("This time slot is no longer available");
        }

        $this->db->beginTransaction();
        try {
            $this->db->query(
                "INSERT INTO appointments 
                (user_id, pet_id, service_id, appointment_date, appointment_time, notes, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())",
                [$userId, $petId, $serviceId, $date, $time, $notes]
            );

            $appointmentId = $this->db->lastInsertId();
            $this->db->commit();

            // Send confirmation email
            $this->sendConfirmationEmail($appointmentId);

            return $appointmentId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function isSlotAvailable($date, $time) {
        $existing = $this->db->fetch(
            "SELECT id FROM appointments 
            WHERE appointment_date = ? AND appointment_time = ? 
            AND status IN ('pending', 'confirmed')",
            [$date, $time]
        );
        return !$existing;
    }

    private function sendConfirmationEmail($appointmentId) {
        $appointment = $this->db->fetch(
            "SELECT a.*, u.name as user_name, u.email, p.name as pet_name, s.name as service_name
            FROM appointments a
            JOIN users u ON a.user_id = u.id
            JOIN pets p ON a.pet_id = p.id
            JOIN services s ON a.service_id = s.id
            WHERE a.id = ?",
            [$appointmentId]
        );

        if (!$appointment) {
            return false;
        }

        $to = $appointment['email'];
        $subject = "PDSA Appointment Confirmation #" . $appointmentId;
        
        $message = "
        <html>
        <head>
            <title>PDSA Appointment Confirmation</title>
        </head>
        <body>
            <h2>Appointment Confirmation</h2>
            <p>Dear " . htmlspecialchars($appointment['user_name']) . ",</p>
            <p>Your appointment for " . htmlspecialchars($appointment['pet_name']) . " has been scheduled.</p>
            
            <h3>Appointment Details</h3>
            <p><strong>Service:</strong> " . htmlspecialchars($appointment['service_name']) . "</p>
            <p><strong>Date:</strong> " . htmlspecialchars($appointment['appointment_date']) . "</p>
            <p><strong>Time:</strong> " . htmlspecialchars($appointment['appointment_time']) . "</p>
            
            <p>Please arrive 10 minutes before your scheduled time.</p>
            <p>If you need to cancel or reschedule, please contact us at least 24 hours in advance.</p>
            
            <p>Thank you,<br>PDSA Veterinary Clinic</p>
        </body>
        </html>
        ";

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . FROM_EMAIL,
            'Reply-To: ' . FROM_EMAIL,
            'X-Mailer: PHP/' . phpversion()
        ];

        return mail($to, $subject, $message, implode("\r\n", $headers));
    }

    public function updateStatus($appointmentId, $status) {
        $allowedStatuses = ['pending', 'confirmed', 'cancelled', 'completed'];
        
        if (!in_array($status, $allowedStatuses)) {
            throw new Exception("Invalid status");
        }

        return $this->db->query(
            "UPDATE appointments SET status = ? WHERE id = ?",
            [$status, $appointmentId]
        )->rowCount() > 0;
    }

    public function getAppointments($userId = null, $status = null, $date = null) {
        $where = [];
        $params = [];

        if ($userId) {
            $where[] = "a.user_id = ?";
            $params[] = $userId;
        }

        if ($status) {
            $where[] = "a.status = ?";
            $params[] = $status;
        }

        if ($date) {
            $where[] = "a.appointment_date = ?";
            $params[] = $date;
        }

        $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

        return $this->db->fetchAll(
            "SELECT a.*, u.name as user_name, u.email, u.phone, 
            p.name as pet_name, p.species, p.breed, p.age,
            s.name as service_name, s.price
            FROM appointments a
            JOIN users u ON a.user_id = u.id
            JOIN pets p ON a.pet_id = p.id
            JOIN services s ON a.service_id = s.id
            $whereClause
            ORDER BY a.appointment_date, a.appointment_time",
            $params
        );
    }

    public function getAvailableSlots($date) {
        // Clinic working hours
        $startHour = 9;
        $endHour = 17;
        $lunchStart = 13;
        $lunchEnd = 14;
        $slotDuration = 30; // minutes

        // Generate all possible slots
        $allSlots = [];
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            // Skip lunch hour
            if ($hour == $lunchStart) continue;
            
            for ($minute = 0; $minute < 60; $minute += $slotDuration) {
                // Skip lunch time
                if ($hour == $lunchStart - 1 && $minute >= 30) continue;
                if ($hour == $lunchEnd && $minute == 0) continue;
                
                $time = sprintf("%02d:%02d", $hour, $minute);
                $allSlots[$time] = true;
            }
        }

        // Get booked slots
        $bookedSlots = $this->db->fetchAll(
            "SELECT appointment_time 
            FROM appointments 
            WHERE appointment_date = ? AND status IN ('pending', 'confirmed')",
            [$date]
        );

        // Mark booked slots as unavailable
        foreach ($bookedSlots as $slot) {
            if (isset($allSlots[$slot['appointment_time']])) {
                $allSlots[$slot['appointment_time']] = false;
            }
        }

        // Return only available slots
        return array_keys(array_filter($allSlots));
    }
}
?>