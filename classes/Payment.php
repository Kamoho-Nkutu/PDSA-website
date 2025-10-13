<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoload

class Payment {
    private $db;
    private $stripe;

    public function __construct(Database $db) {
        $this->db = $db;
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        $this->stripe = new \Stripe\StripeClient(STRIPE_SECRET_KEY);
    }

    public function createPaymentIntent($amount, $currency = 'gbp', $metadata = []) {
        try {
            $intent = \Stripe\PaymentIntent::create([
                'amount' => $amount * 100, // Stripe uses smallest currency unit
                'currency' => $currency,
                'metadata' => $metadata,
                'payment_method_types' => ['card']
            ]);

            return [
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe error: " . $e->getMessage());
            throw new Exception("Payment processing error. Please try again.");
        }
    }

    public function processAppointmentPayment($appointmentId, $paymentMethodId, $userId) {
        $appointment = $this->db->fetch(
            "SELECT a.*, s.price 
            FROM appointments a
            JOIN services s ON a.service_id = s.id
            WHERE a.id = ? AND a.user_id = ? AND a.status = 'confirmed'",
            [$appointmentId, $userId]
        );

        if (!$appointment) {
            throw new Exception("Appointment not found or not eligible for payment");
        }

        $amount = $appointment['price'] * 100; // Convert to pence

        $this->db->beginTransaction();
        try {
            // Create Stripe payment intent
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $amount,
                'currency' => 'gbp',
                'payment_method' => $paymentMethodId,
                'confirm' => true,
                'metadata' => [
                    'appointment_id' => $appointmentId,
                    'user_id' => $userId
                ],
                'description' => "Payment for appointment #$appointmentId",
                'receipt_email' => $_SESSION['user']['email'] ?? null
            ]);

            // Record payment in database
            $this->db->query(
                "INSERT INTO payments 
                (user_id, appointment_id, amount, currency, payment_intent_id, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [
                    $userId,
                    $appointmentId,
                    $appointment['price'],
                    'gbp',
                    $paymentIntent->id,
                    $paymentIntent->status
                ]
            );

            // Update appointment status if payment succeeded
            if ($paymentIntent->status === 'succeeded') {
                $this->db->query(
                    "UPDATE appointments SET status = 'paid' WHERE id = ?",
                    [$appointmentId]
                );
            }

            $this->db->commit();

            // Send payment confirmation email
            $this->sendPaymentConfirmation($appointmentId, $paymentIntent->id);

            return [
                'status' => $paymentIntent->status,
                'payment_id' => $paymentIntent->id,
                'amount' => $appointment['price']
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Payment processing error: " . $e->getMessage());
            throw new Exception("Payment processing failed. Please try again.");
        }
    }

    private function sendPaymentConfirmation($appointmentId, $paymentId) {
        $appointment = $this->db->fetch(
            "SELECT a.*, u.name as user_name, u.email, p.name as pet_name, s.name as service_name, s.price
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
        $subject = "PDSA Payment Confirmation #" . $paymentId;
        
        $message = "
        <html>
        <head>
            <title>PDSA Payment Confirmation</title>
        </head>
        <body>
            <h2>Payment Confirmation</h2>
            <p>Dear " . htmlspecialchars($appointment['user_name']) . ",</p>
            <p>Thank you for your payment for " . htmlspecialchars($appointment['pet_name']) . "'s appointment.</p>
            
            <h3>Payment Details</h3>
            <p><strong>Payment ID:</strong> $paymentId</p>
            <p><strong>Service:</strong> " . htmlspecialchars($appointment['service_name']) . "</p>
            <p><strong>Amount:</strong> £" . number_format($appointment['price'], 2) . "</p>
            <p><strong>Date:</strong> " . date('d/m/Y H:i') . "</p>
            
            <h3>Appointment Details</h3>
            <p><strong>Date:</strong> " . htmlspecialchars($appointment['appointment_date']) . "</p>
            <p><strong>Time:</strong> " . htmlspecialchars($appointment['appointment_time']) . "</p>
            
            <p>If you have any questions about your payment, please contact our support team.</p>
            
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

    public function getPaymentHistory($userId) {
        return $this->db->fetchAll(
            "SELECT p.*, a.appointment_date, a.appointment_time, s.name as service_name
            FROM payments p
            JOIN appointments a ON p.appointment_id = a.id
            JOIN services s ON a.service_id = s.id
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC",
            [$userId]
        );
    }

    public function refundPayment($paymentId, $adminId) {
        $payment = $this->db->fetch(
            "SELECT * FROM payments WHERE payment_intent_id = ? AND status = 'succeeded'",
            [$paymentId]
        );

        if (!$payment) {
            throw new Exception("Payment not found or not eligible for refund");
        }

        $this->db->beginTransaction();
        try {
            // Process refund with Stripe
            $refund = $this->stripe->refunds->create([
                'payment_intent' => $paymentId,
                'amount' => $payment['amount'] * 100
            ]);

            // Update payment status
            $this->db->query(
                "UPDATE payments SET status = 'refunded', refunded_at = NOW() 
                WHERE payment_intent_id = ?",
                [$paymentId]
            );

            // Update appointment status
            $this->db->query(
                "UPDATE appointments SET status = 'cancelled' WHERE id = ?",
                [$payment['appointment_id']]
            );

            // Record refund in database
            $this->db->query(
                "INSERT INTO refunds 
                (payment_id, admin_id, amount, currency, refund_id, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())",
                [
                    $payment['id'],
                    $adminId,
                    $payment['amount'],
                    $payment['currency'],
                    $refund->id
                ]
            );

            $this->db->commit();

            // Send refund confirmation email
            $this->sendRefundConfirmation($payment['appointment_id'], $refund->id);

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Refund processing error: " . $e->getMessage());
            throw new Exception("Refund processing failed. Please try again.");
        }
    }

    private function sendRefundConfirmation($appointmentId, $refundId) {
        $appointment = $this->db->fetch(
            "SELECT a.*, u.name as user_name, u.email, p.name as pet_name, s.name as service_name, s.price
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
        $subject = "PDSA Refund Confirmation #" . $refundId;
        
        $message = "
        <html>
        <head>
            <title>PDSA Refund Confirmation</title>
        </head>
        <body>
            <h2>Refund Confirmation</h2>
            <p>Dear " . htmlspecialchars($appointment['user_name']) . ",</p>
            <p>Your refund for " . htmlspecialchars($appointment['pet_name']) . "'s appointment has been processed.</p>
            
            <h3>Refund Details</h3>
            <p><strong>Refund ID:</strong> $refundId</p>
            <p><strong>Service:</strong> " . htmlspecialchars($appointment['service_name']) . "</p>
            <p><strong>Amount:</strong> £" . number_format($appointment['price'], 2) . "</p>
            <p><strong>Date:</strong> " . date('d/m/Y H:i') . "</p>
            
            <p>Please allow 5-10 business days for the refund to appear in your account.</p>
            <p>If you have any questions about your refund, please contact our support team.</p>
            
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
}
?>