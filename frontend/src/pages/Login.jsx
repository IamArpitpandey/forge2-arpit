import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function Login() {
  const { login, loading } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    try {
      await login(email, password)
      navigate('/tickets')
    } catch (err) {
      setError(err.response?.data?.message || 'Login failed. Check your credentials.')
    }
  }

  const fillDemo = (demoEmail) => {
    setEmail(demoEmail)
    setPassword('password')
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
      <div className="w-full max-w-sm">
        {/* Logo */}
        <div className="mb-8 text-center">
          <div className="mb-2 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 text-2xl">
            📋
          </div>
          <h1 className="text-2xl font-bold text-gray-900">PulseDesk</h1>
          <p className="mt-1 text-sm text-gray-500">Sign in to your support desk</p>
        </div>

        {/* Form card */}
        <form
          onSubmit={handleSubmit}
          className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200"
        >
          {error && (
            <div className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 ring-1 ring-inset ring-red-200">
              {error}
            </div>
          )}

          <label className="mb-1 block text-sm font-medium text-gray-700">Email</label>
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            autoFocus
            className="mb-4 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
            placeholder="you@company.com"
          />

          <label className="mb-1 block text-sm font-medium text-gray-700">Password</label>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            className="mb-5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
            placeholder="••••••••"
          />

          <button
            type="submit"
            disabled={loading}
            className="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none disabled:opacity-50"
          >
            {loading ? 'Signing in...' : 'Sign in'}
          </button>
        </form>

        {/* Demo accounts */}
        <div className="mt-4 rounded-xl bg-white/60 p-4 ring-1 ring-gray-200">
          <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
            Demo accounts (click to fill)
          </p>
          <div className="flex flex-col gap-1.5">
            {[
              ['admin@acme.test', 'Admin'],
              ['marcus@acme.test', 'Agent'],
              ['john@acme.test', 'Customer'],
            ].map(([em, label]) => (
              <button
                key={em}
                onClick={() => fillDemo(em)}
                className="flex items-center justify-between rounded-lg px-2 py-1.5 text-left text-xs hover:bg-gray-100"
              >
                <span className="font-medium text-gray-700">{label}</span>
                <span className="text-gray-400">{em}</span>
              </button>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}
