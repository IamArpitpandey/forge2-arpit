export function StatusBadge({ status }) {
  const styles = {
    open: 'bg-blue-50 text-blue-700 ring-blue-700/10',
    pending: 'bg-amber-50 text-amber-700 ring-amber-700/10',
    resolved: 'bg-emerald-50 text-emerald-700 ring-emerald-700/10',
    closed: 'bg-gray-100 text-gray-600 ring-gray-600/10',
  }
  return (
    <span
      className={`inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset ${
        styles[status] || styles.open
      }`}
    >
      {status}
    </span>
  )
}

export function PriorityBadge({ priority }) {
  const styles = {
    low: 'bg-gray-50 text-gray-600 ring-gray-600/10',
    medium: 'bg-sky-50 text-sky-700 ring-sky-700/10',
    high: 'bg-orange-50 text-orange-700 ring-orange-700/10',
    urgent: 'bg-red-50 text-red-700 ring-red-700/10',
  }
  return (
    <span
      className={`inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset ${
        styles[priority] || styles.medium
      }`}
    >
      {priority}
    </span>
  )
}

export function TagPill({ name }) {
  return (
    <span className="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
      {name}
    </span>
  )
}
