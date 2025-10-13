<?php
class Pet {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function addPet($userId, $name, $species, $breed, $age, $weight = null, $medicalHistory = null) {
        // Validate input
        if (empty($name) || empty($species) || empty($breed) || empty($age)) {
            throw new Exception("All required fields must be filled");
        }

        $this->db->beginTransaction();
        try {
            $this->db->query(
                "INSERT INTO pets 
                (user_id, name, species, breed, age, weight, medical_history, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                [$userId, $name, $species, $breed, $age, $weight, $medicalHistory]
            );

            $petId = $this->db->lastInsertId();
            $this->db->commit();
            return $petId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updatePet($petId, $userId, $data) {
        // Validate ownership
        $pet = $this->db->fetch("SELECT id FROM pets WHERE id = ? AND user_id = ?", [$petId, $userId]);
        if (!$pet) {
            throw new Exception("Pet not found or not owned by user");
        }

        $allowedFields = ['name', 'species', 'breed', 'age', 'weight', 'medical_history'];
        $updates = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $petId;
        $sql = "UPDATE pets SET " . implode(', ', $updates) . " WHERE id = ?";
        return $this->db->query($sql, $params)->rowCount() > 0;
    }

    public function deletePet($petId, $userId) {
        // Check if pet has any appointments
        $appointments = $this->db->fetch(
            "SELECT id FROM appointments WHERE pet_id = ? AND status IN ('pending', 'confirmed')",
            [$petId]
        );

        if ($appointments) {
            throw new Exception("Cannot delete pet with pending or confirmed appointments");
        }

        return $this->db->query(
            "DELETE FROM pets WHERE id = ? AND user_id = ?",
            [$petId, $userId]
        )->rowCount() > 0;
    }

    public function getPets($userId = null) {
        $where = $userId ? "WHERE user_id = ?" : "";
        $params = $userId ? [$userId] : [];

        return $this->db->fetchAll(
            "SELECT * FROM pets $where ORDER BY name",
            $params
        );
    }

    public function getPet($petId, $userId = null) {
        $where = $userId ? "AND user_id = ?" : "";
        $params = $userId ? [$petId, $userId] : [$petId];

        return $this->db->fetch(
            "SELECT * FROM pets WHERE id = ? $where",
            $params
        );
    }

    public function addMedicalRecord($petId, $userId, $vetId, $diagnosis, $treatment, $prescription = null, $notes = null) {
        // Validate ownership
        $pet = $this->getPet($petId, $userId);
        if (!$pet) {
            throw new Exception("Pet not found or not owned by user");
        }

        $this->db->beginTransaction();
        try {
            $this->db->query(
                "INSERT INTO medical_records 
                (pet_id, vet_id, diagnosis, treatment, prescription, notes, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [$petId, $vetId, $diagnosis, $treatment, $prescription, $notes]
            );

            $recordId = $this->db->lastInsertId();
            $this->db->commit();
            return $recordId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getMedicalRecords($petId, $userId = null) {
        // Validate ownership if user ID provided
        if ($userId) {
            $pet = $this->getPet($petId, $userId);
            if (!$pet) {
                throw new Exception("Pet not found or not owned by user");
            }
        }

        return $this->db->fetchAll(
            "SELECT mr.*, u.name as vet_name 
            FROM medical_records mr
            JOIN users u ON mr.vet_id = u.id
            WHERE mr.pet_id = ?
            ORDER BY mr.created_at DESC",
            [$petId]
        );
    }

    public function addPrescription($petId, $userId, $vetId, $medication, $dosage, $frequency, $duration, $notes = null) {
        // Validate ownership
        $pet = $this->getPet($petId, $userId);
        if (!$pet) {
            throw new Exception("Pet not found or not owned by user");
        }

        $this->db->beginTransaction();
        try {
            $this->db->query(
                "INSERT INTO prescriptions 
                (pet_id, vet_id, medication, dosage, frequency, duration, notes, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
                [$petId, $vetId, $medication, $dosage, $frequency, $duration, $notes]
            );

            $prescriptionId = $this->db->lastInsertId();
            $this->db->commit();
            return $prescriptionId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getPrescriptions($petId, $userId = null, $status = null) {
        // Validate ownership if user ID provided
        if ($userId) {
            $pet = $this->getPet($petId, $userId);
            if (!$pet) {
                throw new Exception("Pet not found or not owned by user");
            }
        }

        $where = "WHERE pet_id = ?";
        $params = [$petId];

        if ($status) {
            $where .= " AND status = ?";
            $params[] = $status;
        }

        return $this->db->fetchAll(
            "SELECT p.*, u.name as vet_name 
            FROM prescriptions p
            JOIN users u ON p.vet_id = u.id
            $where
            ORDER BY p.created_at DESC",
            $params
        );
    }
}
?>