# GV Basketball Wiki — Design System

Details of the visual styling, brand colors, typography, layout components, and photo-normalization workflows.

---

## 1. Brand Tokens

These tokens are codified in `build/mu-plugins/gv-assets/gv-brand.css` and synced to the Elementor Global Kit (post ID 5).

### Color Palette

| Token | Hex | Role |
|---|---|---|
| **Navy** | `#123B78` | Primary brand color, headers, buttons |
| **Deep Navy** | `#021F51` | Primary background accents, section headers, overlay backgrounds |
| **Orange** | `#F47B20` | Secondary brand color, main call-to-actions, accent dividers |
| **Gold** | `#C9A24B` | Premium accent color, specific buttons, footer typography accents |
| **Charcoal** | `#1C1C1E` | High-contrast backgrounds, footer borders, body text contrast |
| **Steel** | `#6B6F76` | Subheadings, descriptions, low-contrast text |
| **Light** | `#E6E7E9` | Neutral background light fills |
| **Silver** | `#A7A9AC` | Borders, disabled fields |
| **White** | `#FFFFFF` | Core canvas, text overlays, light-section backgrounds |

### Typography & Fonts

- **Display & Large Headlines:** *Bebas Neue* (all caps, letter-spacing, authoritative)
- **UI & Subheadings:** *Montserrat* (clean, structured, sans-serif)
- **Body & Paragraphs:** *Inter* (highly legible, professional, standard weighting)

---

## 2. Core Layout Components

The design system uses namespaced classes prefixed with `gv-` to avoid conflicts with Elementor:

- `.gv-hero`: Full-width or split hero blocks with dark backgrounds and Bebas Neue headlines.
- `.gv-section`: Standard container wrappers (modifiers: `--light`, `--navy`, `--deep`, `--charcoal`, `--tight`).
- `.gv-wrap`: Centered grid layout bounds (width 1200px max, padded).
- `.gv-btn`: Main buttons (modifiers: `--primary` [orange], `--navy`, `--ghost` [transparent], `--outline`, `--gold` [accent gold]).
- `.gv-grid`: Flex/grid utilities (`--2`, `--3`, `--4` columns).
- `.gv-card`: Card element wrapper with hover transformations.
- `.gv-acc`: Custom HTML5 details accordion element used for dynamic FAQ lists.

---

## 3. Photo Normalization Workflow

To prevent raw, warm phone snaps or inconsistent photography from diluting the site's premium feel, all photos are processed through a cool-neutral normalization process.

### The "Cool-Neutral Premium" Look
- **Median Luma:** `~48-55%`
- **Contrast:** `+~8%` (gentle contrast curve)
- **Saturation:** `~90%` (restrained, non-garish)
- **Color Balance:** Neutral to slightly-cool (gentle blue lift, red suppression, no sepia)

### Local Processing Script (`normalize-photo.sh`)
- **Location:** `build/scripts/normalize-photo.sh`
- **Engine:** Requires local ImageMagick (`magick`) and WebP encoder (`cwebp`).
- **Usage:**
  ```bash
  build/scripts/normalize-photo.sh [--no-stretch] [--quality Q] <input> <output.webp>
  ```
- **How it works:**
  - Auto-stretches luma levels and gammas (skipped with `--no-stretch` for dark silhouettes to prevent grey washing).
  - Saturation set to 90% via `-modulate`.
  - Contrast adjusted via `-sigmoidal-contrast`.
  - Red suppressed to `98.5%`, Blue raised to `102%`.
  - Encoded to `.webp` format at target quality (default 82) with metadata stripped.

### Global CSS Gallery Overlay
The gallery page `/gallery/` has a built-in CSS filter that unifies images at runtime:
```css
.gv-gallery img {
  filter: saturate(.9) contrast(1.06) brightness(1.02);
  transition: transform .3s, filter .3s;
}

/* Soft-Blue overlay layer that dissolves on hover */
.gv-gallery a::after {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: 12px;
  background: #4a70a8;
  mix-blend-mode: soft-light;
  opacity: .2;
  pointer-events: none;
  transition: opacity .3s;
}

.gv-gallery a:hover img {
  transform: scale(1.04);
  filter: saturate(1) contrast(1.05) brightness(1.02);
}
```
This ensures different action photos blend seamlessly into the grid, but spring to full-color life when hovered by visitors.
