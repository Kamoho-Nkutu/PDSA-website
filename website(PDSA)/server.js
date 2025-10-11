const express = require('express');
const cors = require('cors');
const app = express();

// Middleware
app.use(cors());
app.use(express.json());

// Routes
app.use('/api/appointments', require('./routes/appointments'));
app.use('/api/products', require('./routes/products'));
app.use('/api/users', require('./routes/users'));
app.use('/api/analytics', require('./routes/analytics'));

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({ status: 'Server is running', timestamp: new Date().toISOString() });
});

// Default route
app.get('/', (req, res) => {
  res.json({ 
    message: 'PDSA Veterinary Clinic API',
    version: '1.0.0',
    endpoints: {
      appointments: '/api/appointments',
      products: '/api/products',
      users: '/api/users',
      analytics: '/api/analytics'
    }
  });
});

const PORT = process.env.PORT || 5000;

app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});