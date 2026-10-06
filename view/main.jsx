import React from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import App from './App.jsx'
import { ProveedorSesion } from './components/Sesion.jsx'
import './index.css'

createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <BrowserRouter basename={import.meta.env.BASE_URL.replace(/\/$/, '')}>
      <ProveedorSesion>
        <App />
      </ProveedorSesion>
    </BrowserRouter>
  </React.StrictMode>,
)
