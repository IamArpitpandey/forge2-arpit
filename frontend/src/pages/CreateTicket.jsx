import { useState, useEffect } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import api from '../lib/api'
import { useAuth } from '../context/AuthContext'
import { PriorityBadge, TagPill } from '../components/Badges'

const PRIORITIES = ['low', 'medium', 'high', 'urgent']

export default function CreateTicket() {
  const navigate = useNavigate()
  const { user } = useAuth()
  const isStaff = user?.role === 'admin' || user?.role === 'agent'

  const [form, setForm] = useState({
    subject: '',
    description: '',
    priority: 'medium',
    requester_id: '',
    assignee_id: '',
  })
  const [availableTags, setAvailableTags] = useState([])
  const [selectedTagIds, setSelectedTagIds] = useState([])
  const [users, setUsers] = useState([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    Promise.all([
      api.get('/api/tags'),
      isStaff ? api.get('/api/users') : Promise.resolve({ data: [] }),
    ])
      .then(([tagsRes, usersRes]) => {
        setAvailableTags(tagsRes.data)
        if (isStaff) setUsers(usersRes.data)
      })
      .catch(() => {})
  }, [isStaff])

  const handleChange = (e) => {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }))
  }

  const toggleTag = (tagId) => {
    setSelectedTagIds((prev) =>
      prev.includes(tagId) ? prev.filter((id) => id !== tagId) : [...prev, tagId],
    )
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      const payload = {
        subject: form.subject,
        description: form.description,
        priority: form.priority,
        tag_ids: selectedTagIds,
      }
      if (isStaff) {
        if (form.requester_id) payload.requester_id = Number(form.requester_id)
        if (form.assignee_id) payload.assignee_id = Number(form.assignee_id)
      }
      const { data } = await api.post('/api/tickets', payload)
      navigate(`/tickets/${data.id}`)
    } catch (err) {
      const msg = err.response?.data?.message
      if (err.response?.data?.errors) {
        setError(Object.values(err.response.data.errors).flat().join(' '))
      } else {
        setError(msg || 'Failed to create ticket')
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="border-b border-gray-200 bg-white">
        <div className="mx-auto flex max-w-3xl items-center justify-between px-6 py-3">
          <Link to="/tickets" className="flex items-center gap-2">
            <span className="text-xl">📋</span>
            <span className="font-bold text-gray-900">PulseDesk</span>
          </Link>
          <button
            onClick={() => navigate('/tickets')}
            className="rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100"
          >
            ← Back to list
          </button>
        </div>
      </header>

      <main className="mx-auto max-w-3xl px-6 py-6">
        <h1 className="mb-6 text-xl font-bold text-gray-900">New Ticket</h1>

        {error && (
          <div className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 ring-1 ring-inset ring-red-200">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-5 rounded-xl bg-white p-6 ring-1 ring-gray-200">
          {/* Subject */}
          <div>
            <label className="mb-1 block text-sm font-medium text-gray-700">Subject *</label>
            <input
              type="text"
              name="subject"
              value={form.subject}
              onChange={handleChange}
              required
              autoFocus
              maxLength={255}
              placeholder="Brief summary of the issue"
              className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
            />
          </div>

          {/* Description */}
          <div>
            <label className="mb-1 block text-sm font-medium text-gray-700">Description *</label>
            <textarea
              name="description"
              value={form.description}
              onChange={handleChange}
              required
              rows={6}
              placeholder="Describe the issue in detail..."
              className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
            />
          </div>

          {/* Priority */}
          <div>
            <label className="mb-1 block text-sm font-medium text-gray-700">Priority</label>
            <div className="flex flex-wrap gap-2">
              {PRIORITIES.map((p) => (
                <button
                  key={p}
                  type="button"
                  onClick={() => setForm((prev) => ({ ...prev, priority: p }))}
                  className={`rounded-lg px-3 py-1.5 text-xs font-medium transition ${
                    form.priority === p
                      ? 'bg-indigo-600 text-white'
                      : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                  }`}
                >
                  <PriorityBadge priority={p} />
                </button>
              ))}
            </div>
          </div>

          {/* Staff-only fields */}
          {isStaff && (
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              {/* Requester */}
              <div>
                <label className="mb-1 block text-sm font-medium text-gray-700">Requester</label>
                <select
                  name="requester_id"
                  value={form.requester_id}
                  onChange={handleChange}
                  className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                >
                  <option value="">Select requester...</option>
                  {users.map((u) => (
                    <option key={u.id} value={u.id}>
                      {u.name} ({u.role})
                    </option>
                  ))}
                </select>
              </div>

              {/* Assignee */}
              <div>
                <label className="mb-1 block text-sm font-medium text-gray-700">Assignee</label>
                <select
                  name="assignee_id"
                  value={form.assignee_id}
                  onChange={handleChange}
                  className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                >
                  <option value="">Unassigned</option>
                  {users
                    .filter((u) => u.role === 'admin' || u.role === 'agent')
                    .map((u) => (
                      <option key={u.id} value={u.id}>
                        {u.name}
                      </option>
                    ))}
                </select>
              </div>
            </div>
          )}

          {/* Tags */}
          {availableTags.length > 0 && (
            <div>
              <label className="mb-1 block text-sm font-medium text-gray-700">Tags</label>
              <div className="flex flex-wrap gap-2">
                {availableTags.map((tag) => {
                  const selected = selectedTagIds.includes(tag.id)
                  return (
                    <button
                      key={tag.id}
                      type="button"
                      onClick={() => toggleTag(tag.id)}
                      className={`rounded-full px-3 py-1 text-xs font-medium transition ring-1 ring-inset ${
                        selected
                          ? 'bg-indigo-600 text-white ring-indigo-600'
                          : 'bg-indigo-50 text-indigo-700 ring-indigo-700/10 hover:bg-indigo-100'
                      }`}
                    >
                      {tag.name}
                    </button>
                  )
                })}
              </div>
            </div>
          )}

          {/* Actions */}
          <div className="flex justify-end gap-3 border-t border-gray-100 pt-4">
            <button
              type="button"
              onClick={() => navigate('/tickets')}
              className="rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={loading}
              className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:opacity-50"
            >
              {loading ? 'Creating...' : 'Create ticket'}
            </button>
          </div>
        </form>
      </main>
    </div>
  )
}
