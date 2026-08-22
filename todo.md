# Todo

## Now
- [ ] Import 2–3 real receipts (paste or Ollama) and score matches: exact / wrong / none / skipped
- [ ] Commit the working MVP

## Matching
- [ ] Fix ItemMatcher if real receipts fail (abbreviations, pack sizes, Finnish names)

## Inventory
- [ ] Item delete
- [ ] Configurable low-stock threshold (hardcoded ≤ 2 today)
- [ ] Electrical items: SKU, bin/location, supplier, datasheet URL
- [ ] Export / backup

## App polish
- [ ] Build Vite assets in Docker (no CDN fallback in prod)
- [ ] Gemini API path (optional; off by default)
