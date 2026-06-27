import { useState, useEffect } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import api from '../lib/api'
import { useAuth } from '../context/AuthContext'
import { StatusBadge, PriorityBadge, TagPill } from '../components/Badges'

export default function TicketDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { user } = useAuth()
  const [ticket, setTicket] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [replyBody, setReplyBody] = useState('')
  const [replyType, setReplyType] = useState('public')
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    const fetchTicket = async () => {
      try {
        const { data } = await api.get(`/api/tickets/${id}`)
        setTicket(data)
      } catch (err) {
        setError(err.response?.data?.message || 'Failed to load ticket')
      } finally {
        setLoading(false)
      }
    }
    fetchTicket()
  }, [id])

  const handleReply = async (e) => {
    e.preventDefault()
    if (!replyBody.trim()) return
    setSubmitting(true)
    try {
      const { data } = await api.post(`/api/tickets/${id}/replies`, {
        body: replyBody,
        type: replyType,
      })
      setTicket((prev) => ({
        ...prev,
        replies: [...(prev.replies || []), data],
      }))
      setReplyBody('')
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to post reply')
    } finally {
      setSubmitting(false)
    }
  }

  const isStaff = user?.role === 'admin' || user?.role === 'agent'

  if (loading) {
    return (
      <div className="flex min-h-screen items-center justify-center text-sm text-gray-400">
        Loading ticket...
      </div>
    )
  }

  if (error && !ticket) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <div className="text-center">
          <p className="text-sm text-red-600">{error}</p>
          <Link to="/tickets" className="mt-3 inline-block text-sm text-indigo-600">
            ← Back to tickets
          </Link>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="border-b border-gray-200 bg-white">
        <div className="mx-auto flex max-w-4xl items-center justify-between px-6 py-3">
          <Link to="/tickets" className="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900">
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

      <main className="mx-auto max-w-4xl px-6 py-6">
        {/* Ticket header card */}
        <div className="mb-6 rounded-xl bg-white p-5 ring-1 ring-gray-200">
          <div className="mb-2 flex items-start justify-between gap-4">
            <h1 className="text-lg font-bold text-gray-900">{ticket.subject}</h1>
            <div className="flex shrink-0 gap-2">
              <StatusBadge status={ticket.status} />
              <PriorityBadge priority={ticket.priority} />
            </div>
          </div>
          <p className="mb-3 text-sm whitespace-pre-wrap text-gray-600">
            {ticket.description}
          </p>
          <div className="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-gray-500">
            <span>
              <strong className="text-gray-700">Requester:</strong>{' '}
              {ticket.requester?.name || '—'} ({ticket.requester?.email})
            </span>
            <span>
              <strong className="text-gray-700">Assignee:</strong>{' '}
              {ticket.assignee?.name || 'Unassigned'}
            </span>
            <span>
              <strong className="text-gray-700">Created:</strong>{' '}
              {new Date(ticket.created_at).toLocaleString()}
            </span>
            {ticket.tags?.length > 0 && (
              <div className="flex items-center gap-1">
                {ticket.tags.map((tag) => (
                  <TagPill key={tag.id} name={tag.name} />
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Reply thread */}
        <div className="mb-2 flex items-center gap-2">
          <h2 className="text-sm font-semibold text-gray-700">
            Conversation ({ticket.replies?.length || 0} replies)
          </h2>
        </div>

        <div className="mb-6 space-y-3">
          {(ticket.replies || []).map((reply) => {
            const isInternal = reply.type === 'internal'
            const isOwnReply = reply.author?.id === user?.id

            return (
              <div
                key={reply.id}
                className={`rounded-xl p-4 ring-1 ${
                  isInternal
                    ? 'bg-amber-50 ring-amber-200'
                    : 'bg-white ring-gray-200'
                }`}
              >
                {/* Reply header */}
                <div className="mb-2 flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <div
                      className={`flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold ${
                        isInternal ? 'bg-amber-200 text-amber-800' : 'bg-indigo-100 text-indigo-700'
                      }`}
                    >
                      {reply.author?.name?.charAt(0).toUpperCase() || '?'}
                    </div>
                    <div>
                      <span className="text-sm font-medium text-gray-900">
                        {reply.author?.name || 'Unknown'}
                        {isOwnReply && (
                          <span className="ml-1 text-xs text-gray-400">(you)</span>
                        )}
                      </span>
                      <span className="ml-2 text-xs text-gray-400">
                        {new Date(reply.created_at).toLocaleString()}
                      </span>
                    </div>
                  </div>
                  {isInternal && (
                    <span className="inline-flex items-center gap-1 rounded-full bg-amber-200 px-2 py-0.5 text-xs font-medium text-amber-800">
                      🔒 Internal note
                    </span>
                  )}
                </div>
                {/* Reply body */}
                <p className={`text-sm whitespace-pre-wrap ${isInternal ? 'text-amber-900' : 'text-gray-700'}`}>
                  {reply.body}
                </p>
              </div>
            )
          })}

          {(!ticket.replies || ticket.replies.length === 0) && (
            <div className="rounded-xl bg-white p-6 text-center text-sm text-gray-400 ring-1 ring-gray-200">
              No replies yet. Start the conversation below.
            </div>
          )}
        </div>

        {/* Reply form */}
        {error && (
          <div className="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-700 ring-1 ring-inset ring-red-200">
            {error}
          </div>
        )}

        <form onSubmit={handleReply} className="rounded-xl bg-white p-4 ring-1 ring-gray-200">
          <div className="mb-3 flex items-center gap-3">
            <label className="text-sm font-medium text-gray-700">Reply type:</label>
            <div className="flex gap-2">
              <button
                type="button"
                onClick={() => setReplyType('public')}
                className={`rounded-lg px-3 py-1.5 text-xs font-medium ${
                  replyType === 'public'
                    ? 'bg-indigo-600 text-white'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                }`}
              >
                💬 Public reply
              </button>
              {isStaff && (
                <button
                  type="button"
                  onClick={() => setReplyType('internal')}
                  className={`rounded-lg px-3 py-1.5 text-xs font-medium ${
                    replyType === 'internal'
                      ? 'bg-amber-500 text-white'
                      : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                  }`}
                >
                  🔒 Internal note
                </button>
              )}
            </div>
          </div>

          <textarea
            value={replyBody}
            onChange={(e) => setReplyBody(e.target.value)}
            rows={4}
            placeholder="Type your reply..."
            className="mb-3 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
          />

          <div className="flex justify-end">
            <button
              type="submit"
              disabled={submitting || !replyBody.trim()}
              className={`rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition disabled:opacity-50 ${
                replyType === 'internal'
                  ? 'bg-amber-500 hover:bg-amber-400'
                  : 'bg-indigo-600 hover:bg-indigo-500'
              }`}
            >
              {submitting ? 'Posting...' : 'Post reply'}
            </button>
          </div>
        </form>
      </main>
    </div>
  )
}
