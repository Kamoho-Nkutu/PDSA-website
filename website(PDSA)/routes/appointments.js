const express = require('express');
const router = express.Router();
const { db } = require('../config/firebase-admin');

// Get all appointments
router.get('/', async (req, res) => {
  try {
    const appointmentsRef = db.collection('appointments');
    const snapshot = await appointmentsRef.orderBy('date', 'desc').get();
    
    const appointments = [];
    snapshot.forEach(doc => {
      appointments.push({
        id: doc.id,
        ...doc.data()
      });
    });
    
    res.json(appointments);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Create new appointment
router.post('/', async (req, res) => {
  try {
    const { patientName, ownerName, service, date, time, status, notes } = req.body;
    
    const docRef = await db.collection('appointments').add({
      patientName,
      ownerName,
      service,
      date,
      time,
      status: status || 'pending',
      notes: notes || '',
      createdAt: new Date().toISOString()
    });
    
    res.json({ id: docRef.id, message: 'Appointment created successfully' });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Update appointment status
router.put('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { status } = req.body;
    
    await db.collection('appointments').doc(id).update({
      status,
      updatedAt: new Date().toISOString()
    });
    
    res.json({ message: 'Appointment updated successfully' });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

module.exports = router;