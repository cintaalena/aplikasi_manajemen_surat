/**
 * Mengubah teks menjadi Title Case sesuai KBBI:
 * huruf pertama setiap kata kapital, sisanya huruf kecil.
 * Aman untuk nilai null/undefined (dikembalikan apa adanya agar fallback || tetap bekerja).
 */
export const toTitleCase = (str) => {
  if (!str) return str
  return String(str)
    .toLowerCase()
    .replace(/\b\w/g, (c) => c.toUpperCase())
}
