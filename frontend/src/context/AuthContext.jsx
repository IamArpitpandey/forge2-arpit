import { createContext, useContext, useState, useCallback } from 'react'
import api from '../lib/api'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => {
    const stored = localStorage.getItem('pulsedesk_user')
    return stored ? JSON.parse(stored) : null
  })
  const [token, setToken] = useState(() => localStorage.getItem('pulsedesk_token'))
  const [loading, setLoading] = useState(false)

  const login = useCallback(async (email, password) => {
    setLoading(true)
    try {
      const { data } = await api.post('/api/login', { email, password })
      localStorage.setItem('pulsedesk_token', data.token)
      localStorage.setItem('pulsedesk_user', JSON.stringify(data.user))
      setToken(data.token)
      setUser(data.user)
      return data.user
    } finally {
      setLoading(false)
    }
  }, [])

  const logout = useCallback(async () => {
    try {
      await api.post('/api/logout')
    } catch {
      // token may already be invalid — ignore
    }
    localStorage.removeItem('pulsedesk_token')
    localStorage.removeItem('pulsedesk_user')
    setToken(null)
    setUser(null)
  }, [])

  const value = { user, token, loading, login, logout, isAuthed: !!token }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
