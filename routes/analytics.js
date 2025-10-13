const express = require('express');
const router = express.Router();
const { db } = require('../config/firebase-admin');

// Get dashboard statistics
router.get('/dashboard', async (req, res) => {
  try {
    // Get today's date for filtering
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const todayISO = today.toISOString();
    
    // Calculate tomorrow for date range
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowISO = tomorrow.toISOString();
    
    // Get today's appointments
    const appointmentsSnapshot = await db.collection('appointments')
      .where('date', '>=', todayISO)
      .where('date', '<', tomorrowISO)
      .get();
    
    const todayAppointments = appointmentsSnapshot.size;
    
    // Calculate today's revenue (example logic)
    let todayRevenue = 0;
    appointmentsSnapshot.forEach(doc => {
      const appointment = doc.data();
      // Simple pricing model based on service type
      if (appointment.service === 'Vaccination') todayRevenue += 250;
      else if (appointment.service === 'Dental Care') todayRevenue += 450;
      else if (appointment.service === 'Surgery') todayRevenue += 800;
      else todayRevenue += 300; // Default consultation fee
    });
    
    // Get total patients
    const usersSnapshot = await db.collection('users').get();
    const totalPatients = usersSnapshot.size;
    
    // Get today's product sales (example logic)
    const productsSnapshot = await db.collection('orders')
      .where('orderDate', '>=', todayISO)
      .where('orderDate', '<', tomorrowISO)
      .get();
    
    let todayProductsSold = 0;
    productsSnapshot.forEach(doc => {
      const order = doc.data();
      todayProductsSold += order.items ? order.items.length : 0;
    });
    
    res.json({
      todayAppointments,
      todayRevenue,
      totalPatients,
      todayProductsSold
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

module.exports = router;