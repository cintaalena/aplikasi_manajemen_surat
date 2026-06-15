/**
 * Navigasi antar field form menggunakan tombol panah.
 * Tambahkan @keydown="handleFormArrowNav" pada elemen <form>.
 *
 * ArrowDown / ArrowUp   → field berikutnya / sebelumnya
 * ArrowRight            → field berikutnya (select & date selalu; text input hanya saat kursor di akhir)
 * ArrowLeft             → field sebelumnya (select & date selalu; text input hanya saat kursor di awal)
 *
 * Catatan:
 *  - textarea dikecualikan sebagai sumber navigasi (panah tetap menggerakkan kursor di dalam teks)
 *  - field readonly dan disabled dilewati sebagai target
 */
export function useFormArrowNav() {
  const handleFormArrowNav = (e) => {
    const KEYS = ['ArrowDown', 'ArrowUp', 'ArrowRight', 'ArrowLeft']
    if (!KEYS.includes(e.key)) return

    const el  = e.target
    const tag = el.tagName.toLowerCase()

    if (tag === 'textarea') return

    const type       = (el.type || '').toLowerCase()
    const isTextLike = tag === 'input' &&
      !['date', 'checkbox', 'radio', 'file'].includes(type) &&
      !el.readOnly

    let direction = 0

    if (e.key === 'ArrowDown') {
      direction = 1
    } else if (e.key === 'ArrowUp') {
      direction = -1
    } else if (e.key === 'ArrowRight') {
      if (isTextLike && el.selectionStart !== el.value.length) return
      direction = 1
    } else if (e.key === 'ArrowLeft') {
      if (isTextLike && el.selectionStart !== 0) return
      direction = -1
    }

    if (direction === 0) return

    const form = el.closest('form')
    if (!form) return

    const fields = Array.from(
      form.querySelectorAll(
        'input:not([type=checkbox]):not([type=radio]):not([type=file]):not([readonly]),' +
        'select,' +
        'textarea'
      )
    ).filter(f => !f.disabled)

    const currentIndex = fields.indexOf(el)
    if (currentIndex === -1) return

    const nextIndex = currentIndex + direction
    if (nextIndex < 0 || nextIndex >= fields.length) return

    e.preventDefault()
    fields[nextIndex].focus()
  }

  return { handleFormArrowNav }
}
