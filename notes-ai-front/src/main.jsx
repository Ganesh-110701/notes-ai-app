import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { Provider } from './components/ui/provider'
import { Toaster } from './components/ui/toaster'
import App from './App.jsx'

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <Provider>
      <Toaster />
      <App />
    </Provider>
  </StrictMode>,
)
